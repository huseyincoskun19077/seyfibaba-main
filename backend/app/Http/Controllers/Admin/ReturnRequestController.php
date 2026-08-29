<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\CommissionService;
use App\Services\IyzicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnRequestController extends Controller
{
    public function __construct(
        private CommissionService $commissionService,
        private IyzicoService $iyzicoService,
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
            return response()->json(['message' => 'Return request not found'], 404);
        }

        return response()->json(['return' => $return]);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0',
            'refund_method' => 'required|string|max:50',
            'admin_note' => 'nullable|string',
        ]);

        $return = ReturnRequest::find($id);
        if (!$return) {
            return response()->json(['message' => 'Return request not found'], 404);
        }

        if (!in_array((int) $return->status, [ReturnRequest::STATUS_SELLER_APPROVED, ReturnRequest::STATUS_SELLER_REJECTED], true)) {
            return response()->json(['message' => 'Return request is not ready for admin approval'], 422);
        }

        $note = $request->admin_note;
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
        $return->update([
            'status' => ReturnRequest::STATUS_ADMIN_APPROVED,
            'refund_amount' => $refundAmount,
            'refund_method' => $request->refund_method,
            'admin_response' => $note,
            'admin_note' => $note,
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Return request approved by admin']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string',
            'admin_note' => 'nullable|string',
        ]);

        $return = ReturnRequest::find($id);
        if (!$return) {
            return response()->json(['message' => 'Return request not found'], 404);
        }

        $note = $request->admin_note;
        $return->update([
            'status' => ReturnRequest::STATUS_ADMIN_REJECTED,
            'rejected_reason' => $request->rejected_reason,
            'admin_response' => $note,
            'admin_note' => $note,
            'rejected_at' => now(),
        ]);

        return response()->json(['message' => 'Return request rejected by admin']);
    }

    public function markReceived(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string',
        ]);

        $return = ReturnRequest::find($id);
        if (!$return) {
            return response()->json(['message' => 'Return request not found'], 404);
        }

        if ((int) $return->status !== ReturnRequest::STATUS_ADMIN_APPROVED) {
            return response()->json(['message' => 'Only admin-approved requests can be marked as received'], 422);
        }

        $note = $request->admin_note;
        $return->update([
            'status' => ReturnRequest::STATUS_ITEM_RECEIVED,
            'admin_response' => $note,
            'admin_note' => $note,
        ]);

        return response()->json(['message' => 'Returned item marked as received']);
    }

    public function refund(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string',
        ]);

        $return = ReturnRequest::with(['orderProduct', 'order'])->find($id);
        if (!$return) {
            return response()->json(['message' => 'Return request not found'], 404);
        }

        if (!in_array((int) $return->status, [ReturnRequest::STATUS_ADMIN_APPROVED, ReturnRequest::STATUS_ITEM_RECEIVED], true)) {
            return response()->json(['message' => 'Return request is not ready for refund'], 422);
        }

        $order = $return->order;
        $refundAmount = (float) $return->refund_amount;
        $note = $request->admin_note;

        DB::beginTransaction();

        try {
            // If payment was via Iyzico, process refund through Iyzico API
            $iyzicoRefundResult = null;
            if ($order && strtolower($order->payment_method) === 'iyzico' && $order->payment_status == 1) {
                $iyzicoRefundResult = $this->processIyzicoRefund($order, $return, $refundAmount);

                if ($iyzicoRefundResult['success'] === false) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Iyzico refund failed: ' . $iyzicoRefundResult['error'],
                        'refund_error' => $iyzicoRefundResult['error'],
                    ], 422);
                }
            }

            $return->update([
                'status' => ReturnRequest::STATUS_REFUNDED,
                'admin_response' => $note,
                'admin_note' => $note,
                'refunded_at' => now(),
                'refund_transaction_id' => $iyzicoRefundResult['transaction_id'] ?? null,
                'refund_status' => $iyzicoRefundResult ? 'iyzico_refunded' : 'manual',
            ]);

            // Update order refund status
            if ($order) {
                $order->refound_status = 1;
                $order->payment_refound_date = now()->toDateTimeString();
                $order->save();
            }

            $this->commissionService->recordReturn($return);

            DB::commit();

            $message = $iyzicoRefundResult
                ? 'Iyzico refund processed successfully (Transaction: ' . ($iyzicoRefundResult['transaction_id'] ?? 'N/A') . ')'
                : 'Refund processed successfully (manual)';

            return response()->json(['message' => $message]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Refund processing error', [
                'return_request_id' => $id,
                'error' => $exception->getMessage(),
            ]);
            return response()->json(['message' => 'Error processing refund: ' . $exception->getMessage()], 500);
        }
    }

    private function processIyzicoRefund($order, ReturnRequest $return, float $refundAmount): array
    {
        $paymentData = $order->iyzico_payment_data
            ? json_decode($order->iyzico_payment_data, true)
            : null;

        // Find the matching paymentTransactionId for this order product
        $paymentTransactionId = null;
        if ($paymentData && !empty($paymentData['items'])) {
            $orderProduct = $return->orderProduct;
            // Match by item_id (product ID used during checkout)
            foreach ($paymentData['items'] as $item) {
                // Iyzico checkout item_id formatı: genelde "PROD-{productId}"
                $expectedIds = $orderProduct ? [
                    (string) $orderProduct->product_id,
                    'PROD-' . (string) $orderProduct->product_id,
                ] : [];

                if ($orderProduct && in_array((string) $item['item_id'], $expectedIds, true)) {
                    $paymentTransactionId = $item['payment_transaction_id'];
                    break;
                }
            }
            // Fallback: use first item if single-item order or no match found
            if (!$paymentTransactionId && count($paymentData['items']) === 1) {
                $paymentTransactionId = $paymentData['items'][0]['payment_transaction_id'];
            }
        }

        if (!$paymentTransactionId) {
            // Store error but allow manual refund
            $return->update([
                'refund_error' => 'Iyzico payment transaction ID not found. Payment data may be missing (orders placed before refund feature).',
            ]);

            Log::warning('Iyzico refund: paymentTransactionId not found', [
                'order_id' => $order->id,
                'return_request_id' => $return->id,
                'has_payment_data' => (bool) $paymentData,
            ]);

            return [
                'success' => false,
                'error' => 'Iyzico ödeme işlem ID\'si bulunamadı. Bu sipariş refund öncesi alınmış olabilir — manuel iade gerekebilir.',
            ];
        }

        try {
            $conversationId = 'refund_' . $return->id . '_' . time();
            $result = $this->iyzicoService->refund($paymentTransactionId, $refundAmount, $conversationId);

            Log::info('Iyzico refund result', [
                'return_request_id' => $return->id,
                'status' => $result->getStatus(),
                'error_code' => $result->getErrorCode(),
                'error_message' => $result->getErrorMessage(),
            ]);

            if ($result->getStatus() === 'success') {
                return [
                    'success' => true,
                    'transaction_id' => $result->getPaymentId(),
                ];
            }

            $errorMsg = $result->getErrorMessage() ?: 'Iyzico refund failed with unknown error';
            $return->update(['refund_error' => $errorMsg]);

            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('Iyzico refund exception', [
                'return_request_id' => $return->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
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
            default => response()->json(['message' => 'Unsupported admin status transition'], 422),
        };
    }
}
