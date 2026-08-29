<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CampaignNotification extends Notification
{
    use Queueable;

    public function __construct(private Campaign $campaign) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        $title = (string) ($this->campaign->title ?: $this->campaign->name);
        $offer = (float) ($this->campaign->offer ?? 0);

        return [
            'type' => 'campaign',
            'campaign_id' => $this->campaign->id,
            'campaign_slug' => (string) ($this->campaign->slug ?? $this->campaign->id),
            'title' => $title,
            'message' => "Yeni kampanya: {$title} — %{$offer} indirim firsati!",
        ];
    }

    public function toFcm($notifiable): array
    {
        $payload = $this->toArray($notifiable);

        return [
            'title' => 'Kampanya basladi',
            'body' => $payload['message'],
            'data' => [
                'type' => 'campaign',
                'campaign_id' => (string) $this->campaign->id,
                'campaign_slug' => (string) ($this->campaign->slug ?? $this->campaign->id),
            ],
        ];
    }
}
