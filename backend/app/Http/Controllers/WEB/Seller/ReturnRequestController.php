<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        $setting = Setting::first();

        $returns = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'images'])
            ->where('seller_id', $seller->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderByDesc('id')
            ->get();

        return view('seller.return_request', compact('returns', 'setting'));
    }

    public function show($id)
    {
        $seller = Auth::guard('web')->user()->seller;
        $setting = Setting::first();

        $return = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'images', 'seller'])
            ->where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (! $return) {
            return redirect()
                ->route('seller.return-requests.index')
                ->with(['messege' => 'İade talebi bulunamadı.', 'alert-type' => 'error']);
        }

        $defaultReturnAddress = old(
            'return_address',
            $return->return_address ?: ReturnRequest::buildSellerReturnAddress($seller)
        );
        $defaultShippingPayer = old(
            'return_shipping_payer',
            in_array($return->return_shipping_payer, ['seller', 'buyer'], true)
                ? $return->return_shipping_payer
                : ReturnRequest::defaultShippingPayerForReason($return->reason)
        );

        return view('seller.show_return_request', compact(
            'return',
            'setting',
            'defaultReturnAddress',
            'defaultShippingPayer'
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        $seller = Auth::guard('web')->user()->seller;
        $status = (int) $request->status;

        $return = ReturnRequest::where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (! $return) {
            return redirect()
                ->route('seller.return-requests.index')
                ->with(['messege' => 'İade talebi bulunamadı.', 'alert-type' => 'error']);
        }

        if ((int) $return->status !== ReturnRequest::STATUS_PENDING) {
            return redirect()->back()->with([
                'messege' => 'Bu talep artık bekleyen durumda değil. İşlem yapılamaz.',
                'alert-type' => 'error',
            ]);
        }

        if ($status === ReturnRequest::STATUS_SELLER_APPROVED) {
            $request->validate([
                'return_address' => 'required|string|min:10',
                'return_shipping_payer' => 'required|in:seller,buyer',
                'return_carrier_name' => 'nullable|string|max:100',
                'return_cargo_code' => 'nullable|string|max:120',
                'return_shipping_instructions' => 'nullable|string|max:2000',
                'seller_note' => 'nullable|string|max:2000',
            ], [
                'return_address.required' => 'İade adresi zorunludur.',
                'return_address.min' => 'İade adresi en az 10 karakter olmalıdır.',
                'return_shipping_payer.required' => 'İade kargo ücretini kimin karşılayacağını seçin.',
                'return_shipping_payer.in' => 'Kargo ücretini yalnızca satıcı veya alıcı karşılayabilir.',
            ]);

            $payer = $request->return_shipping_payer === 'buyer'
                ? ReturnRequest::PAYER_BUYER
                : ReturnRequest::PAYER_SELLER;

            try {
                $return->update([
                    'status' => ReturnRequest::STATUS_SELLER_APPROVED,
                    'vendor_response' => $request->seller_note,
                    'seller_note' => $request->seller_note,
                    'return_address' => trim((string) $request->return_address),
                    'return_shipping_payer' => $payer,
                    'return_carrier_name' => $request->filled('return_carrier_name') ? trim((string) $request->return_carrier_name) : null,
                    'return_cargo_code' => $request->filled('return_cargo_code') ? trim((string) $request->return_cargo_code) : null,
                    'return_shipping_instructions' => $request->filled('return_shipping_instructions') ? trim((string) $request->return_shipping_instructions) : null,
                    'approved_at' => now(),
                ]);
            } catch (\Throwable $e) {
                \Log::error('Seller return approve failed', [
                    'return_id' => $return->id,
                    'error' => $e->getMessage(),
                ]);

                $hint = str_contains($e->getMessage(), 'return_address')
                    || str_contains($e->getMessage(), 'Unknown column')
                    ? ' Sunucuda iade kargo alanları için migration çalıştırılmamış olabilir (php artisan migrate).'
                    : '';

                return redirect()->back()->with([
                    'messege' => 'İade onaylanamadı.'.$hint,
                    'alert-type' => 'error',
                ])->withInput();
            }

            return redirect()->back()->with([
                'messege' => 'İade talebini onayladınız. Müşteri iade adresi ve kargo talimatını görecek; süreç yöneticiye iletildi.',
                'alert-type' => 'success',
            ]);
        }

        if ($status === ReturnRequest::STATUS_SELLER_REJECTED) {
            $request->validate([
                'rejected_reason' => 'required|string|min:5',
            ], [
                'rejected_reason.required' => 'Red gerekçesi zorunludur.',
                'rejected_reason.min' => 'Red gerekçesi en az 5 karakter olmalıdır.',
            ]);

            $reason = trim((string) $request->rejected_reason);

            $return->update([
                'status' => ReturnRequest::STATUS_SELLER_REJECTED,
                'vendor_response' => $reason,
                'seller_note' => $reason,
                'rejected_reason' => $reason,
                'rejected_at' => now(),
            ]);

            app(\App\Services\SellerPayoutService::class)->syncPayoutBlockFromReturns($return->order);

            return redirect()->back()->with([
                'messege' => 'İade talebini reddettiniz. Müşteri “İade talebi reddedildi” ve yazdığınız gerekçeyi görecek.',
                'alert-type' => 'success',
            ]);
        }

        return redirect()->back()->with([
            'messege' => 'Geçersiz işlem. Lütfen onay veya red seçin.',
            'alert-type' => 'error',
        ]);
    }
}
