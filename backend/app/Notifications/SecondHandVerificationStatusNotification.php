<?php

namespace App\Notifications;

use App\Models\SecondHandVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SecondHandVerificationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SecondHandVerification $verification,
        private readonly string $status,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        $approved = $this->status === SecondHandVerification::STATUS_APPROVED;

        return [
            'type' => 'second_hand_verification',
            'verification_id' => $this->verification->id,
            'status' => $this->status,
            'title' => $approved ? 'İkinci el doğrulama onaylandı' : 'İkinci el doğrulama reddedildi',
            'message' => $approved
                ? 'Hesabınız onaylandı. Artık ikinci el ilan ekleyebilirsiniz.'
                : ('Başvurunuz reddedildi.'.($this->verification->admin_note ? ' Gerekçe: '.$this->verification->admin_note : '')),
        ];
    }

    public function toFcm($notifiable): array
    {
        $approved = $this->status === SecondHandVerification::STATUS_APPROVED;
        $body = $approved
            ? 'Hesabınız onaylandı. Artık ikinci el ilan ekleyebilirsiniz.'
            : ('Başvurunuz reddedildi.'.($this->verification->admin_note ? ' Gerekçe: '.$this->verification->admin_note : ''));

        return [
            'title' => $approved ? 'İkinci el doğrulama onaylandı' : 'İkinci el doğrulama reddedildi',
            'body' => $body,
            'data' => [
                'type' => 'second_hand_verification',
                'verification_id' => (string) $this->verification->id,
                'status' => $this->status,
            ],
        ];
    }
}
