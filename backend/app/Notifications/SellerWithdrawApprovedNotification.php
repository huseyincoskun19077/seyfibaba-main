<?php

namespace App\Notifications;

use App\Models\SellerWithdraw;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SellerWithdrawApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private SellerWithdraw $withdraw) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        $amount = number_format((float) $this->withdraw->withdraw_amount, 2, ',', '.');

        return [
            'type' => 'seller_withdraw_approved',
            'withdraw_id' => $this->withdraw->id,
            'amount' => (float) $this->withdraw->withdraw_amount,
            'method' => (string) $this->withdraw->method,
            'message' => "Paranız transfer edilmiştir: {$amount} ₺",
        ];
    }

    public function toFcm($notifiable): array
    {
        $amount = number_format((float) $this->withdraw->withdraw_amount, 2, ',', '.');
        $method = (string) ($this->withdraw->method ?: 'EFT/Havale');

        return [
            'title' => 'Para transferi tamamlandı',
            'body' => "Paranız transfer edilmiştir. Tutar: {$amount} ₺ ({$method})",
            'data' => [
                'type' => 'seller_withdraw_approved',
                'withdraw_id' => (string) $this->withdraw->id,
                'amount' => (string) $this->withdraw->withdraw_amount,
            ],
        ];
    }
}
