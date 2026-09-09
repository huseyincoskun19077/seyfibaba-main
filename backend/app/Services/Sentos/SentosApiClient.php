<?php

namespace App\Services\Sentos;

use App\Models\VendorSentosSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Isolated Sentos HTTP client.
 * Phase 1: connection test. Phase 2: product listing for vendor sync only.
 */
class SentosApiClient
{
    public function normalizeBaseUrl(string $input): string
    {
        $value = trim($input);
        $value = rtrim($value, '/');

        if ($value === '') {
            return '';
        }

        if (! Str::startsWith($value, ['http://', 'https://'])) {
            $host = preg_replace('#\.sentos\.com\.tr.*$#i', '', $value);
            $host = preg_replace('#[^a-zA-Z0-9\-.]#', '', (string) $host);
            $value = 'https://' . $host . '.sentos.com.tr';
        }

        if (! Str::endsWith($value, '/api')) {
            $value .= '/api';
        }

        return $value;
    }

    /**
     * @return array{ok: bool, message: string, status: int|null, product_hint: string|null}
     */
    public function testConnection(VendorSentosSetting $setting): array
    {
        if (! $setting->hasCredentials()) {
            return [
                'ok' => false,
                'message' => 'Sentos API bilgileri eksik.',
                'status' => null,
                'product_hint' => null,
            ];
        }

        try {
            $response = $this->request($setting, 'GET', '/products', [
                'page' => 1,
                'size' => 1,
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
                'message' => 'Kimlik doğrulama başarısız (API key/secret kontrol edin).',
                'status' => $status,
                'product_hint' => null,
            ];
        }

        if ($status >= 200 && $status < 300) {
            $hint = $this->extractProductHint($response->json());

            return [
                'ok' => true,
                'message' => 'Sentos bağlantısı başarılı.',
                'status' => $status,
                'product_hint' => $hint,
            ];
        }

        return [
            'ok' => false,
            'message' => 'Sentos yanıtı beklenmeyen: HTTP ' . $status,
            'status' => $status,
            'product_hint' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAllProducts(VendorSentosSetting $setting, int $pageSize = 50, int $maxPages = 100): array
    {
        $normalizer = app(SentosProductNormalizer::class);
        $all = [];
        $page = 1;

        while ($page <= $maxPages) {
            $response = $this->request($setting, 'GET', '/products', [
                'page' => $page,
                'size' => $pageSize,
                'pageSize' => $pageSize,
                'per_page' => $pageSize,
            ], null, 60);

            if ($response->status() === 401 || $response->status() === 403) {
                throw new \RuntimeException('Kimlik doğrulama başarısız (API key/secret).');
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
        }

        return $all;
    }

    public function request(
        VendorSentosSetting $setting,
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
            (string) $setting->api_key,
            (string) $setting->api_secret
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
            'DELETE' => $pending->delete($url, $body ?? []),
            default => throw new \InvalidArgumentException('Unsupported Sentos HTTP method: ' . $method),
        };
    }

    private function extractProductHint(mixed $json): ?string
    {
        $normalizer = app(SentosProductNormalizer::class);
        $items = $normalizer->extractListItems($json);
        if ($items !== []) {
            return 'Ürün endpoint erişilebilir (örnek sayfa kayıt: ' . count($items) . ').';
        }

        if (is_array($json) && (isset($json['total']) || isset($json['totalElements']) || isset($json['count']))) {
            $total = $json['total'] ?? $json['totalElements'] ?? $json['count'];

            return 'Ürün endpoint erişilebilir (toplam: ' . $total . ').';
        }

        return 'Ürün listesi endpoint yanıt verdi.';
    }
}
