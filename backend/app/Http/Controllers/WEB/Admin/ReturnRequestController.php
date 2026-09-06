<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReturnRequest;
use App\Models\Setting;
use App\Services\CommissionService;
use Illuminate\Support\Facades\DB;

class ReturnRequestController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->middleware('auth:admin');
        $this->commissionService = $commissionService;
    }

    public function index(Request $request)
    {
        $returns = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'seller'])
                               ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                               ->orderBy('id', 'desc')
                               ->get();

        return view('admin.return_request', compact('returns'));
    }

    public function show($id)
    {
        $setting = Setting::first();
        $return = ReturnRequest::with(['order.orderProducts', 'orderProduct.product', 'user', 'seller', 'images'])
                              ->find($id);

        if (! $return) {
            return redirect()->back()->with([
                'messege' => 'İade talebi bulunamadı.',
                'alert-type' => 'error',
            ]);
        }

        $suggestedRefund = null;
        if ($return->order && $return->orderProduct) {
            $suggestedRefund = $return->order->suggestedReturnRefund(
                $return->orderProduct,
                (int) $return->qty,
                $return->id
            );
            if ((float) $return->refund_amount <= 0) {
                $return->refund_amount = $suggestedRefund['refund_amount'];
            }
        }

        return view('admin.show_return_request', compact('return', 'setting', 'suggestedRefund'));
    }

    public function updateStatus(Request $request, $id)
    {
        $return = ReturnRequest::find($id);
        if (! $return) {
            return redirect()->back()->with([
                'messege' => 'İade talebi bulunamadı.',
                'alert-type' => 'error',
            ]);
        }

        $status = (int) $request->status;
        $adminNote = trim((string) ($request->input('admin_note', $request->admin_response) ?? ''));

        if ($status === ReturnRequest::STATUS_REFUNDED) {
            return $this->processRefund($return, $adminNote);
        }

        if ($status === ReturnRequest::STATUS_ADMIN_REJECTED) {
            $request->validate([
                'rejected_reason' => 'required|string|min:5',
                'admin_note' => 'required|string|min:5',
            ], [
                'rejected_reason.required' => 'Red gerekçesi zorunludur.',
                'admin_note.required' => 'Yönetici notu zorunludur (müşteri bunu görür).',
            ]);
        }

        if ($status === ReturnRequest::STATUS_ADMIN_APPROVED) {
            $request->validate([
                'refund_amount' => 'required|numeric|min:0',
                'refund_method' => 'required|string',
                'admin_note' => 'required|string|min:3',
            ], [
                'admin_note.required' => 'Yönetici notu zorunludur.',
            ]);
        }

        $previousStatus = (int) $return->status;

        $return->status = $status;
        $return->admin_response = $adminNote !== '' ? $adminNote : $return->admin_response;
        $return->admin_note = $adminNote !== '' ? $adminNote : $return->admin_note;

        if ($request->filled('refund_amount')) {
            $return->refund_amount = $request->refund_amount;
        }

        if ($request->filled('refund_method')) {
            $return->refund_method = $request->refund_method;
        }

        if ($request->filled('rejected_reason')) {
            $return->rejected_reason = trim((string) $request->rejected_reason);
            if ($status === ReturnRequest::STATUS_ADMIN_REJECTED) {
                $return->rejected_at = now();
            }
        }

        if ($status === ReturnRequest::STATUS_ADMIN_APPROVED) {
            $return->approved_at = now();
            // Satıcı reddini yönetici bozduysa eski red metni müşteri/satıcı ekranında kalmasın
            if ($previousStatus === ReturnRequest::STATUS_SELLER_REJECTED) {
                $return->rejected_reason = null;
                $return->rejected_at = null;
            }
        }

        $return->save();

        if (in_array($status, [ReturnRequest::STATUS_ADMIN_REJECTED, ReturnRequest::STATUS_USER_CANCELLED], true)) {
            app(\App\Services\SellerPayoutService::class)->syncPayoutBlockFromReturns($return->order);
        }

        $messages = [
            ReturnRequest::STATUS_ADMIN_APPROVED => 'İade talebini onayladınız. Sonraki adım: ürün teslimi veya iadeyi tamamlama.',
            ReturnRequest::STATUS_ITEM_RECEIVED => 'Ürün teslim alındı olarak işaretlendi.',
            ReturnRequest::STATUS_ADMIN_REJECTED => 'İade talebini reddettiniz. Müşteri yönetici notunu / red gerekçesini görecek.',
        ];

        return redirect()->back()->with([
            'messege' => $messages[$status] ?? 'Durum güncellendi.',
            'alert-type' => 'success',
        ]);
    }

    protected function processRefund($return, $note)
    {
        if ((int) $return->status === ReturnRequest::STATUS_REFUNDED) {
            return redirect()->back()->with([
                'messege' => 'Bu talep için iade zaten tamamlanmış.',
                'alert-type' => 'error',
            ]);
        }

        DB::beginTransaction();
        try {
            $return->status = ReturnRequest::STATUS_REFUNDED;
            $return->admin_response = $note !== '' ? $note : $return->admin_response;
            $return->admin_note = $note !== '' ? $note : ($return->admin_note ?: 'İade tamamlandı.');
            $return->refunded_at = now();
            if (! $return->refund_method) {
                $return->refund_method = 'original_gateway';
            }
            $return->save();

            $this->commissionService->recordReturn($return);

            try {
                \App\Helpers\MailHelper::setMailConfig();
                $user = \App\Models\User::find($return->user_id);
                if ($user && $user->email) {
                    $return->loadMissing(['order', 'orderProduct.product']);
                    $productName = $return->orderProduct->product->name
                        ?? $return->orderProduct->product_name
                        ?? 'Ürün';
                    $amount = number_format((float) $return->refund_amount, 2, ',', '.') . ' ₺';
                    $content = "İade talebiniz onaylandı ve iade işlemi tamamlandı.\n\nSipariş No: {$return->order->order_id}\nÜrün: {$productName}\nİade Tutarı: {$amount}\n\nTutar, ödeme yönteminize göre hesabınıza yansıyacaktır.";
                    \Mail::to($user->email)->send(new \App\Mail\ReturnApprovedMail($content));
                }
            } catch (\Throwable $e) {
                \Log::warning('Return approved mail failed', ['return_id' => $return->id, 'error' => $e->getMessage()]);
            }

            DB::commit();

            return redirect()->route('admin.return-requests.index')->with([
                'messege' => 'İade tamamlandı. Müşteri bilgilendirildi.',
                'alert-type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with([
                'messege' => 'İade tamamlanamadı: '.$e->getMessage(),
                'alert-type' => 'error',
            ]);
        }
    }
}
