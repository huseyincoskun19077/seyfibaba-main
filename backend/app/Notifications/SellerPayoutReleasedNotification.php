<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SellerPayoutReleasedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Order $order,
        private Vendor $vendor,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        $orderNumber = (string) ($this->order->order_id ?? $this->order->id);

        return [
            'type' => 'seller_payout_released',
            'order_id' => $this->order->id,
            'order_number' => $orderNumber,
            'seller_id' => $this->vendor->id,
            'message' => "Sipariş #{$orderNumber} hakedişiniz onaylandı. Paranız Iyzico hesabınıza yatırılacaktır; bankaya geçiş Iyzico takvimine göredir.",
        ];
    }

    public function toFcm($notifiable): array
    {
        $orderNumber = (string) ($this->order->order_id ?? $this->order->id);

        return [
            'title' => 'Hakediş onaylandı',
            'body' => "Sipariş #{$orderNumber} hakedişiniz onaylandı. Paranız Iyzico hesabınıza yatırılacaktır.",
            'data' => [
                'type' => 'seller_payout_released',
                'order_id' => (string) $this->order->id,
                'order_number' => $orderNumber,
                'seller_id' => (string) $this->vendor->id,
            ],
        ];
    }
}
