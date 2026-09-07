<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ReturnRequest;
use App\Models\Vendor;
use App\Notifications\SellerPayoutReleasedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SellerPayoutService
{
    public function __construct(
        private readonly PayoutSettingsService $payoutSettings,
        private readonly IyzicoService $iyzicoService,
    ) {
    }

    public function isIyzicoPayment(Order $order): bool
    {
        return $order->payment_status == 1
            && strtolower((string) $order->payment_method) === 'iyzico';
    }

    public function isBankPayment(Order $order): bool
    {
        return $order->payment_status == 1
            && strtolower((string) $order->payment_method) === 'bankpayment';
    }

    public function schedulePayoutEligibility(Order $order, ?Carbon $from = null): void
    {
        $eligibleAt = ($from ?? now())->copy()->addDays($this->payoutSettings->payoutHoldDays());

        if (! $order->payout_eligible_at) {
            $order->payout_eligible_at = $eligibleAt;
            $order->save();
        }

        OrderProduct::query()
            ->where('order_id', $order->id)
            ->whereNull('payout_eligible_at')
            ->update(['payout_eligible_at' => $eligibleAt]);
    }

    public function eligibleOrdersQuery(bool $force = false)
    {
        $query = Order::query()
            ->where('order_status', 3)
            ->whereNull('payout_blocked_at')
            ->where(function ($q) {
                $q->whereNull('payout_hold_until')
                    ->orWhere('payout_hold_until', '<=', now());
            })
            ->where(function ($q) {
                $q->where('payout_status', 'pending')
                    ->orWhereNull('payout_status');
            })
            ->whereNull('payout_processed_at');

        if (! $force) {
            $query->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('payout_eligible_at')
                        ->where('payout_eligible_at', '<=', now());
                })->orWhere(function ($inner) {
                    $cutoff = now()->subDays($this->payoutSettings->payoutHoldDays());
                    $inner->where(function ($confirmed) use ($cutoff) {
                        $confirmed->whereNotNull('customer_confirmed_at')
                            ->where('customer_confirmed_at', '<=', $cutoff);
                    })->orWhere(function ($auto) use ($cutoff) {
                        $auto->whereNull('customer_confirmed_at')
                            ->whereNotNull('auto_complete_date')
                            ->where('auto_complete_date', '<=', $cutoff);
                    });
                });
            });
        }

        return $query;
    }

    /**
     * @return array{success: bool, message: string, results: array<int, array<string, mixed>>}
     */
    public function processOrderPayout(Order $order, bool $force = false): array
    {
        $order = $order->fresh(['orderProducts']);

        // Kısmi iade tamamlandıysa eski "Aktif iade" bloğunu kaldır.
        $this->syncPayoutBlockFromReturns($order);
        $order = $order->fresh(['orderProducts']);

        if ((int) $order->order_status !== 3) {
            return $this->failure('Sipariş tamamlanmış olmalıdır.');
        }

        if ($order->isFullyRefundedForSeller()
            || ($order->payout_status ?? '') === 'cancelled'
            || $order->payout_block_reason === self::PAYOUT_BLOCK_FULL_RETURN
        ) {
            $this->markFullyRefundedNoPayout($order);

            return $this->failure('Ürünler iade edildi — hakediş yok.');
        }

        if ($order->payout_blocked_at) {
            $reason = trim((string) ($order->payout_block_reason ?? ''));

            return $this->failure(
                $reason !== ''
                    ? 'Bu siparişin ödemesi bloklanmış: '.$reason
                    : 'Bu siparişin ödemesi bloklanmış.'
            );
        }

        if (! $force && $order->payout_hold_until && now()->lt($order->payout_hold_until)) {
            return $this->failure('Bu siparişin ödemesi bekletmede: '.$order->payout_hold_until);
        }

        if ($order->payout_processed_at) {
            return $this->failure('Satıcı ödemesi zaten işlendi.');
        }

        if ($this->hasActiveReturnBlock($order)) {
            return $this->failure('Aktif iade talebi nedeniyle ödeme yapılamaz.');
        }

        if ($this->isBankPayment($order)) {
            return $this->markBankOrderWithdrawable($order);
        }

        if (! $this->isIyzicoPayment($order)) {
            return $this->failure('Bu ödeme yöntemi için otomatik satıcı ödemesi tanımlı değil.');
        }

        if (! $force && ! $this->isPayoutDue($order)) {
            return $this->failure('Ödeme bekleme süresi henüz dolmadı.');
        }

        $order->payout_status = 'processing';
        $order->save();

        $results = $this->approveIyzicoOrderItems($order);
        $allSuccess = collect($results)->every(fn (array $row) => ($row['status'] ?? '') === 'success');
        $dryRun = $this->payoutSettings->iyzicoPayoutDryRun();
        $wasRealApprove = collect($results)->contains(
            fn (array $row) => ($row['status'] ?? '') === 'success' && empty($row['dry_run']) && empty($row['skipped'])
        );

        if ($allSuccess) {
            $order->seller_paid_at = now();
            $order->payout_processed_at = now();
            $order->payout_status = 'completed';
            $order->save();

            if (! $dryRun && $wasRealApprove) {
                $this->notifySellersPayoutReleased($order);
            }

            return [
                'success' => true,
                'message' => 'İyzico onayları başarıyla gönderildi.',
                'results' => $results,
            ];
        }

        $order->payout_status = 'failed';
        $order->save();

        return [
            'success' => false,
            'message' => 'Bazı satırlar için İyzico onayı başarısız oldu.',
            'results' => $results,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function approveIyzicoOrderItems(Order $order): array
    {
        $this->syncPaymentTransactionIds($order);

        $results = [];
        $dryRun = $this->payoutSettings->iyzicoPayoutDryRun();

        foreach ($order->orderProducts as $orderProduct) {
            if ((int) $orderProduct->seller_id === 0) {
                continue;
            }

            if ($orderProduct->iyzico_approved_at) {
                $results[] = [
                    'order_product_id' => $orderProduct->id,
                    'status' => 'success',
                    'message' => 'Zaten onaylanmış',
                    'skipped' => true,
                ];
                continue;
            }

            $refundedQty = $this->refundedQtyForOrderProduct($orderProduct);
            if ($refundedQty >= (int) $orderProduct->qty) {
                $orderProduct->payout_status = 'cancelled';
                $orderProduct->payout_block_reason = self::PAYOUT_BLOCK_FULL_RETURN;
                $orderProduct->save();

                $results[] = [
                    'order_product_id' => $orderProduct->id,
                    'status' => 'success',
                    'message' => 'Satır tamamen iade edildi — Iyzico onayı atlandı',
                    'skipped' => true,
                ];
                continue;
            }

            $transactionId = (string) ($orderProduct->iyzico_payment_transaction_id ?? '');
            if ($transactionId === '') {
                $results[] = [
                    'order_product_id' => $orderProduct->id,
                    'status' => 'error',
                    'message' => 'paymentTransactionId bulunamadı',
                ];
                continue;
            }

            try {
                if ($dryRun) {
                    Log::info('Iyzico payout dry-run approve', [
                        'order_id' => $order->id,
                        'order_product_id' => $orderProduct->id,
                        'payment_transaction_id' => $transactionId,
                    ]);

                    $this->markOrderProductApproved($orderProduct, $transactionId, true);

                    $results[] = [
                        'order_product_id' => $orderProduct->id,
                        'payment_transaction_id' => $transactionId,
                        'status' => 'success',
                        'message' => 'Dry-run: onay simüle edildi',
                        'dry_run' => true,
                    ];
                    continue;
                }

                $approval = $this->iyzicoService->approvePaymentItem(
                    $transactionId,
                    'order-'.$order->id.'-op-'.$orderProduct->id
                );

                if ($approval->getStatus() === 'success') {
                    $this->markOrderProductApproved($orderProduct, $transactionId, false);

                    $results[] = [
                        'order_product_id' => $orderProduct->id,
                        'payment_transaction_id' => $transactionId,
                        'status' => 'success',
                        'message' => 'İyzico onayı başarılı',
                    ];
                } elseif ($this->isAlreadyApprovedIyzicoError($approval->getErrorCode(), $approval->getErrorMessage())) {
                    $this->markOrderProductApproved($orderProduct, $transactionId, false);

                    $results[] = [
                        'order_product_id' => $orderProduct->id,
                        'payment_transaction_id' => $transactionId,
                        'status' => 'success',
                        'message' => 'Iyzico zaten onaylı',
                        'skipped' => true,
                    ];
                } else {
                    $orderProduct->payout_status = 'failed';
                    $orderProduct->save();

                    $results[] = [
                        'order_product_id' => $orderProduct->id,
                        'payment_transaction_id' => $transactionId,
                        'status' => 'error',
                        'message' => (string) ($approval->getErrorMessage() ?: 'İyzico onayı başarısız'),
                        'error_code' => $approval->getErrorCode(),
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Iyzico approve exception', [
                    'order_id' => $order->id,
                    'order_product_id' => $orderProduct->id,
                    'payment_transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                ]);

                $orderProduct->payout_status = 'failed';
                $orderProduct->save();

                $results[] = [
                    'order_product_id' => $orderProduct->id,
                    'payment_transaction_id' => $transactionId,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function refundedQtyForOrderProduct(OrderProduct $orderProduct): int
    {
        return (int) ReturnRequest::query()
            ->where('order_product_id', $orderProduct->id)
            ->where('status', ReturnRequest::STATUS_REFUNDED)
            ->sum('qty');
    }

    protected function isAlreadyApprovedIyzicoError(?string $errorCode, ?string $errorMessage): bool
    {
        $haystack = strtolower(trim(($errorCode ?? '').' '.($errorMessage ?? '')));
        if ($haystack === '') {
            return false;
        }

        return str_contains($haystack, 'already')
            || str_contains($haystack, 'approved')
            || str_contains($haystack, 'onaylanm')
            || str_contains($haystack, 'daha önce');
    }

    public function syncPaymentTransactionIds(Order $order): void
    {
        $paymentData = $order->iyzico_payment_data
            ? json_decode($order->iyzico_payment_data, true)
            : null;

        if (! is_array($paymentData) || empty($paymentData['items'])) {
            return;
        }

        $itemsByProductId = collect($paymentData['items'])
            ->filter(fn ($item) => ! empty($item['payment_transaction_id']))
            ->keyBy(function ($item) {
                $itemId = (string) ($item['item_id'] ?? '');
                if (preg_match('/^PROD-(\d+)$/', $itemId, $matches)) {
                    return (int) $matches[1];
                }

                return $itemId;
            });

        foreach ($order->orderProducts as $orderProduct) {
            if ($orderProduct->iyzico_payment_transaction_id) {
                continue;
            }

            $match = $itemsByProductId->get((int) $orderProduct->product_id);
            if (! $match && $itemsByProductId->count() === 1) {
                $match = $itemsByProductId->first();
            }

            if ($match) {
                $orderProduct->iyzico_payment_transaction_id = (string) $match['payment_transaction_id'];
                $orderProduct->save();
            }
        }
    }

    public const PAYOUT_BLOCK_FULL_RETURN = 'Ürünler iade edildi — hakediş yok';

    public function blockPayoutForReturn(Order $order, ?string $reason = null): void
    {
        $reason = $reason ?: 'Aktif iade talebi';
        $alreadyFullReturn = $order->payout_block_reason === self::PAYOUT_BLOCK_FULL_RETURN;

        if ($order->payout_blocked_at && $alreadyFullReturn && $reason !== self::PAYOUT_BLOCK_FULL_RETURN) {
            return;
        }

        if ($order->payout_blocked_at && $reason === 'Aktif iade talebi' && ! $alreadyFullReturn) {
            return;
        }

        $order->payout_blocked_at = now();
        $order->payout_block_reason = $reason;
        if ($reason === self::PAYOUT_BLOCK_FULL_RETURN) {
            $order->payout_status = 'cancelled';
        }
        $order->save();

        OrderProduct::query()
            ->where('order_id', $order->id)
            ->whereIn('payout_status', ['pending', 'blocked'])
            ->update([
                'payout_status' => 'blocked',
                'payout_block_reason' => $reason,
            ]);
    }

    public function markFullyRefundedNoPayout(Order $order): void
    {
        $this->blockPayoutForReturn($order, self::PAYOUT_BLOCK_FULL_RETURN);
    }

    public function syncPayoutBlockFromReturns(Order $order): void
    {
        $order->loadMissing('orderProducts');

        if ($order->isFullyRefundedForSeller()) {
            $this->markFullyRefundedNoPayout($order);

            return;
        }

        if ($order->payout_block_reason === self::PAYOUT_BLOCK_FULL_RETURN) {
            return;
        }

        if ($this->hasActiveReturnBlock($order)) {
            $this->blockPayoutForReturn($order, 'Aktif iade talebi');

            return;
        }

        // Aktif iade yok + tam iade değil → iade kaynaklı bloğu kaldır (kısmi iade sonrası).
        if ($this->isReturnDrivenPayoutBlockReason($order->payout_block_reason)) {
            $order->payout_blocked_at = null;
            $order->payout_block_reason = null;
            $order->save();

            OrderProduct::query()
                ->where('order_id', $order->id)
                ->where('payout_status', 'blocked')
                ->where(function ($q) {
                    $q->whereNull('payout_block_reason')
                        ->orWhere('payout_block_reason', 'Aktif iade talebi')
                        ->orWhere('payout_block_reason', 'like', 'İade talebi #%');
                })
                ->update([
                    'payout_status' => 'pending',
                    'payout_block_reason' => null,
                ]);
        }
    }

    protected function isReturnDrivenPayoutBlockReason(?string $reason): bool
    {
        $reason = trim((string) $reason);
        if ($reason === '' || $reason === 'Aktif iade talebi') {
            return $reason === 'Aktif iade talebi';
        }

        return str_starts_with($reason, 'İade talebi #');
    }

    public function hasActiveReturnBlock(Order $order): bool
    {
        return ReturnRequest::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [
                ReturnRequest::STATUS_PENDING,
                ReturnRequest::STATUS_SELLER_APPROVED,
                ReturnRequest::STATUS_ADMIN_APPROVED,
                ReturnRequest::STATUS_ITEM_RECEIVED,
            ])
            ->exists();
    }

    protected function isPayoutDue(Order $order): bool
    {
        if ($order->payout_eligible_at && now()->gte($order->payout_eligible_at)) {
            return true;
        }

        $cutoff = now()->subDays($this->payoutSettings->payoutHoldDays());
        if ($order->customer_confirmed_at && $order->customer_confirmed_at <= $cutoff) {
            return true;
        }

        if (! $order->customer_confirmed_at && $order->auto_complete_date && $order->auto_complete_date <= $cutoff) {
            return true;
        }

        return false;
    }

    /**
     * @return array{success: bool, message: string, results: array<int, array<string, mixed>>}
     */
    protected function markBankOrderWithdrawable(Order $order): array
    {
        $order->payout_status = 'completed';
        $order->payout_processed_at = null;
        $order->save();

        OrderProduct::query()
            ->where('order_id', $order->id)
            ->update([
                'payout_status' => 'paid',
                'payout_processed_at' => now(),
            ]);

        return [
            'success' => true,
            'message' => 'Havale siparişi: tutar satıcı bakiyesine eklendi (çekim talebi ile ödenir).',
            'results' => [[
                'channel' => 'bank_withdraw',
                'status' => 'success',
            ]],
        ];
    }

    protected function markOrderProductApproved(OrderProduct $orderProduct, string $transactionId, bool $dryRun): void
    {
        $orderProduct->iyzico_payment_transaction_id = $transactionId;
        $orderProduct->iyzico_approved_at = now();
        $orderProduct->payout_status = 'paid';
        $orderProduct->payout_processed_at = now();
        $orderProduct->save();
    }

    protected function notifySellersPayoutReleased(Order $order): void
    {
        $sellerIds = $order->orderProducts
            ->pluck('seller_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        foreach ($sellerIds as $sellerId) {
            $cacheKey = "seller_payout_released:{$order->id}:{$sellerId}";
            if (! Cache::add($cacheKey, 1, now()->addDays(7))) {
                continue;
            }

            $vendor = Vendor::with('user')->find($sellerId);
            if (! $vendor || ! $vendor->user) {
                continue;
            }

            try {
                $vendor->user->notify(new SellerPayoutReleasedNotification($order, $vendor));
            } catch (\Throwable $e) {
                Log::warning('Seller payout notification failed', [
                    'order_id' => $order->id,
                    'seller_id' => $sellerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array{success: bool, message: string, results: array<int, array<string, mixed>>}
     */
    protected function failure(string $message, array $results = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'results' => $results,
        ];
    }
}
