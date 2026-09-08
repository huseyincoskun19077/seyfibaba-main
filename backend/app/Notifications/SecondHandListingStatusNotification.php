<?php

namespace App\Notifications;

use App\Models\SecondHandListing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SecondHandListingStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SecondHandListing $listing,
        private readonly string $status,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        $title = match ($this->status) {
            SecondHandListing::STATUS_ACTIVE => 'İkinci el ilanınız yayınlandı',
            SecondHandListing::STATUS_REJECTED => 'İkinci el ilanınız reddedildi',
            default => 'İkinci el ilan durumu güncellendi',
        };

        $message = match ($this->status) {
            SecondHandListing::STATUS_ACTIVE => '"'.$this->listing->title.'" ilanınız yayında.',
            SecondHandListing::STATUS_REJECTED => '"'.$this->listing->title.'" ilanınız reddedildi.'
                .($this->listing->review_note ? ' Gerekçe: '.$this->listing->review_note : ''),
            default => '"'.$this->listing->title.'" ilan durumu: '.$this->status,
        };

        return [
            'type' => 'second_hand_listing',
            'listing_id' => $this->listing->id,
            'status' => $this->status,
            'title' => $title,
            'message' => $message,
        ];
    }

    public function toFcm($notifiable): array
    {
        $data = $this->toArray($notifiable);

        return [
            'title' => $data['title'],
            'body' => $data['message'],
            'data' => [
                'type' => 'second_hand_listing',
                'listing_id' => (string) $this->listing->id,
                'status' => $this->status,
            ],
        ];
    }
}
