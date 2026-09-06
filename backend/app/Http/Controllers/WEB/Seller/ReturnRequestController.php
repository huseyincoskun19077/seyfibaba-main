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

        $return = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'images'])
            ->where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (! $return) {
            return redirect()
                ->route('seller.return-requests.index')
                ->with(['messege' => 'İade talebi bulunamadı.', 'alert-type' => 'error']);
        }

        return view('seller.show_return_request', compact('return', 'setting'));
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
            $return->update([
                'status' => ReturnRequest::STATUS_SELLER_APPROVED,
                'vendor_response' => $request->seller_note,
                'seller_note' => $request->seller_note,
                'approved_at' => now(),
            ]);

            return redirect()->back()->with([
                'messege' => 'İade talebini onayladınız. Süreç yöneticiye iletildi; para iadesi yönetici tamamlayacak.',
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
