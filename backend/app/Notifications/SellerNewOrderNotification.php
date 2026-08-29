<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SellerNewOrderNotification extends Notification
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
            'type' => 'seller_new_order',
            'order_id' => $this->order->id,
            'order_number' => $orderNumber,
            'seller_id' => $this->vendor->id,
            'message' => "Yeni sipariş: #{$orderNumber}",
        ];
    }

    public function toFcm($notifiable): array
    {
        $orderNumber = (string) ($this->order->order_id ?? $this->order->id);
        $amount = $this->order->total_amount ?? null;
        $body = "Yeni siparişiniz var (#{$orderNumber}).";
        if ($amount !== null && $amount !== '') {
            $body = "Yeni sipariş: #{$orderNumber} · ".number_format((float) $amount, 2, ',', '.').' ₺';
        }

        return [
            'title' => 'Yeni sipariş',
            'body' => $body,
            'data' => [
                'type' => 'seller_new_order',
                'order_id' => (string) $this->order->id,
                'order_number' => $orderNumber,
                'seller_id' => (string) $this->vendor->id,
            ],
        ];
    }
}
