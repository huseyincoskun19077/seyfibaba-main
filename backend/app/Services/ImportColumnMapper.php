<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportColumnMapper
{
    /** @var array<string, list<string>> */
    private const FIELD_SYNONYMS = [
        'name' => [
            'name', 'urun adi', 'urun_adi', 'urun ad', 'product name', 'product_name',
            'baslik', 'title', 'isim', 'ad', 'urun ismi', 'urun_ismi',
        ],
        'short_name' => ['short_name', 'short name', 'kisa ad', 'kisa_ad'],
        'slug' => ['slug', 'seourl', 'seo url'],
        'category' => ['category', 'kategori', 'ana kategori', 'ana_kategori', 'kategori adi'],
        'sub_category' => ['sub_category', 'sub category', 'alt kategori', 'alt_kategori'],
        'child_category' => ['child_category', 'child category', 'alt alt kategori', 'alt_alt_kategori'],
        'brand' => ['brand', 'marka', 'marka adi', 'marka_adi'],
        'price' => [
            'price', 'fiyat', 'birim fiyat', 'birim_fiyat', 'satis fiyati', 'satis_fiyati',
            'liste fiyati', 'liste_fiyati', 'ucret', 'tutar',
        ],
        'offer_price' => [
            'offer_price', 'offer price', 'indirimli fiyat', 'indirimli_fiyat',
            'kampanya fiyati', 'kampanya_fiyati',
        ],
        'qty' => ['qty', 'quantity', 'stok', 'stock', 'adet', 'miktar', 'quantity in stock'],
        'short_description' => ['short_description', 'short description', 'kisa aciklama', 'kisa_aciklama'],
        'long_description' => ['long_description', 'long description', 'uzun aciklama', 'uzun_aciklama', 'aciklama', 'description'],
        'sku' => ['sku', 'barkod', 'barcode', 'stok kodu', 'stok_kodu', 'urun kodu', 'urun_kodu', 'erp urun kodu', 'erp_urun_kodu', 'kod'],
        'weight' => ['weight', 'agirlik', 'gramaj'],
        'tags' => ['tags', 'etiket', 'etiketler', 'anahtar kelime', 'anahtar_kelime'],
        'image_url' => [
            'image_url', 'image url', 'image', 'resim url', 'resim_url', 'resim',
            'gorsel', 'gorsel url', 'gorsel_url', 'foto', 'fotograf', 'photo', 'picture',
        ],
    ];

    /**
     * @param  list<mixed>  $rawHeaders
     * @param  list<mixed>|null  $sampleRow
     * @return array{
     *   headers: list<string>,
     *   notes: list<string>,
     *   valid: bool
     * }
     */
    public function mapHeaders(array $rawHeaders, ?array $sampleRow = null): array
    {
        $notes = [];
        $columnCount = count($rawHeaders);
        $assignments = array_fill(0, $columnCount, null);

        foreach (self::FIELD_SYNONYMS as $field => $synonyms) {
            $bestIndex = null;
            $bestScore = 0;

            foreach ($rawHeaders as $index => $rawHeader) {
                if ($assignments[$index] !== null) {
                    continue;
                }

                $normalized = $this->normalizeHeader((string) $rawHeader);
                if ($normalized === '') {
                    continue;
                }

                $score = $this->synonymScore($normalized, $synonyms);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestIndex = $index;
                }
            }

            if ($bestIndex !== null && $bestScore > 0) {
                $assignments[$bestIndex] = $field;
                $original = trim((string) ($rawHeaders[$bestIndex] ?? ''));
                if ($original !== '' && Str::snake($original) !== $field) {
                    $notes[] = "Sütun eşleştirildi: \"{$original}\" → {$field}";
                }
            }
        }

        if (! in_array('name', $assignments, true) || ! in_array('price', $assignments, true)) {
            $aiMapping = $this->mapWithAi($rawHeaders, $sampleRow);
            if ($aiMapping) {
                foreach ($aiMapping as $index => $field) {
                    if (! isset($assignments[$index]) || $assignments[$index] === null) {
                        if (in_array($field, array_keys(self::FIELD_SYNONYMS), true)) {
                            $assignments[$index] = $field;
                            $original = trim((string) ($rawHeaders[$index] ?? ''));
                            $notes[] = "AI sütun eşleştirmesi: \"{$original}\" → {$field}";
                        }
                    }
                }
            }
        }

        $headers = [];
        foreach ($rawHeaders as $index => $rawHeader) {
            $headers[$index] = $assignments[$index] ?? Str::snake(trim((string) $rawHeader));
        }

        $hasName = in_array('name', $headers, true);
        $hasPrice = in_array('price', $headers, true);

        return [
            'headers' => $headers,
            'notes' => $notes,
            'valid' => $hasName && $hasPrice,
        ];
    }

    /**
     * @param  list<string>  $synonyms
     */
    private function synonymScore(string $normalized, array $synonyms): float
    {
        $best = 0.0;

        foreach ($synonyms as $position => $synonym) {
            if ($normalized === $synonym) {
                return 100 - $position;
            }

            if (str_contains($normalized, $synonym) || str_contains($synonym, $normalized)) {
                $best = max($best, 85 - $position);
            }
        }

        return $best;
    }

    private function normalizeHeader(string $header): string
    {
        $header = Str::ascii(mb_strtolower(trim($header)));
        $header = preg_replace('/[^a-z0-9\s]/', ' ', $header) ?? '';
        $header = preg_replace('/\s+/', ' ', $header) ?? '';

        return trim($header);
    }

    /**
     * @param  list<mixed>  $rawHeaders
     * @param  list<mixed>|null  $sampleRow
     * @return array<int, string>|null
     */
    private function mapWithAi(array $rawHeaders, ?array $sampleRow): ?array
    {
        $setting = Setting::first();
        if (! $setting || (! $setting->openai_enabled && ! $setting->claude_enabled)) {
            return null;
        }

        $headerList = collect($rawHeaders)
            ->map(fn ($h, $i) => $i . ': ' . trim((string) $h))
            ->implode("\n");

        $sampleList = $sampleRow
            ? collect($sampleRow)->map(fn ($v) => (string) $v)->implode(' | ')
            : '';

        $allowed = implode(', ', array_keys(self::FIELD_SYNONYMS));

        $prompt = <<<PROMPT
Sen e-ticaret Excel/CSV sütun eşleştirme uzmanısın. Satıcılar farklı başlıklar kullanır (Ürün Adı, name, başlık, Birim Fiyat, Stok, Marka vb.).

Dosya sütunları (index: başlık):
{$headerList}

Örnek veri satırı: {$sampleList}

Hedef alanlar (SADECE bunları kullan): {$allowed}

Her sütun index'i için en uygun hedef alanı seç. Eşleşmeyen sütunları atla.
SADECE JSON döndür: {"0":"name","3":"price"} gibi index→alan haritası.
Zorunlu: name ve price mutlaka eşleşmeli.
PROMPT;

        try {
            $raw = $this->callAiText($setting, $prompt);
            $decoded = json_decode(trim($raw), true);
            if (! is_array($decoded)) {
                if (preg_match('/\{[\s\S]*\}/', $raw, $match)) {
                    $decoded = json_decode($match[0], true);
                }
            }

            if (! is_array($decoded)) {
                return null;
            }

            $mapping = [];
            foreach ($decoded as $index => $field) {
                $mapping[(int) $index] = (string) $field;
            }

            return $mapping;
        } catch (\Throwable $e) {
            Log::warning('Import column AI mapping failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function callAiText(Setting $setting, string $prompt): string
    {
        $apiKey = trim($setting->openai_api_key ?? '');

        if ($setting->openai_enabled) {
            $endpoint = str_starts_with($apiKey, 'gsk_')
                ? 'https://api.groq.com/openai/v1/chat/completions'
                : 'https://api.openai.com/v1/chat/completions';

            $response = Http::timeout(max(30, (int) ($setting->openai_timeout ?? 30)))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'model' => $setting->openai_model ?? 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 400,
                    'temperature' => 0.1,
                ]);

            $data = $response->json();
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }
        }

        if ($setting->claude_enabled) {
            $response = Http::timeout(max(30, (int) ($setting->claude_timeout ?? 30)))
                ->withHeaders([
                    'x-api-key' => trim($setting->claude_api_key ?? ''),
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => $setting->claude_model ?? 'claude-sonnet-4-5-20250929',
                    'max_tokens' => 400,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

            $data = $response->json();
            if (isset($data['content'][0]['text'])) {
                return trim($data['content'][0]['text']);
            }
        }

        throw new \RuntimeException('AI yanıt vermedi');
    }

    public function normalizeNumeric(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        $normalized = trim((string) $value);
        $normalized = str_replace([' ', '₺', 'TL', 'tl', 'TRY', 'try'], '', $normalized);

        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $normalized)) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',') && ! str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? $normalized : null;
    }
}
