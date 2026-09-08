<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SecondHandNewMessageNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $payload,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        $listingTitle = (string) ($this->payload['listing_title'] ?? 'İlan');
        $body = (string) ($this->payload['body'] ?? 'Yeni mesajınız var.');
        if (mb_strlen($body) > 120) {
            $body = mb_substr($body, 0, 117).'...';
        }

        $sender = (string) ($this->payload['sender_display'] ?? 'Kullanıcı');

        return [
            'type' => 'second_hand_message',
            'conversation_id' => (int) ($this->payload['conversation_id'] ?? 0),
            'listing_id' => (int) ($this->payload['listing_id'] ?? 0),
            'title' => 'İkinci El: '.$listingTitle,
            'message' => $sender.': '.$body,
        ];
    }

    public function toFcm($notifiable): array
    {
        $data = $this->toArray($notifiable);

        return [
            'title' => $data['title'],
            'body' => $data['message'],
            'data' => [
                'type' => 'second_hand_message',
                'conversation_id' => (string) ($this->payload['conversation_id'] ?? ''),
                'listing_id' => (string) ($this->payload['listing_id'] ?? ''),
            ],
        ];
    }
}
