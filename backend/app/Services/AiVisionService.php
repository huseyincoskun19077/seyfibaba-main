<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiVisionService
{
    /**
     * Analyze a product photo and return structured content suggestions.
     *
     * @param  array<int, array{id:int,name:string,subcategories:array<int,array{id:int,name:string}>}>  $categoryTree
     */
    public function analyzeProduct(
        UploadedFile $image,
        string $productName,
        float $price,
        ?float $offerPrice,
        int $quantity,
        array $categoryTree
    ): array {
        $setting = Setting::first();
        $openaiEnabled = (bool) $setting->openai_enabled;
        $openaiKey = trim((string) ($setting->openai_api_key ?? ''));
        $claudeEnabled = (bool) $setting->claude_enabled;
        $claudeKey = trim((string) ($setting->claude_api_key ?? ''));
        $isGroq = str_starts_with($openaiKey, 'gsk_');

        $prompt = $this->buildPrompt($productName, $price, $offerPrice, $quantity, $categoryTree);
        $imageData = $this->encodeImage($image);
        $unavailable = new \RuntimeException(
            'Açıklama şu an doldurulamadı. Kendiniz yazabilir veya daha sonra tekrar deneyebilirsiniz.'
        );

        if ($openaiEnabled && $openaiKey !== '' && ! $isGroq) {
            try {
                $model = $this->visionModel($setting->openai_model ?? 'gpt-4o-mini', true);
                $raw = $this->callOpenAIVision($setting, $prompt, $imageData, $image->getMimeType(), $model);

                return $this->parseResponse($raw);
            } catch (\Throwable $e) {
                Log::warning('OpenAI vision failed', ['message' => $e->getMessage()]);
            }
        }

        if ($claudeEnabled && $claudeKey !== '') {
            try {
                $raw = $this->callClaudeVision($setting, $prompt, $imageData, $image->getMimeType());

                return $this->parseResponse($raw);
            } catch (\Throwable $e) {
                Log::warning('Vision fallback failed', ['message' => $e->getMessage()]);
            }
        }

        if ($openaiEnabled && $openaiKey !== '') {
            try {
                $textPrompt = $prompt . "\n\nNOT: Görsel analizi yapılamadı. Sadece ürün adına ve fiyat bilgisine göre en uygun içeriği üret.";
                $raw = $this->callOpenAIText($setting, $textPrompt);

                return $this->parseResponse($raw);
            } catch (\Throwable $e) {
                Log::warning('OpenAI text fallback failed', ['message' => $e->getMessage()]);
            }
        }

        throw $unavailable;
    }

    private function buildPrompt(
        string $productName,
        float $price,
        ?float $offerPrice,
        int $quantity,
        array $categoryTree
    ): string {
        $categoryJson = json_encode($categoryTree, JSON_UNESCAPED_UNICODE);
        $priceInfo = $offerPrice && $offerPrice > 0 && $offerPrice < $price
            ? "Liste fiyatı: {$price} TL, indirimli fiyat: {$offerPrice} TL"
            : "Fiyat: {$price} TL";

        return <<<PROMPT
Sen Seyfibaba berber, kuaför ve güzellik salonu ekipmanları pazaryeri için ürün içerik uzmanısın. Satıcının girdiği bilgiler ve ürün fotoğrafını analiz ederek eksiksiz ürün içeriği üret. Berber koltuğu, kuaför tezgahı, makas, fön makinesi gibi salon ekipmanlarına odaklan.

Satıcının girdiği bilgiler (bunları DEĞİŞTİRME):
- Ürün adı: {$productName}
- Stok adedi: {$quantity}
- {$priceInfo}

Mevcut kategoriler (category_id ve sub_category_id değerlerini SADECE bu listeden seç):
{$categoryJson}

Fotoğrafı incele ve ürünü tanı. Türkiye pazarına uygun, satış odaklı Türkçe içerik üret.

SADECE geçerli JSON döndür, markdown veya açıklama ekleme:
{
  "category_id": 0,
  "sub_category_id": 0,
  "short_name": "kısa ürün adı max 30 karakter",
  "name": "SEO uyumlu tam ürün adı",
  "short_description": "1-2 cümle özet",
  "long_description": "HTML açıklama (<p>, <ul>, <li>, <strong> kullan, 3-5 paragraf)",
  "seo_title": "max 60 karakter",
  "seo_description": "max 155 karakter",
  "tags": "virgülle ayrılmış 5-8 anahtar kelime",
  "brand_name": "fotoğraftan veya ürün adından tahmin edilen marka adı (yoksa boş string)"
}

category_id mutlaka listeden geçerli bir ID olmalı. Alt kategori yoksa sub_category_id 0 olsun.
PROMPT;
    }

    private function encodeImage(UploadedFile $image): string
    {
        return base64_encode(file_get_contents($image->getRealPath()));
    }

    private function visionModel(string $model, bool $openai): string
    {
        $visionCapable = ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano'];

        if ($openai && !in_array($model, $visionCapable, true)) {
            return 'gpt-4o-mini';
        }

        return $model;
    }

    private function callOpenAIVision(Setting $setting, string $prompt, string $base64, string $mimeType, string $model): string
    {
        $apiKey = trim($setting->openai_api_key ?? '');
        $timeout = max(60, (int) ($setting->openai_timeout ?? 60));

        if (!$apiKey) {
            throw new \RuntimeException('OpenAI API anahtarı yapılandırılmamış.');
        }

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:' . $mimeType . ';base64,' . $base64,
                                    'detail' => 'low',
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 4000,
                'temperature' => 0.4,
            ]);

        return $this->extractOpenAIContent($response->json());
    }

    private function callOpenAIText(Setting $setting, string $prompt): string
    {
        $apiKey = trim($setting->openai_api_key ?? '');
        $model = $setting->openai_model ?? 'gpt-4o-mini';
        $timeout = max(60, (int) ($setting->openai_timeout ?? 60));

        $endpoint = str_starts_with($apiKey, 'gsk_')
            ? 'https://api.groq.com/openai/v1/chat/completions'
            : 'https://api.openai.com/v1/chat/completions';

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 4000,
                'temperature' => 0.5,
            ]);

        return $this->extractOpenAIContent($response->json());
    }

    private function extractOpenAIContent(?array $data): string
    {
        if (isset($data['error'])) {
            throw new \RuntimeException('OpenAI: ' . ($data['error']['message'] ?? 'Bilinmeyen hata'));
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Geçersiz OpenAI yanıtı');
        }

        return trim($data['choices'][0]['message']['content']);
    }

    private function callClaudeVision(Setting $setting, string $prompt, string $base64, string $mimeType): string
    {
        $apiKey = trim($setting->claude_api_key ?? '');
        $model = $setting->claude_model ?? 'claude-sonnet-4-5-20250929';
        $timeout = max(60, (int) ($setting->claude_timeout ?? 60));

        if (!$apiKey) {
            throw new \RuntimeException('Açıklama şu an doldurulamadı. Kendiniz yazabilir veya daha sonra tekrar deneyebilirsiniz.');
        }

        $mediaType = str_starts_with($mimeType, 'image/') ? $mimeType : 'image/jpeg';

        $response = Http::timeout($timeout)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 4000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mediaType,
                                    'data' => $base64,
                                ],
                            ],
                            ['type' => 'text', 'text' => $prompt],
                        ],
                    ],
                ],
            ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \RuntimeException('Claude: ' . ($data['error']['message'] ?? 'Bilinmeyen hata'));
        }

        if (!isset($data['content'][0]['text'])) {
            throw new \RuntimeException('Geçersiz Claude yanıtı');
        }

        return trim($data['content'][0]['text']);
    }

    private function parseResponse(string $raw): array
    {
        $text = trim($raw);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $this->normalizeFields($decoded);
        }

        if (preg_match('/```(?:json)?\s*\n?([\s\S]*?)\n?```/', $text, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            if (is_array($decoded)) {
                return $this->normalizeFields($decoded);
            }
        }

        $first = strpos($text, '{');
        $last = strrpos($text, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $decoded = json_decode(substr($text, $first, $last - $first + 1), true);
            if (is_array($decoded)) {
                return $this->normalizeFields($decoded);
            }
        }

        throw new \RuntimeException('AI yanıtı işlenemedi. Lütfen tekrar deneyin.');
    }

    private function normalizeFields(array $data): array
    {
        return [
            'category_id' => (int) ($data['category_id'] ?? 0),
            'sub_category_id' => (int) ($data['sub_category_id'] ?? 0),
            'short_name' => trim((string) ($data['short_name'] ?? '')),
            'name' => trim((string) ($data['name'] ?? '')),
            'short_description' => trim((string) ($data['short_description'] ?? '')),
            'long_description' => trim((string) ($data['long_description'] ?? '')),
            'seo_title' => trim((string) ($data['seo_title'] ?? '')),
            'seo_description' => trim((string) ($data['seo_description'] ?? '')),
            'tags' => trim((string) ($data['tags'] ?? '')),
            'brand_name' => trim((string) ($data['brand_name'] ?? '')),
        ];
    }
}
