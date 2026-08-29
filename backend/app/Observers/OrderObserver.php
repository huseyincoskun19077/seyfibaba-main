<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\BuyerOrderStatusNotification;
use App\Services\SellerPushNotifier;

class OrderObserver
{
    public function updated(Order $order): void
    {
        $this->notifyBuyerOrderStatus($order);
        $this->notifySellersWhenOrderConfirmed($order);
    }

    private function notifyBuyerOrderStatus(Order $order): void
    {
        if (! $order->wasChanged('order_status')) {
            return;
        }

        $order->loadMissing('user');
        if (! $order->user) {
            return;
        }

        $status = (int) $order->order_status;
        $messages = [
            3 => ['Sipariş teslim edildi', 'Siparişiniz başarıyla teslim edildi.'],
            4 => ['Sipariş reddedildi', 'Siparişiniz reddedildi veya iptal edildi.'],
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

    private function notifySellersWhenOrderConfirmed(Order $order): void
    {
        $draftCleared = $order->wasChanged('is_draft') && ($order->is_draft ?? 'no') === 'no';
        $paymentConfirmed = $order->wasChanged('payment_status')
            && (int) $order->payment_status === 1
            && ($order->is_draft ?? 'no') !== 'yes';

        if (! $draftCleared && ! $paymentConfirmed) {
            return;
        }

        app(SellerPushNotifier::class)->notifySellersForOrder($order);
    }
}
