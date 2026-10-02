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

        [$productAmount, $shippingAmount] = $this->splitRefundAmounts($order, $return, $refundAmount);
        $parts = [];
        if ($productAmount > 0.009) {
            $parts[] = [
                'kind' => 'product',
                'amount' => $productAmount,
                'transaction_id' => $this->resolvePaymentTransactionId($order, $return),
            ];
        }
        if ($shippingAmount > 0.009) {
            $parts[] = [
                'kind' => 'shipping',
                'amount' => $shippingAmount,
                'transaction_id' => $this->resolveShippingTransactionId($order, (int) $return->seller_id),
            ];
        }

        foreach ($parts as $part) {
            if (empty($part['transaction_id'])) {
                $msg = $part['kind'] === 'shipping'
                    ? 'Kargo iadesi için Iyzico işlem ID\'si bulunamadı.'
                    : 'Iyzico ödeme işlem ID\'si bulunamadı. Iyzico panelinden manuel iade gerekebilir.';
                $return->refund_error = $msg;
                $return->save();

                return [
                    'success' => false,
                    'error' => $msg,
                ];
            }
        }

        $transactionIds = [];
        foreach ($parts as $part) {
            try {
                $conversationId = 'refund_'.$return->id.'_'.$part['kind'].'_'.time();
                $result = $this->iyzicoService->refund($part['transaction_id'], $part['amount'], $conversationId);

                Log::info('Iyzico refund result', [
                    'return_request_id' => $return->id,
                    'order_id' => $order->id,
                    'kind' => $part['kind'],
                    'amount' => $part['amount'],
                    'payment_transaction_id' => $part['transaction_id'],
                    'status' => $result->getStatus(),
                    'error_code' => $result->getErrorCode(),
                    'error_message' => $result->getErrorMessage(),
                ]);

                if ($result->getStatus() !== 'success') {
                    $errorMsg = $result->getErrorMessage() ?: 'Iyzico iadesi başarısız.';
                    $return->refund_error = $errorMsg;
                    $return->save();

                    return [
                        'success' => false,
                        'error' => $errorMsg,
                    ];
                }

                $transactionIds[] = $result->getPaymentId() ?: $part['transaction_id'];
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

        return [
            'success' => true,
            'transaction_id' => implode(',', $transactionIds),
        ];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function splitRefundAmounts(Order $order, ReturnRequest $return, float $refundAmount): array
    {
        $shippingAmount = 0.0;
        $orderProduct = $return->orderProduct;
        if ($orderProduct) {
            $calc = $order->suggestedReturnRefund(
                $orderProduct,
                (int) $return->qty,
                $return->id
            );
            $suggested = round((float) ($calc['refund_amount'] ?? 0), 2);
            $shipping = round((float) ($calc['shipping'] ?? 0), 2);
            if (! empty($calc['shipping_included']) && $shipping > 0 && $suggested > 0) {
                $shippingAmount = round($shipping * min(1, $refundAmount / $suggested), 2);
                $shippingAmount = min($shippingAmount, $refundAmount);
            }
        }

        return [round($refundAmount - $shippingAmount, 2), round($shippingAmount, 2)];
    }

    private function resolveShippingTransactionId(Order $order, int $sellerId): ?string
    {
        foreach ($order->sellerShippingRows() as $row) {
            if ((int) $row['vendor_id'] === $sellerId && ! empty($row['payment_transaction_id'])) {
                return (string) $row['payment_transaction_id'];
            }
        }

        $paymentData = $order->iyzico_payment_data
            ? json_decode($order->iyzico_payment_data, true)
            : null;
        if (! is_array($paymentData) || empty($paymentData['items'])) {
            return null;
        }

        $wanted = [
            $order->shippingBasketItemId($sellerId),
            'SHIPPING-'.$order->id,
        ];
        foreach ($paymentData['items'] as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (in_array((string) ($item['item_id'] ?? ''), $wanted, true) && ! empty($item['payment_transaction_id'])) {
                return (string) $item['payment_transaction_id'];
            }
        }

        return null;
    }

    /**
     * Admin ekranı için Iyzico ürün/işlem eşlemesi.
     *
     * @return array{
     *   basket_item_id: string|null,
     *   product_id: int|null,
     *   payment_transaction_id: string|null,
     *   payment_id: string|null,
     *   matched_from: string|null
     * }
     */
    public function resolveDisplayInfo(Order $order, ReturnRequest $return): array
    {
        $orderProduct = $return->orderProduct;
        $productId = $orderProduct ? (int) $orderProduct->product_id : null;
        $basketItemId = $productId ? 'PROD-'.$productId : null;
        $txn = $this->resolvePaymentTransactionId($order, $return);
        $matchedFrom = null;
        if ($orderProduct && ! empty($orderProduct->iyzico_payment_transaction_id)) {
            $matchedFrom = 'order_product';
        } elseif ($txn) {
            $matchedFrom = 'iyzico_payment_data';
        }

        $paymentData = $order->iyzico_payment_data
            ? json_decode($order->iyzico_payment_data, true)
            : null;
        $paymentId = is_array($paymentData)
            ? ($paymentData['payment_id'] ?? $paymentData['paymentId'] ?? null)
            : null;

        return [
            'basket_item_id' => $basketItemId,
            'product_id' => $productId,
            'payment_transaction_id' => $txn,
            'payment_id' => $paymentId ? (string) $paymentId : null,
            'matched_from' => $matchedFrom,
        ];
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
