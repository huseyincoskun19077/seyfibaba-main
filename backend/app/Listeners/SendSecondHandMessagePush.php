<?php

namespace App\Listeners;

use App\Events\SecondHandMessageSent;

/**
 * Bildirim controller'da gönderilir; bu listener yalnızca broadcast sonrası ek iş için tutulur.
 */
class SendSecondHandMessagePush
{
    public function handle(SecondHandMessageSent $event): void
    {
        // no-op: database + FCM SecondHandMessagingController::deliverSecondHandMessageNotice içinde
    }
}
