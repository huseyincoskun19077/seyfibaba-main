<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    private ?array $credentials = null;

    public function isConfigured(): bool
    {
        $path = config('firebase.credentials');

        return is_string($path) && $path !== '' && is_readable($path);
    }

    public function sendToUser(?User $user, string $title, string $body, array $data = []): bool
    {
        return $this->sendToUserDetailed($user, $title, $body, $data)['sent'];
    }

    /**
     * @return array{sent: bool, reason: ?string}
     */
    public function sendToUserDetailed(?User $user, string $title, string $body, array $data = []): array
    {
        if (! $user || empty($user->fcm_token)) {
            Log::warning('FCM skipped: user has no device token.', [
                'user_id' => $user?->id,
            ]);

            return ['sent' => false, 'reason' => 'no_token'];
        }

        return $this->sendToTokenDetailed((string) $user->fcm_token, $title, $body, $data);
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        return $this->sendToTokenDetailed($token, $title, $body, $data)['sent'];
    }

    /**
     * @return array{sent: bool, reason: ?string}
     */
    public function sendToTokenDetailed(string $token, string $title, string $body, array $data = []): array
    {
        if (! $this->isConfigured()) {
            Log::warning('FCM skipped: credentials file not configured or not readable.', [
                'path' => config('firebase.credentials'),
            ]);

            return ['sent' => false, 'reason' => 'not_configured'];
        }

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return ['sent' => false, 'reason' => 'auth_failed'];
        }

        $projectId = (string) config('firebase.project_id', 'seyfibabapp');
        // FCM data must be a JSON object {}; empty PHP [] encodes as a list and returns 400.
        $dataPayload = (object) $this->stringifyData($data);
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $dataPayload,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'seyfibaba_default',
                        'sound' => 'default',
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    $payload
                );

            if ($response->successful()) {
                return ['sent' => true, 'reason' => null];
            }

            Log::warning('FCM send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM send exception: '.$e->getMessage());
        }

        return ['sent' => false, 'reason' => 'send_failed'];
    }

    private function stringifyData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalized[(string) $key] = is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $normalized;
    }

    private function getCredentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = config('firebase.credentials');
        $json = file_get_contents($path);
        $this->credentials = json_decode($json, true);

        if (! is_array($this->credentials)) {
            throw new \RuntimeException('Invalid Firebase credentials JSON.');
        }

        return $this->credentials;
    }

    private function getAccessToken(): ?string
    {
        try {
            $cached = Cache::get('fcm_oauth_access_token');
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        } catch (\Throwable $e) {
            Log::warning('FCM OAuth cache read failed: '.$e->getMessage());
        }

        try {
            $credentials = $this->getCredentials();
            $now = time();

            $jwt = JWT::encode([
                'iss' => $credentials['client_email'],
                'sub' => $credentials['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ], $credentials['private_key'], 'RS256');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                Log::warning('FCM OAuth token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $accessToken = $response->json('access_token');
            if (! is_string($accessToken) || $accessToken === '') {
                return null;
            }

            try {
                Cache::put('fcm_oauth_access_token', $accessToken, 3300);
            } catch (\Throwable $e) {
                Log::warning('FCM OAuth cache write failed: '.$e->getMessage());
            }

            return $accessToken;
        } catch (\Throwable $e) {
            Log::warning('FCM OAuth token exception: '.$e->getMessage());

            return null;
        }
    }
}
