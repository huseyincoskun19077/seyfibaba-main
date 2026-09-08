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
        $listingTitle = trim((string) ($this->payload['listing_title'] ?? 'İlan'));
        if ($listingTitle === '') {
            $listingTitle = 'İlan';
        }

        $body = trim((string) ($this->payload['body'] ?? ''));
        if ($body === '') {
            $body = 'Yeni mesajınız var.';
        }
        if (mb_strlen($body) > 120) {
            $body = mb_substr($body, 0, 117).'...';
        }

        $sender = trim((string) ($this->payload['sender_display'] ?? 'Kullanıcı'));
        if ($sender === '') {
            $sender = 'Kullanıcı';
        }

        return [
            'type' => 'second_hand_message',
            'conversation_id' => (int) ($this->payload['conversation_id'] ?? 0),
            'listing_id' => (int) ($this->payload['listing_id'] ?? 0),
            'listing_title' => $listingTitle,
            'title' => 'İkinci El mesaj',
            'message' => $listingTitle.' — '.$sender.': '.$body,
            'subject' => 'İkinci El: '.$listingTitle,
        ];
    }

    public function toFcm($notifiable): array
    {
        $data = $this->toArray($notifiable);

        return [
            'title' => 'İkinci El: '.$data['listing_title'],
            'body' => $data['message'],
            'data' => [
                'type' => 'second_hand_message',
                'conversation_id' => (string) ($this->payload['conversation_id'] ?? ''),
                'listing_id' => (string) ($this->payload['listing_id'] ?? ''),
                'listing_title' => (string) $data['listing_title'],
            ],
        ];
    }
}
