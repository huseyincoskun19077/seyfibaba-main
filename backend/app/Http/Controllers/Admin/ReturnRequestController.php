<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\CommissionService;
use App\Services\ReturnIyzicoRefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnRequestController extends Controller
{
    public function __construct(
        private CommissionService $commissionService,
        private ReturnIyzicoRefundService $returnIyzicoRefundService,
    ) {
        $this->middleware('auth:admin-api');
    }

    public function index(Request $request)
    {
        $returns = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'seller', 'images'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->user_id))
            ->when($request->filled('seller_id'), fn ($query) => $query->where('seller_id', $request->seller_id))
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json(['returns' => $returns]);
    }

    public function show($id)
    {
        $return = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'seller', 'images'])->find($id);
        if (!$return) {
            return response()->json(['message' => 'İade talebi bulunamadı'], 404);
        }

        return response()->json(['return' => $return]);
    }

    public function approve(Request $request, $id)
    {
        $return = ReturnRequest::with('seller')->find($id);
        if (!$return) {
            return response()->json(['message' => 'İade talebi bulunamadı'], 404);
        }

        if (!in_array((int) $return->status, [ReturnRequest::STATUS_SELLER_APPROVED, ReturnRequest::STATUS_SELLER_REJECTED], true)) {
            return response()->json(['message' => 'Bu talep yönetici onayı için hazır değil'], 422);
        }

        $request->merge([
            'return_address' => $request->input(
                'return_address',
                $return->return_address ?: ReturnRequest::buildSellerReturnAddress($return->seller)
            ),
            'return_shipping_payer' => $request->input(
                'return_shipping_payer',
                $return->return_shipping_payer ?: ReturnRequest::defaultShippingPayerForReason($return->reason)
            ),
        ]);

        $request->validate([
            'refund_amount' => 'required|numeric|min:0',
            'refund_method' => 'required|string|max:50',
            'admin_note' => 'nullable|string',
            'return_address' => 'required|string|min:10',
            'return_shipping_payer' => 'required|in:seller,buyer',
            'return_carrier_name' => 'nullable|string|max:100',
            'return_cargo_code' => 'nullable|string|max:120',
            'return_shipping_instructions' => 'nullable|string|max:2000',
        ], [
            'return_address.required' => 'İade adresi zorunludur',
            'return_shipping_payer.required' => 'İade kargo ücretini kimin karşılayacağını seçin',
            'return_shipping_payer.in' => 'Kargo ücretini yalnızca satıcı veya alıcı karşılayabilir',
        ]);

        $note = $request->admin_note;
        $previousStatus = (int) $return->status;
        $refundAmount = (float) $request->refund_amount;
        if ($refundAmount <= 0) {
            $return->loadMissing(['order.orderProducts', 'orderProduct']);
            if ($return->order && $return->orderProduct) {
                $refundAmount = (float) $return->order->suggestedReturnRefund(
                    $return->orderProduct,
                    (int) $return->qty,
                    $return->id
                )['refund_amount'];
            }
        }
        $payer = $request->return_shipping_payer === 'buyer'
            ? ReturnRequest::PAYER_BUYER
            : ReturnRequest::PAYER_SELLER;
        $return->update([
            'status' => ReturnRequest::STATUS_ADMIN_APPROVED,
            'refund_amount' => $refundAmount,
            'refund_method' => $request->refund_method,
            'admin_response' => $note,
            'admin_note' => $note,
            'return_address' => trim((string) $request->return_address),
            'return_shipping_payer' => $payer,
            'return_carrier_name' => $request->filled('return_carrier_name') ? trim((string) $request->return_carrier_name) : ($return->return_carrier_name ?: null),
            'return_cargo_code' => $request->filled('return_cargo_code') ? trim((string) $request->return_cargo_code) : ($return->return_cargo_code ?: null),
            'return_shipping_instructions' => $request->filled('return_shipping_instructions')
                ? trim((string) $request->return_shipping_instructions)
                : ($return->return_shipping_instructions ?: null),
            'approved_at' => now(),
            'rejected_reason' => $previousStatus === ReturnRequest::STATUS_SELLER_REJECTED ? null : $return->rejected_reason,
            'rejected_at' => $previousStatus === ReturnRequest::STATUS_SELLER_REJECTED ? null : $return->rejected_at,
        ]);

        return response()->json(['message' => 'İade talebi yönetici tarafından onaylandı']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string',
            'admin_note' => 'nullable|string',
        ]);

        $return = ReturnRequest::find($id);
        if (!$return) {
            return response()->json(['message' => 'İade talebi bulunamadı'], 404);
        }

        $note = $request->admin_note;
        $return->update([
            'status' => ReturnRequest::STATUS_ADMIN_REJECTED,
            'rejected_reason' => $request->rejected_reason,
            'admin_response' => $note,
            'admin_note' => $note,
            'rejected_at' => now(),
        ]);

        return response()->json(['message' => 'İade talebi yönetici tarafından reddedildi']);
    }

    public function markReceived(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string',
        ]);

        $return = ReturnRequest::find($id);
        if (!$return) {
            return response()->json(['message' => 'İade talebi bulunamadı'], 404);
        }

        if (!in_array((int) $return->status, [ReturnRequest::STATUS_ADMIN_APPROVED, ReturnRequest::STATUS_SELLER_APPROVED], true)) {
            return response()->json(['message' => 'Yalnızca onaylı talepler teslim alındı işaretlenebilir'], 422);
        }

        $note = $request->admin_note;
        $return->update([
            'status' => ReturnRequest::STATUS_ITEM_RECEIVED,
            'admin_response' => $note,
            'admin_note' => $note,
        ]);

        return response()->json(['message' => 'İade ürünü teslim alındı olarak işaretlendi']);
    }

    public function refund(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string',
        ]);

        $return = ReturnRequest::with(['orderProduct', 'order'])->find($id);
        if (!$return) {
            return response()->json(['message' => 'İade talebi bulunamadı'], 404);
        }

        if ((int) $return->status !== ReturnRequest::STATUS_ITEM_RECEIVED) {
            return response()->json([
                'message' => 'Para iadesi için önce satıcı veya yönetici ürünü “teslim alındı” olarak işaretlemelidir',
            ], 422);
        }

        $order = $return->order;
        $refundAmount = (float) $return->refund_amount;
        $note = $request->admin_note;

        DB::beginTransaction();

        try {
            // If payment was via Iyzico, process refund through Iyzico API
            $iyzicoRefundResult = null;
            if ($order && strtolower($order->payment_method) === 'iyzico' && $order->payment_status == 1) {
                $iyzicoRefundResult = $this->returnIyzicoRefundService->refund($order, $return, $refundAmount);

                if (($iyzicoRefundResult['success'] ?? false) === false) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Iyzico iadesi başarısız: ' . ($iyzicoRefundResult['error'] ?? 'Bilinmeyen hata'),
                        'refund_error' => $iyzicoRefundResult['error'] ?? null,
                    ], 422);
                }
            }

            $return->update([
                'status' => ReturnRequest::STATUS_REFUNDED,
                'admin_response' => $note,
                'admin_note' => $note,
                'refunded_at' => now(),
                'refund_transaction_id' => ($iyzicoRefundResult && empty($iyzicoRefundResult['skipped']))
                    ? ($iyzicoRefundResult['transaction_id'] ?? null)
                    : null,
                'refund_status' => ($iyzicoRefundResult && empty($iyzicoRefundResult['skipped']))
                    ? 'iyzico_refunded'
                    : 'manual',
                'refund_error' => null,
            ]);

            // Update order refund status (kolonlar eski DB'lerde olmayabilir)
            if ($order) {
                $orderDirty = false;
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'refound_status')) {
                    $order->refound_status = 1;
                    $orderDirty = true;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'payment_refound_date')) {
                    $order->payment_refound_date = now()->toDateTimeString();
                    $orderDirty = true;
                }
                if ($orderDirty) {
                    $order->save();
                }
            }

            $this->commissionService->recordReturn($return);

            if ($order) {
                app(\App\Services\SellerPayoutService::class)->syncPayoutBlockFromReturns($order);
            }

            DB::commit();

            $message = ($iyzicoRefundResult && empty($iyzicoRefundResult['skipped']))
                ? 'Iyzico iadesi tamamlandı (İşlem: ' . ($iyzicoRefundResult['transaction_id'] ?? '-') . ')'
                : 'Para iadesi tamamlandı (manuel)';

            return response()->json(['message' => $message]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Refund processing error', [
                'return_request_id' => $id,
                'error' => $exception->getMessage(),
            ]);
            return response()->json(['message' => 'İade işlenirken hata: ' . $exception->getMessage()], 500);
        }
    }

    public function stats()
    {
        return response()->json([
            'stats' => [
                'pending' => ReturnRequest::where('status', ReturnRequest::STATUS_PENDING)->count(),
                'seller_approved' => ReturnRequest::where('status', ReturnRequest::STATUS_SELLER_APPROVED)->count(),
                'admin_approved' => ReturnRequest::where('status', ReturnRequest::STATUS_ADMIN_APPROVED)->count(),
                'received' => ReturnRequest::where('status', ReturnRequest::STATUS_ITEM_RECEIVED)->count(),
                'refunded' => ReturnRequest::where('status', ReturnRequest::STATUS_REFUNDED)->count(),
                'rejected' => ReturnRequest::whereIn('status', [ReturnRequest::STATUS_SELLER_REJECTED, ReturnRequest::STATUS_ADMIN_REJECTED])->count(),
            ],
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        return match ((int) $request->status) {
            ReturnRequest::STATUS_ADMIN_APPROVED => $this->approve($request, $id),
            ReturnRequest::STATUS_ADMIN_REJECTED => $this->reject($request, $id),
            ReturnRequest::STATUS_ITEM_RECEIVED => $this->markReceived($request, $id),
            ReturnRequest::STATUS_REFUNDED => $this->refund($request, $id),
            default => response()->json(['message' => 'Geçersiz yönetici durum geçişi'], 422),
        };
    }
}
