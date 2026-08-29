<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $body,
        private array $extra = [],
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return array_merge([
            'type' => 'admin_broadcast',
            'title' => $this->title,
            'message' => $this->body,
        ], $this->extra);
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => array_merge([
                'type' => 'admin_broadcast',
            ], $this->stringifyExtra($this->extra)),
        ];
    }

    private function stringifyExtra(array $extra): array
    {
        $normalized = [];

        foreach ($extra as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalized[(string) $key] = is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $normalized;
    }
}
