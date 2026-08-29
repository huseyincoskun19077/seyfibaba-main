<?php

namespace App\Notifications;

use App\Models\Coupon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DiscountNotification extends Notification
{
    use Queueable;

    public function __construct(private Coupon $coupon) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        $discountLabel = $this->coupon->offer_type == 1
            ? '%'.$this->coupon->discount
            : number_format((float) $this->coupon->discount, 2, ',', '.').' TL';

        return [
            'type' => 'discount',
            'coupon_id' => $this->coupon->id,
            'coupon_code' => (string) $this->coupon->code,
            'title' => (string) $this->coupon->name,
            'message' => "Indirim kuponu: {$this->coupon->code} ({$discountLabel})",
        ];
    }

    public function toFcm($notifiable): array
    {
        $payload = $this->toArray($notifiable);

        return [
            'title' => 'Yeni indirim kuponu',
            'body' => $payload['message'],
            'data' => [
                'type' => 'discount',
                'coupon_id' => (string) $this->coupon->id,
                'coupon_code' => (string) $this->coupon->code,
            ],
        ];
    }
}
