<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class PushBroadcastService
{
    public function __construct(
        private FcmPushService $fcm,
    ) {}

    /**
     * @return array{inbox: int, push: int, push_reason: ?string}
     */
    public function sendToUsers(Collection $users, Notification $notification): array
    {
        $inbox = 0;
        $push = 0;
        $pushReason = null;

        $users->unique('id')->chunk(500)->each(function (Collection $chunk) use ($notification, &$inbox, &$push, &$pushReason) {
            foreach ($chunk as $user) {
                $result = $this->deliver($user, $notification);
                $inbox += $result['inbox'];
                $push += $result['push'];

                if ($result['push'] === 0 && $result['push_reason'] !== null) {
                    $pushReason = $result['push_reason'];
                }
            }
        });

        return ['inbox' => $inbox, 'push' => $push, 'push_reason' => $pushReason];
    }

    /**
     * @return array{inbox: int, push: int}
     */
    public function sendToAllBuyers(Notification $notification): array
    {
        return $this->sendToUsers(User::query()->get(), $notification);
    }

    /**
     * @return array{inbox: int, push: int}
     */
    public function sendToAllSellers(Notification $notification): array
    {
        return $this->sendToUsers(
            User::query()->where('is_vendor', 1)->get(),
            $notification
        );
    }

    /**
     * @return array{inbox: int, push: int}
     */
    public function sendToUserIds(array $userIds, Notification $notification): array
    {
        if ($userIds === []) {
            return ['inbox' => 0, 'push' => 0, 'push_reason' => null];
        }

        return $this->sendToUsers(
            User::query()->whereIn('id', $userIds)->get(),
            $notification
        );
    }

    /**
     * @return array{inbox: int, push: int}
     */
    public function sendToUserByEmail(string $email, Notification $notification): array
    {
        $email = trim($email);
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return ['inbox' => 0, 'push' => 0, 'push_reason' => 'user_not_found'];
        }

        return $this->deliver($user, $notification);
    }

    /**
     * @return array{inbox: int, push: int, push_reason: ?string}
     */
    private function deliver(User $user, Notification $notification): array
    {
        $user->notify($notification);

        $pushResult = $this->sendPush($user, $notification);

        return [
            'inbox' => 1,
            'push' => $pushResult['sent'] ? 1 : 0,
            'push_reason' => $pushResult['reason'],
        ];
    }

    /**
     * @return array{sent: bool, reason: ?string}
     */
    private function sendPush(User $user, Notification $notification): array
    {
        if (! method_exists($notification, 'toFcm')) {
            return ['sent' => false, 'reason' => 'no_fcm_payload'];
        }

        $freshUser = $user->fresh();
        if (! $freshUser) {
            return ['sent' => false, 'reason' => 'user_not_found'];
        }

        $payload = $notification->toFcm($freshUser);
        if (! is_array($payload)) {
            return ['sent' => false, 'reason' => 'no_fcm_payload'];
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if ($title === '' || $body === '') {
            return ['sent' => false, 'reason' => 'empty_payload'];
        }

        return $this->fcm->sendToUserDetailed($freshUser, $title, $body, $data);
    }
}
