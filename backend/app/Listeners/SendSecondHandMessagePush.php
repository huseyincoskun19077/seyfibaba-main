<?php

namespace App\Listeners;

use App\Events\SecondHandMessageSent;
use App\Services\FcmPushService;

class SendSecondHandMessagePush
{
    public function handle(SecondHandMessageSent $event): void
    {
        $data = $event->data;
        $listingTitle = (string) ($data['listing_title'] ?? 'İlan');
        $body = (string) ($data['body'] ?? 'Yeni mesajınız var.');

        if (mb_strlen($body) > 120) {
            $body = mb_substr($body, 0, 117).'...';
        }

        app(FcmPushService::class)->sendToUser(
            $event->user,
            'İkinci El: '.$listingTitle,
            $body,
            [
                'type' => 'second_hand_message',
                'conversation_id' => (string) ($data['conversation_id'] ?? ''),
                'listing_id' => (string) ($data['listing_id'] ?? ''),
            ]
        );
    }
}
