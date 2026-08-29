<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BuyerOrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $body,
        private int $orderId,
        private string $orderNumber,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'order',
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'title' => $this->title,
            'message' => $this->body,
        ];
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => [
                'type' => 'order',
                'order_id' => (string) $this->orderId,
                'order_number' => $this->orderNumber,
            ],
        ];
    }
}
