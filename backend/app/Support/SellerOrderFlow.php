<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Collection;

class SellerOrderFlow
{
    /**
     * Satıcının bu siparişteki ana seller_status (tek satıcı varsayımı; çoklu satırda en düşük aktif adım).
     */
    public static function sellerStatus(Collection $orderProducts): int
    {
        if ($orderProducts->isEmpty()) {
            return 0;
        }

        if ($orderProducts->contains(fn ($op) => (int) $op->seller_status === 4)) {
            return 4;
        }

        return (int) $orderProducts->min('seller_status');
    }

    /**
     * @return array{state: string, label: string, detail: string, badge: string}
     */
    public static function payoutInfo(Order $order, Collection $orderProducts): array
    {
        $sellerStatus = self::sellerStatus($orderProducts);
        $method = strtolower((string) $order->payment_method);
        $payoutStatus = (string) ($order->payout_status ?? 'pending');
        $blockReason = (string) ($order->payout_block_reason ?? '');
        $sellerId = $orderProducts->first()?->seller_id;
        $fullyReturned = $payoutStatus === 'cancelled'
            || $blockReason === \App\Services\SellerPayoutService::PAYOUT_BLOCK_FULL_RETURN
            || str_contains($blockReason, 'Ürünler iade edildi')
            || $order->isFullyRefundedForSeller($sellerId ? (int) $sellerId : null);

        if ($fullyReturned || ($sellerStatus === 4 && $order->isFullyRefundedForSeller($sellerId ? (int) $sellerId : null))) {
            return [
                'state' => 'returned',
                'label' => 'Ürünler iade edildi',
                'detail' => 'Bu siparişteki ürünler iade edildi. Hakediş ödemesi yapılmaz.',
                'badge' => 'secondary',
            ];
        }

        if ($sellerStatus === 4) {
            return [
                'state' => 'cancelled',
                'label' => 'Sipariş iptal',
                'detail' => 'Sipariş iptal edildi. Hakediş oluşmaz.',
                'badge' => 'danger',
            ];
        }

        if ($sellerStatus < 3) {
            return [
                'state' => 'waiting',
                'label' => 'Hakediş henüz başlamadı',
                'detail' => 'Müşteri siparişi teslim aldığında hakediş süreci başlar.',
                'badge' => 'secondary',
            ];
        }

        if ($order->payout_blocked_at) {
            return [
                'state' => 'blocked',
                'label' => 'Hakediş bekletiliyor',
                'detail' => $blockReason !== ''
                    ? $blockReason
                    : 'Bu siparişin ödemesi geçici olarak durduruldu.',
                'badge' => 'danger',
            ];
        }

        $paid = $order->payout_processed_at
            || in_array($payoutStatus, ['completed', 'paid'], true)
            || $orderProducts->contains(fn ($op) => ! empty($op->iyzico_approved_at)
                || (string) ($op->payout_status ?? '') === 'paid');

        if ($paid) {
            $processedAt = $order->payout_processed_at
                ? $order->payout_processed_at->format('d.m.Y H:i')
                : null;

            return [
                'state' => 'paid',
                'label' => 'Hakediş ödemesi yapıldı',
                'detail' => $processedAt
                    ? "Ödeme işlendi: {$processedAt}"
                    : 'Satıcı hesabınıza aktarım tamamlandı.',
                'badge' => 'success',
            ];
        }

        if ($method === 'bankpayment') {
            return [
                'state' => 'pending',
                'label' => 'Hakediş bekleniyor',
                'detail' => 'Havale siparişlerinde tutar, admin onayı ve bekleme süresi sonrası çekim talebi ile ödenir. Henüz kazanç sayılmaz.',
                'badge' => 'warning',
            ];
        }

        $eligible = $order->payout_eligible_at
            ? $order->payout_eligible_at->format('d.m.Y H:i')
            : null;

        return [
            'state' => 'pending',
            'label' => 'Hakediş bekleniyor',
            'detail' => $eligible
                ? "Tahmini aktarım: {$eligible}. İyzico onayı olmadan kazanç sayılmaz."
                : 'Sipariş tamamlandı. İyzico/admin onayı sonrası hesabınıza aktarılır; onay öncesi kazanç değildir.',
            'badge' => 'info',
        ];
    }

    /**
     * @return list<array{key: string, title: string, description: string, state: string}>
     */
    public static function steps(int $sellerStatus, string $payoutState = 'waiting'): array
    {
        $defs = [
            ['key' => 'received', 'title' => 'Yeni sipariş', 'description' => 'Ödeme alındı, hazırlık bekleniyor.'],
            ['key' => 'preparing', 'title' => 'Hazırlık onayı', 'description' => 'Ürünü paketleyip kargoya vereceğinizi onaylayın.'],
            ['key' => 'shipped', 'title' => 'Kargoya verildi', 'description' => 'Kargo firması ve takip numarasını girin.'],
            ['key' => 'completed', 'title' => 'Teslim alındı', 'description' => 'Müşteri teslim aldığında sipariş tamamlanır.'],
            ['key' => 'payout', 'title' => 'Hakediş ödemesi', 'description' => 'Tamamlanan siparişin ödemesi hesabınıza aktarılır.'],
        ];

        if ($sellerStatus === 4) {
            return array_map(fn ($step) => array_merge($step, ['state' => 'cancelled']), $defs);
        }

        $progressIndex = match (true) {
            $sellerStatus <= 0 => 0,
            $sellerStatus === 1 => 1,
            $sellerStatus === 2 => 2,
            default => 3,
        };

        return array_map(function (array $step, int $index) use ($progressIndex, $sellerStatus, $payoutState) {
            if ($step['key'] === 'payout') {
                if ($payoutState === 'returned' || $payoutState === 'cancelled') {
                    return array_merge($step, [
                        'state' => 'cancelled',
                        'title' => $payoutState === 'returned' ? 'Ürünler iade edildi' : 'Sipariş iptal',
                        'description' => $payoutState === 'returned'
                            ? 'İade tamamlandı, hakediş yok.'
                            : 'Sipariş iptal, hakediş yok.',
                    ]);
                }
                if ($sellerStatus < 3) {
                    return array_merge($step, ['state' => 'upcoming']);
                }
                if ($payoutState === 'paid') {
                    return array_merge($step, ['state' => 'done']);
                }

                return array_merge($step, ['state' => 'current']);
            }

            if ($sellerStatus >= 3 && $index <= 3) {
                return array_merge($step, ['state' => 'done']);
            }

            if ($index < $progressIndex) {
                return array_merge($step, ['state' => 'done']);
            }
            if ($index === $progressIndex) {
                return array_merge($step, ['state' => 'current']);
            }

            return array_merge($step, ['state' => 'upcoming']);
        }, $defs, array_keys($defs));
    }
}
