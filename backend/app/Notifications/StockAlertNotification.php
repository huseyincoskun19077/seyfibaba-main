<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Product $product,
        private int $threshold,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        $initialQty = (int) ($this->product->initial_qty ?? $this->product->qty ?? 0);

        return [
            'type' => 'stock_alert',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_slug' => $this->product->slug,
            'current_stock' => $this->product->qty,
            'initial_qty' => $initialQty,
            'threshold' => $this->threshold,
            'message' => "\"{$this->product->name}\" stoğunda son {$this->product->qty} adet kaldı (başlangıç: {$initialQty}).",
        ];
    }

    public function toFcm($notifiable): array
    {
        $initialQty = (int) ($this->product->initial_qty ?? $this->product->qty ?? 0);

        return [
            'title' => 'Stok azaldı',
            'body' => "\"{$this->product->name}\" stoğunda son {$this->product->qty} adet kaldı.",
            'data' => [
                'type' => 'stock_alert',
                'product_id' => (string) $this->product->id,
                'product_slug' => (string) ($this->product->slug ?? ''),
                'current_stock' => (string) $this->product->qty,
                'initial_qty' => (string) $initialQty,
                'threshold' => (string) $this->threshold,
            ],
        ];
    }
}
