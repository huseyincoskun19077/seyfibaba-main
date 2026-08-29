<?php

namespace App\Observers;

use App\Models\OrderProduct;
use App\Notifications\BuyerOrderStatusNotification;
use App\Services\SellerPushNotifier;

class OrderProductObserver
{
    public function created(OrderProduct $orderProduct): void
    {
        $order = $orderProduct->order()->first();
        if (! $order || ($order->is_draft ?? 'no') === 'yes') {
            return;
        }

        if (! $orderProduct->seller_id) {
            return;
        }

        app(SellerPushNotifier::class)->notifyNewOrderForSeller(
            $order,
            (int) $orderProduct->seller_id
        );
    }

    public function updated(OrderProduct $orderProduct): void
    {
        if (! $orderProduct->wasChanged('seller_status')) {
            return;
        }

        $order = $orderProduct->order()->with('user')->first();
        if (! $order || ! $order->user) {
            return;
        }

        $status = (int) $orderProduct->seller_status;
        $messages = [
            1 => ['Siparişiniz onaylandı', 'Satıcı siparişinizi hazırlamaya başladı.'],
            2 => ['Siparişiniz kargoya verildi', 'Paketiniz yola çıktı. Takip edebilirsiniz.'],
            3 => ['Sipariş teslim edildi', 'Siparişiniz teslim edildi.'],
            4 => ['Sipariş iptal edildi', 'Siparişiniz iptal edildi.'],
        ];

        if (! isset($messages[$status])) {
            return;
        }

        $order->user->notify(new BuyerOrderStatusNotification(
            $messages[$status][0],
            $messages[$status][1],
            (int) $order->id,
            (string) $order->order_id,
        ));
    }
}
