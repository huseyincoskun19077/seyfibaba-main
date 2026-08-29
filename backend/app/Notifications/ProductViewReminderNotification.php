<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductViewReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private Product $product) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'product_view_reminder',
            'product_id' => $this->product->id,
            'product_slug' => (string) ($this->product->slug ?? ''),
            'product_name' => (string) $this->product->name,
            'title' => 'Ilginizi ceken urun',
            'message' => "\"{$this->product->name}\" urununu tekrar incelemek ister misiniz?",
        ];
    }

    public function toFcm($notifiable): array
    {
        $payload = $this->toArray($notifiable);

        return [
            'title' => $payload['title'],
            'body' => $payload['message'],
            'data' => [
                'type' => 'product_view_reminder',
                'product_id' => (string) $this->product->id,
                'product_slug' => (string) ($this->product->slug ?? ''),
            ],
        ];
    }
}
