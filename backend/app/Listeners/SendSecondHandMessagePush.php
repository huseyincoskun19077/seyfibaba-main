<?php

namespace App\Listeners;

use App\Events\SecondHandMessageSent;
use App\Notifications\SecondHandNewMessageNotification;

class SendSecondHandMessagePush
{
    public function handle(SecondHandMessageSent $event): void
    {
        try {
            $event->user->notify(new SecondHandNewMessageNotification($event->data));
        } catch (\Throwable $e) {
            \Log::warning('Second hand message notification failed', [
                'user_id' => $event->user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
