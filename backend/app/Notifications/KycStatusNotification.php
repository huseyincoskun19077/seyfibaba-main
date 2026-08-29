<?php

namespace App\Notifications;

use App\Mail\KycStatusMail;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KycStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Vendor $vendor,
        private readonly string $status,
        private readonly ?string $reason = null,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail($notifiable): KycStatusMail
    {
        return (new KycStatusMail($this->vendor, $this->status, $this->reason))
            ->to($notifiable->email);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'kyc_status',
            'seller_id' => $this->vendor->id,
            'shop_name' => $this->vendor->shop_name,
            'status' => $this->status,
            'reason' => $this->reason,
            'message' => $this->status === 'approved'
                ? 'Hesap doğrulamanız (KYC) onaylandı. Ürün ekleyebilirsiniz.'
                : ('Hesap doğrulamanız reddedildi.'.($this->reason ? ' Gerekçe: '.$this->reason : '')),
        ];
    }

    public function toFcm($notifiable): array
    {
        if ($this->status === 'approved') {
            return [
                'title' => 'KYC onaylandı',
                'body' => 'Hesap doğrulamanız tamamlandı. Ürün ekleyebilirsiniz.',
                'data' => [
                    'type' => 'kyc_status',
                    'seller_id' => (string) $this->vendor->id,
                    'status' => 'approved',
                ],
            ];
        }

        $body = 'Hesap doğrulamanız reddedildi.';
        if ($this->reason) {
            $body .= ' Gerekçe: '.$this->reason;
        }

        return [
            'title' => 'KYC reddedildi',
            'body' => $body,
            'data' => [
                'type' => 'kyc_status',
                'seller_id' => (string) $this->vendor->id,
                'status' => (string) $this->status,
            ],
        ];
    }
}
