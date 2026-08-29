<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KycReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private Vendor $vendor) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'kyc_reminder',
            'seller_id' => $this->vendor->id,
            'shop_name' => $this->vendor->shop_name,
            'status' => 'not_submitted',
            'message' => 'KYC doğrulamanızı tamamlamadınız. Ürün eklemek için doğrulama gerekli.',
        ];
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => 'KYC doğrulama gerekli',
            'body' => 'Hesap doğrulamanızı tamamlamadınız. Ürün eklemek için KYC yapın.',
            'data' => [
                'type' => 'kyc_reminder',
                'seller_id' => (string) $this->vendor->id,
                'status' => 'not_submitted',
            ],
        ];
    }
}
