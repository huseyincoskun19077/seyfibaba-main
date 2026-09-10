<?php

namespace App\Services\Softtr;

use App\Models\VendorSofttrSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Softtr HTTP client (Basic Auth).
 * Docs: https://api.softtr.net/docbeta/ — base URL is https://www.{domain}/api
 * Product catalog is PULLED via GET /products/list (updateStokAndPrice writes INTO Softtr — not used here).
 */
class SofttrApiClient
{
    public function normalizeBaseUrl(string $input): string
    {
        $value = trim($input);
        $value = rtrim($value, '/');

        if ($value === '') {
            return '';
        }

        if (! Str::startsWith($value, ['http://', 'https://'])) {
            $host = preg_replace('#^www\.#i', '', $value);
            $host = preg_replace('#[^a-zA-Z0-9\-.]#', '', (string) $host);
            $value = 'https://www.' . $host;
        }

        $value = preg_replace('#/api$#i', '', $value) ?? $value;
        $value = rtrim($value, '/');

        return $value . '/api';
    }

    /**
     * @return array{ok: bool, message: string, status: int|null, product_hint: string|null}
     */
    public function testConnection(VendorSofttrSetting $setting): array
    {
        if (! $setting->hasCredentials()) {
            return [
                'ok' => false,
                'message' => 'Softtr API bilgileri eksik.',
                'status' => null,
                'product_hint' => null,
            ];
        }

        try {
            $response = $this->request($setting, 'GET', '/products/list', [
                'page' => 1,
                'pageSize' => 1,
            ]);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Bağlantı hatası: ' . $e->getMessage(),
                'status' => null,
                'product_hint' => null,
            ];
        }

        $status = $response->status();

        if ($status === 401 || $status === 403) {
            return [
                'ok' => false,
                'message' => 'Kimlik doğrulama başarısız (API kullanıcı/şifre kontrol edin).',
                'status' => $status,
                'product_hint' => null,
            ];
        }

        if ($status >= 200 && $status < 300) {
            $items = app(SofttrProductNormalizer::class)->extractListItems($response->json());
            $hint = null;
            if ($items !== []) {
                $first = $items[0];
                $name = (string) ($first['title'] ?? $first['title_tr'] ?? $first['name'] ?? $first['itemTitle'] ?? '');
                $hint = $name !== '' ? ('Örnek ürün: ' . mb_substr($name, 0, 80)) : ('Ürün listesi OK (' . count($items) . '+)');
            }

            return [
                'ok' => true,
                'message' => 'Softtr bağlantısı başarılı.',
                'status' => $status,
                'product_hint' => $hint,
            ];
        }

        return [
            'ok' => false,
            'message' => 'Softtr yanıtı beklenmeyen: HTTP ' . $status,
            'status' => $status,
            'product_hint' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAllProducts(
        VendorSofttrSetting $setting,
        int $pageSize = 50,
        int $maxPages = 100,
        int $pageDelayMs = 0
    ): array {
        $normalizer = app(SofttrProductNormalizer::class);
        $all = [];
        $page = 1;

        while ($page <= $maxPages) {
            $response = $this->request($setting, 'GET', '/products/list', [
                'page' => $page,
                'pageSize' => $pageSize,
                'size' => $pageSize,
            ], null, 60);

            if ($response->status() === 401 || $response->status() === 403) {
                throw new \RuntimeException('Kimlik doğrulama başarısız (API kullanıcı/şifre).');
            }

            if (! $response->successful()) {
                throw new \RuntimeException('Ürün listesi HTTP ' . $response->status());
            }

            $items = $normalizer->extractListItems($response->json());
            if ($items === []) {
                break;
            }

            foreach ($items as $item) {
                $all[] = $item;
            }

            if (count($items) < $pageSize) {
                break;
            }

            $page++;
            if ($pageDelayMs > 0 && $page <= $maxPages) {
                usleep($pageDelayMs * 1000);
            }
        }

        return $all;
    }

    /**
     * Shop origin without /api — used to absolutize relative Softtr image paths.
     */
    public function shopOrigin(VendorSofttrSetting $setting): string
    {
        $base = rtrim($this->normalizeBaseUrl($setting->api_base_url), '/');

        return (string) preg_replace('#/api$#i', '', $base);
    }

    public function request(
        VendorSofttrSetting $setting,
        string $method,
        string $path,
        array $query = [],
        ?array $body = null,
        int $timeout = 20
    ): Response {
        $base = rtrim($this->normalizeBaseUrl($setting->api_base_url), '/');
        $path = '/' . ltrim($path, '/');
        $url = $base . $path;

        $pending = Http::withBasicAuth(
            (string) $setting->api_user,
            (string) $setting->api_password
        )
            ->acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->connectTimeout(10);

        $method = strtoupper($method);

        return match ($method) {
            'GET' => $pending->get($url, $query),
            'POST' => $pending->post($url, $body ?? []),
            'PUT' => $pending->put($url, $body ?? []),
            default => throw new \InvalidArgumentException('Unsupported Softtr HTTP method: ' . $method),
        };
    }
}
