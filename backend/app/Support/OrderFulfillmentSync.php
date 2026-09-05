<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderProduct;

/**
 * Sipariş geneli order_status'u ürün satırlarından (seller_status) senkronlar.
 * Alıcı arayüzü hem satır hem order_status üzerinden "Kargoda" görebilir.
 */
class OrderFulfillmentSync
{
    public static function sync(Order $order): void
    {
        $lines = OrderProduct::query()
            ->where('order_id', $order->id)
            ->get(['id', 'seller_status', 'shipped_at', 'delivered_at', 'customer_confirmed_at', 'auto_confirmed_at']);

        if ($lines->isEmpty()) {
            return;
        }

        $allConfirmed = $lines->every(function (OrderProduct $line) {
            return ! empty($line->customer_confirmed_at)
                || ! empty($line->auto_confirmed_at)
                || (int) $line->seller_status >= 3;
        });

        $anyShipped = $lines->contains(function (OrderProduct $line) {
            return (int) $line->seller_status >= 2
                || ! empty($line->shipped_at)
                || ! empty($line->delivered_at);
        });

        $anyPreparing = $lines->contains(fn (OrderProduct $line) => (int) $line->seller_status >= 1);

        $current = (int) $order->order_status;
        if ($current === 4) {
            return;
        }

        if ($allConfirmed) {
            if ($current < 3) {
                $order->order_status = 3;
                $order->order_completed_date = $order->order_completed_date ?: date('Y-m-d');
                $order->save();
            }
            return;
        }

        if ($anyShipped) {
            if ($current < 2) {
                $order->order_status = 2;
                $order->order_delivered_date = $order->order_delivered_date ?: date('Y-m-d');
                $order->save();
            }
            return;
        }

        if ($anyPreparing && $current < 1) {
            $order->order_status = 1;
            $order->order_approval_date = $order->order_approval_date ?: date('Y-m-d');
            $order->save();
        }
    }
}
