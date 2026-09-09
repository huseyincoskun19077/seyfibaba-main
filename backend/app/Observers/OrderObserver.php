<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\BuyerOrderStatusNotification;
use App\Services\SellerPushNotifier;
use App\Services\Sentos\SentosOrderSyncService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function updated(Order $order): void
    {
        $this->notifyBuyerOrderStatus($order);
        $this->notifySellersWhenOrderConfirmed($order);
        $this->pushSentosWhenOrderPaid($order);
    }

    private function pushSentosWhenOrderPaid(Order $order): void
    {
        if (! config('features.sentos_enabled', true)) {
            return;
        }

        $draftCleared = $order->wasChanged('is_draft') && ($order->is_draft ?? 'no') === 'no';
        $paymentConfirmed = $order->wasChanged('payment_status')
            && (int) $order->payment_status === 1
            && ($order->is_draft ?? 'no') !== 'yes';

        if (! $draftCleared && ! $paymentConfirmed) {
            return;
        }

        try {
            // Never break checkout/payment if Sentos is down.
            app(SentosOrderSyncService::class)->pushPaidOrder($order);
        } catch (\Throwable $e) {
            Log::warning('Sentos order observer push failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
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
