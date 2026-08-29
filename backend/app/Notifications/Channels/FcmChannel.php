<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);
        if (! is_array($payload)) {
            return;
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if ($title === '' || $body === '') {
            return;
        }

        $freshUser = $notifiable instanceof User ? $notifiable->fresh() : $notifiable;
        if ($freshUser instanceof User) {
            $notifiable = $freshUser;
        }

        $token = null;
        if (method_exists($notifiable, 'routeNotificationForFcm')) {
            $token = $notifiable->routeNotificationForFcm($notification);
        }
        if (! $token && isset($notifiable->fcm_token)) {
            $token = $notifiable->fcm_token;
        }

        $token = is_string($token) ? trim($token) : '';
        if ($token === '') {
            return;
        }

        $fcm = app(FcmPushService::class);
        if ($notifiable instanceof User) {
            $sent = $fcm->sendToUser($notifiable, $title, $body, $data);
            if (! $sent) {
                \Illuminate\Support\Facades\Log::warning('FCM channel delivery failed', [
                    'user_id' => $notifiable->id,
                    'notification' => $notification::class,
                ]);
            }

            return;
        }

        $fcm->sendToToken($token, $title, $body, $data);
    }
}
