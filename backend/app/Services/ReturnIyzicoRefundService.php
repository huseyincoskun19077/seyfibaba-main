<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Log;

class ReturnIyzicoRefundService
{
    public function __construct(private IyzicoService $iyzicoService)
    {
    }

    /**
     * Iyzico siparişlerinde kısmi/tam iadeyi API üzerinden gönderir.
     * Havale siparişlerinde çağrılmamalı.
     *
     * @return array{success: bool, transaction_id?: string|null, error?: string}
     */
    public function refund(Order $order, ReturnRequest $return, float $refundAmount): array
    {
        if (strtolower((string) $order->payment_method) !== 'iyzico' || (int) $order->payment_status !== 1) {
            return [
                'success' => true,
                'transaction_id' => null,
                'skipped' => true,
            ];
        }

        if ($refundAmount <= 0) {
            return [
                'success' => false,
                'error' => 'İade tutarı geçersiz.',
            ];
        }

        $paymentTransactionId = $this->resolvePaymentTransactionId($order, $return);

        if (! $paymentTransactionId) {
            $msg = 'Iyzico ödeme işlem ID\'si bulunamadı. Iyzico panelinden manuel iade gerekebilir.';
            $return->refund_error = $msg;
            $return->save();

            Log::warning('Iyzico refund: paymentTransactionId not found', [
                'order_id' => $order->id,
                'return_request_id' => $return->id,
                'has_payment_data' => ! empty($order->iyzico_payment_data),
            ]);

            return [
                'success' => false,
                'error' => $msg,
            ];
        }

        try {
            $conversationId = 'refund_'.$return->id.'_'.time();
            $result = $this->iyzicoService->refund($paymentTransactionId, $refundAmount, $conversationId);

            Log::info('Iyzico refund result', [
                'return_request_id' => $return->id,
                'order_id' => $order->id,
                'amount' => $refundAmount,
                'payment_transaction_id' => $paymentTransactionId,
                'status' => $result->getStatus(),
                'error_code' => $result->getErrorCode(),
                'error_message' => $result->getErrorMessage(),
            ]);

            if ($result->getStatus() === 'success') {
                return [
                    'success' => true,
                    'transaction_id' => $result->getPaymentId() ?: $paymentTransactionId,
                ];
            }

            $errorMsg = $result->getErrorMessage() ?: 'Iyzico iadesi başarısız.';
            $return->refund_error = $errorMsg;
            $return->save();

            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('Iyzico refund exception', [
                'return_request_id' => $return->id,
                'error' => $e->getMessage(),
            ]);

            $return->refund_error = $e->getMessage();
            $return->save();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function resolvePaymentTransactionId(Order $order, ReturnRequest $return): ?string
    {
        $orderProduct = $return->orderProduct;
        if ($orderProduct && ! empty($orderProduct->iyzico_payment_transaction_id)) {
            return (string) $orderProduct->iyzico_payment_transaction_id;
        }

        $paymentData = $order->iyzico_payment_data
            ? json_decode($order->iyzico_payment_data, true)
            : null;

        if (! is_array($paymentData) || empty($paymentData['items']) || ! is_array($paymentData['items'])) {
            return null;
        }

        if ($orderProduct) {
            $expectedIds = [
                (string) $orderProduct->product_id,
                'PROD-'.(string) $orderProduct->product_id,
            ];
            foreach ($paymentData['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                if (in_array((string) ($item['item_id'] ?? ''), $expectedIds, true)
                    && ! empty($item['payment_transaction_id'])
                ) {
                    return (string) $item['payment_transaction_id'];
                }
            }
        }

        if (count($paymentData['items']) === 1 && ! empty($paymentData['items'][0]['payment_transaction_id'])) {
            return (string) $paymentData['items'][0]['payment_transaction_id'];
        }

        return null;
    }
}
