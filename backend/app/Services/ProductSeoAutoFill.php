<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ürün adı + açıklamadan SEO üretir.
 * AI açıksa kısa meta ister; kapalıysa veya hata olursa şablon kullanır.
 */
class ProductSeoAutoFill
{
    /**
     * @return array{seo_title: string, seo_description: string}
     */
    public function resolve(
        ?string $seoTitle,
        ?string $seoDescription,
        string $productName,
        ?string $shortDescription = null,
        ?string $longDescription = null
    ): array {
        $name = trim($productName);
        $title = trim((string) $seoTitle);
        $desc = trim((string) $seoDescription);

        if ($title !== '' && $desc !== '') {
            return [
                'seo_title' => mb_substr($title, 0, 70),
                'seo_description' => mb_substr($desc, 0, 160),
            ];
        }

        $ai = $this->tryAiMeta($name, $shortDescription, $longDescription);
        if ($ai !== null) {
            return [
                'seo_title' => $title !== '' ? mb_substr($title, 0, 70) : $ai['seo_title'],
                'seo_description' => $desc !== '' ? mb_substr($desc, 0, 160) : $ai['seo_description'],
            ];
        }

        $fallback = $this->fallbackMeta($name, $shortDescription, $longDescription);

        return [
            'seo_title' => $title !== '' ? mb_substr($title, 0, 70) : $fallback['seo_title'],
            'seo_description' => $desc !== '' ? mb_substr($desc, 0, 160) : $fallback['seo_description'],
        ];
    }

    /**
     * @return array{seo_title: string, seo_description: string}|null
     */
    protected function tryAiMeta(string $name, ?string $shortDescription, ?string $longDescription): ?array
    {
        try {
            $setting = Setting::query()->first();
            if (! $setting) {
                return null;
            }

            $openaiOn = (bool) ($setting->openai_enabled ?? false);
            $claudeOn = (bool) ($setting->claude_enabled ?? false);
            if (! $openaiOn && ! $claudeOn) {
                return null;
            }

            $plain = trim(strip_tags((string) ($shortDescription ?: $longDescription ?: '')));
            $plain = Str::limit(preg_replace('/\s+/u', ' ', $plain) ?: '', 800, '');

            $prompt = "Sen Seyfibaba (seyfibaba.com) SEO yazarısın. Platform: Türkiye’de berber, kuaför, güzellik salonu ve profesyonel kuaför malzemeleri pazaryeri.\n"
                ."Sadece Türkçe yaz. Alakasız ürün/sektör uydurma. Abartılı vaat yok.\n"
                ."Ürün adı: {$name}\n"
                ."Açıklama: {$plain}\n"
                ."seo_title max 60 karakter (ürün ana kelimesi + isteğe bağlı | Seyfibaba).\n"
                ."seo_description max 155 karakter; ürünün gerçek faydasına dayansın, salon/profesyonel bağlam doğal olsun.\n"
                .'Sadece JSON: {"seo_title":"...","seo_description":"..."}';

            $raw = null;
            if ($openaiOn && trim((string) ($setting->openai_api_key ?? '')) !== '') {
                $raw = $this->callOpenAiCompatible($setting, $prompt);
            }

            if ($raw === null && $claudeOn && trim((string) ($setting->claude_api_key ?? '')) !== '') {
                $raw = $this->callClaude($setting, $prompt);
            }

            if ($raw === null) {
                return null;
            }

            if (! preg_match('/\{.*\}/s', $raw, $m)) {
                return null;
            }

            $data = json_decode($m[0], true);
            if (! is_array($data)) {
                return null;
            }

            $t = trim((string) ($data['seo_title'] ?? ''));
            $d = trim((string) ($data['seo_description'] ?? ''));
            if ($t === '' || $d === '') {
                return null;
            }

            return [
                'seo_title' => mb_substr($t, 0, 70),
                'seo_description' => mb_substr($d, 0, 160),
            ];
        } catch (\Throwable $e) {
            Log::warning('ProductSeoAutoFill AI failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{seo_title: string, seo_description: string}
     */
    protected function fallbackMeta(string $name, ?string $shortDescription, ?string $longDescription): array
    {
        $plain = trim(strip_tags((string) ($shortDescription ?: $longDescription ?: $name)));
        $plain = preg_replace('/\s+/u', ' ', $plain) ?: $name;

        $title = mb_substr($name, 0, 55);
        if (! str_contains(mb_strtolower($title), 'seyfibaba')) {
            $title = mb_substr($title.' | Seyfibaba', 0, 60);
        }

        $descSource = $plain !== '' ? $plain : $name;
        $desc = mb_substr($descSource, 0, 155);
        if (mb_strlen($descSource) > 155) {
            $desc = rtrim(mb_substr($descSource, 0, 152)).'...';
        }
        // Salon bağlamı yoksa kısa ekle (spam değil, doğal)
        $lower = mb_strtolower($desc);
        if (! str_contains($lower, 'berber') && ! str_contains($lower, 'kuaför') && ! str_contains($lower, 'salon') && mb_strlen($desc) < 140) {
            $suffix = ' Profesyonel salon ve kuaför kullanımı için.';
            $desc = mb_substr(rtrim($desc, '.').'.'.$suffix, 0, 155);
        }

        return [
            'seo_title' => $title,
            'seo_description' => $desc !== '' ? $desc : $name,
        ];
    }

    protected function callOpenAiCompatible(Setting $setting, string $prompt): ?string
    {
        $base = rtrim((string) ($setting->openai_base_url ?: 'https://api.openai.com/v1'), '/');
        $model = (string) ($setting->openai_model ?: 'gpt-4o-mini');
        $key = (string) $setting->openai_api_key;

        $response = Http::withToken($key)
            ->timeout(25)
            ->post($base.'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Sen Seyfibaba pazaryeri (berber/kuaför/salon malzemeleri) için Türkçe SEO uzmanısın. Sadece geçerli JSON döndür. Alakasız sektör veya abartılı vaat yazma.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
                'max_tokens' => 300,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json('choices.0.message.content');
    }

    protected function callClaude(Setting $setting, string $prompt): ?string
    {
        $model = (string) ($setting->claude_model ?: 'claude-3-5-haiku-latest');
        $key = (string) $setting->claude_api_key;

        $response = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(25)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 300,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            return null;
        }

        $blocks = $response->json('content');
        if (! is_array($blocks)) {
            return null;
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'text') {
                return (string) ($block['text'] ?? '');
            }
        }

        return null;
    }
}
