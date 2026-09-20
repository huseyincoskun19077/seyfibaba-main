<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminAdAssistantService
{
    public function buildSystemMessage(): string
    {
        $cfg = config('admin_ad_assistant');
        $knowledge = $cfg['knowledge'] ?? [];

        $lines = [$cfg['system_prompt'] ?? ''];
        $lines[] = '';
        $lines[] = '=== SEYFIBABA REKLAM BİLGİ BANKASI (tek kaynak) ===';
        foreach ($knowledge as $key => $value) {
            if (is_array($value)) {
                $lines[] = strtoupper((string) $key) . ': ' . implode(' | ', $value);
            } else {
                $lines[] = strtoupper((string) $key) . ': ' . $value;
            }
        }
        $lines[] = '=== BİLGİ BANKASI SONU ===';
        $lines[] = 'Sipariş, müşteri veya ödeme verisine erişimin yok. Proje dosyalarını değiştiremezsin.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{reply: string, model: string}
     */
    public function chat(array $history, string $userMessage): array
    {
        $setting = Setting::first();
        if (! $setting || ! $setting->openai_enabled) {
            throw new \RuntimeException('OpenAI AI ayarları kapalı. Admin → AI Ayarları’ndan açın.');
        }

        $apiKey = trim((string) ($setting->openai_api_key ?? ''));
        if ($apiKey === '' || str_starts_with($apiKey, 'gsk_')) {
            throw new \RuntimeException('Reklam asistanı için OpenAI API anahtarı gerekir (Groq görsel/Astra için uygun değil).');
        }

        $preferred = (string) config('admin_ad_assistant.chat_model', 'gpt-6-astra');
        $fallback = (string) config('admin_ad_assistant.chat_model_fallback', $setting->openai_model ?: 'gpt-4o');
        $timeout = (int) ($setting->openai_timeout ?? 90);
        $maxTokens = (int) config('admin_ad_assistant.max_tokens', 2500);
        $temperature = (float) config('admin_ad_assistant.temperature', 0.7);

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemMessage()],
        ];

        $maxHistory = (int) config('admin_ad_assistant.max_history', 20);
        $trimmed = array_slice($history, -$maxHistory);
        foreach ($trimmed as $row) {
            $role = ($row['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $messages[] = ['role' => $role, 'content' => $content];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $lastError = null;
        foreach (array_unique([$preferred, $fallback]) as $model) {
            try {
                $reply = $this->callChatCompletions($apiKey, $model, $messages, $maxTokens, $temperature, $timeout);

                return ['reply' => $reply, 'model' => $model];
            } catch (\Throwable $e) {
                $lastError = $e;
                Log::warning('AdminAdAssistant chat model failed', [
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        throw new \RuntimeException($lastError?->getMessage() ?: 'Chat isteği başarısız.');
    }

    /**
     * @return array{url: string, prompt: string, model: string}
     */
    public function generateImage(string $prompt, string $size = '1024x1024'): array
    {
        $setting = Setting::first();
        if (! $setting || ! $setting->openai_enabled) {
            throw new \RuntimeException('OpenAI AI ayarları kapalı.');
        }

        $apiKey = trim((string) ($setting->openai_api_key ?? ''));
        if ($apiKey === '' || str_starts_with($apiKey, 'gsk_')) {
            throw new \RuntimeException('Görsel üretim için OpenAI API anahtarı gerekir.');
        }

        $prompt = trim($prompt);
        if (mb_strlen($prompt) < 8) {
            throw new \InvalidArgumentException('Görsel açıklaması çok kısa.');
        }

        // Reklam dışı / hassas istekleri kaba filtre
        $blocked = ['password', 'iban', 'sipariş', 'order id', 'exploit', 'hack', 'xss'];
        $lower = mb_strtolower($prompt);
        foreach ($blocked as $word) {
            if (str_contains($lower, $word)) {
                throw new \InvalidArgumentException('Bu prompt reklam görseli için uygun değil.');
            }
        }

        $branded = $prompt . "\n\nBrand context: Seyfibaba Turkish barber/hair salon B2B marketplace ad creative. Clean professional look, no fake logos of other marketplaces, no readable personal data, no phone numbers invented.";

        $model = (string) config('admin_ad_assistant.image_model', 'gpt-image-1');
        $timeout = max(60, (int) ($setting->openai_timeout ?? 90));

        $payload = [
            'model' => $model,
            'prompt' => $branded,
            'size' => $this->normalizeSize($size),
            'n' => 1,
        ];

        // gpt-image-1 returns b64_json by default in many accounts; request url when supported
        if (str_starts_with($model, 'dall-e')) {
            $payload['response_format'] = 'b64_json';
            $payload['quality'] = 'standard';
        }

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/images/generations', $payload);

        $data = $response->json();
        if (! $response->successful() || isset($data['error'])) {
            $msg = $data['error']['message'] ?? ('HTTP ' . $response->status());
            // Fallback to dall-e-3 once
            if ($model !== 'dall-e-3') {
                return $this->generateImageWithModel($apiKey, 'dall-e-3', $branded, $size, $timeout);
            }
            throw new \RuntimeException('Görsel üretilemedi: ' . $msg);
        }

        return $this->persistImageResult($data, $prompt, $model);
    }

    private function generateImageWithModel(string $apiKey, string $model, string $prompt, string $size, int $timeout): array
    {
        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'size' => $this->normalizeSize($size, true),
                'n' => 1,
                'response_format' => 'b64_json',
                'quality' => 'standard',
            ]);

        $data = $response->json();
        if (! $response->successful() || isset($data['error'])) {
            throw new \RuntimeException('Görsel üretilemedi: ' . ($data['error']['message'] ?? 'bilinmeyen hata'));
        }

        return $this->persistImageResult($data, $prompt, $model);
    }

    private function persistImageResult(array $data, string $prompt, string $model): array
    {
        $item = $data['data'][0] ?? null;
        if (! $item) {
            throw new \RuntimeException('Görsel yanıtı boş.');
        }

        $dir = public_path('uploads/admin-ads');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'ad-' . date('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.png';
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (! empty($item['b64_json'])) {
            file_put_contents($path, base64_decode($item['b64_json']));
        } elseif (! empty($item['url'])) {
            $bin = Http::timeout(60)->get($item['url'])->body();
            file_put_contents($path, $bin);
        } else {
            throw new \RuntimeException('Görsel verisi bulunamadı.');
        }

        $publicUrl = asset('uploads/admin-ads/' . $filename);

        return [
            'url' => $publicUrl,
            'path' => 'uploads/admin-ads/' . $filename,
            'prompt' => $prompt,
            'model' => $model,
        ];
    }

    private function callChatCompletions(
        string $apiKey,
        string $model,
        array $messages,
        int $maxTokens,
        float $temperature,
        int $timeout
    ): string {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ];

        // Newer models may prefer max_completion_tokens
        if (str_contains($model, 'gpt-6') || str_contains($model, 'o1') || str_contains($model, 'o3')) {
            $payload['max_completion_tokens'] = $maxTokens;
        } else {
            $payload['max_tokens'] = $maxTokens;
        }

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        $data = $response->json();
        if (! $response->successful() || isset($data['error'])) {
            throw new \RuntimeException($data['error']['message'] ?? ('HTTP ' . $response->status()));
        }

        $content = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
        if ($content === '') {
            throw new \RuntimeException('Boş model yanıtı.');
        }

        return $content;
    }

    private function normalizeSize(string $size, bool $dalle = false): string
    {
        $allowed = $dalle
            ? ['1024x1024', '1024x1792', '1792x1024']
            : ['1024x1024', '1024x1536', '1536x1024', '1024x1792', '1792x1024'];

        return in_array($size, $allowed, true) ? $size : '1024x1024';
    }
}
