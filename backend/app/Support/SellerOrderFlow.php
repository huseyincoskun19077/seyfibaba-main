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
                'detail' => (string) ($order->payout_block_reason ?: 'Bu siparişin ödemesi geçici olarak durduruldu.'),
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
                'label' => 'Hakediş çekilebilir değil',
                'detail' => 'Havale siparişlerinde tutar çekim talebi ile ödenir. Bekleme süresi dolunca çekim yapabilirsiniz.',
                'badge' => 'warning',
            ];
        }

        $eligible = $order->payout_eligible_at
            ? $order->payout_eligible_at->format('d.m.Y H:i')
            : null;

        return [
            'state' => 'pending',
            'label' => 'Hakediş ödemesi bekleniyor',
            'detail' => $eligible
                ? "Tahmini aktarım: {$eligible} (bekleme süresi sonrası otomatik işlenir)."
                : 'Sipariş tamamlandı. Ödeme bekleme süresi sonunda hesabınıza aktarılır.',
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
