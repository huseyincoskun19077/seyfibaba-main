<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SellerSsoTicketService
{
    private const TTL_SECONDS = 90;

    public function issue(int $userId, ?string $next = null): string
    {
        $payload = json_encode([
            'user_id' => $userId,
            'next' => $next,
            'exp' => time() + self::TTL_SECONDS,
            'n' => bin2hex(random_bytes(8)),
        ], JSON_UNESCAPED_SLASHES);

        $encoded = $this->base64UrlEncode($payload);

        return $encoded.'.'.hash_hmac('sha256', $encoded, $this->secret());
    }

    /**
     * @return array{user_id: int, next: ?string}|null
     */
    public function consume(string $code): ?array
    {
        $code = trim($code);
        if ($code === '' || ! str_contains($code, '.')) {
            return $this->consumeLegacyCacheTicket($code);
        }

        [$encoded, $signature] = explode('.', $code, 2);
        if ($encoded === '' || $signature === '') {
            return null;
        }

        $expected = hash_hmac('sha256', $encoded, $this->secret());
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encoded), true);
        if (! is_array($payload) || empty($payload['user_id'])) {
            return null;
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        $usedKey = 'seller_sso_used:'.hash('sha256', $code);
        if (Cache::has($usedKey)) {
            return null;
        }
        Cache::put($usedKey, 1, self::TTL_SECONDS);

        return [
            'user_id' => (int) $payload['user_id'],
            'next' => isset($payload['next']) ? (string) $payload['next'] : null,
        ];
    }

    public function redirectUrl(int $userId, ?string $next = null): string
    {
        $code = $this->issue($userId, $next);

        return $this->sellerPanelOrigin().'/seller/sso?code='.rawurlencode($code);
    }

    private function sellerPanelOrigin(): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $frontend = rtrim((string) config('app.frontend_url', 'https://seyfibaba.com'), '/');
        $appHost = strtolower((string) (parse_url($appUrl, PHP_URL_HOST) ?: ''));
        $frontHost = strtolower((string) (parse_url($frontend, PHP_URL_HOST) ?: ''));
        $frontHost = preg_replace('/^www\./', '', (string) $frontHost) ?: $frontHost;
        $appHostBare = preg_replace('/^www\./', '', (string) $appHost) ?: $appHost;

        if ($appHost !== '' && $appHostBare !== $frontHost) {
            return $appUrl;
        }

        return 'https://admin.seyfibaba.com';
    }

    private function secret(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $key !== '' ? $key : 'seyfibaba-seller-sso';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : '';
    }

    /**
     * @return array{user_id: int, next: ?string}|null
     */
    private function consumeLegacyCacheTicket(string $code): ?array
    {
        if ($code === '') {
            return null;
        }

        $payload = Cache::pull('seller_sso_ticket:'.hash('sha256', $code));
        if (! is_array($payload) || empty($payload['user_id'])) {
            return null;
        }

        return [
            'user_id' => (int) $payload['user_id'],
            'next' => isset($payload['next']) ? (string) $payload['next'] : null,
        ];
    }
}
