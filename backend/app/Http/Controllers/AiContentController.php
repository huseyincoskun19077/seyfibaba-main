<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiContentController extends Controller
{
    // Auth is handled by route-level middleware (api.php groups or web.php groups)

    /**
     * Generate AI-powered product content.
     *
     * Actions:
     * - full: Generate complete product content (name, description, meta, return/delivery text)
     * - enhance: Improve existing product content
     * - translate: Translate product content to another language
     * - generate_meta: Generate SEO meta tags from existing content
     * - (no action): Plain prompt mode
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'nullable|string|max:5000',
            'action' => 'nullable|string|in:full,enhance,translate,generate_meta',
            'content_type' => 'nullable|string|in:product,blog',
            'product_name' => 'nullable|string|max:500',
            'category_name' => 'nullable|string|max:500',
            'existing_content' => 'nullable|array',
            'target_lang' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $setting = Setting::first();
        $openaiEnabled = (bool) $setting->openai_enabled;
        $claudeEnabled = (bool) $setting->claude_enabled;

        if (!$openaiEnabled && !$claudeEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'AI içerik üretimi şu anda kapalı. Admin panelden AI ayarlarını aktifleştirin.',
            ], 503);
        }

        $action = $request->input('action');

        $contentType = $request->input('content_type', 'product');

        if ($action) {
            $prompt = $contentType === 'blog'
                ? $this->buildBlogPrompt($action, $request->all())
                : $this->buildActionPrompt($action, $request->all());
        } else {
            if (!$request->prompt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prompt veya action parametresi gerekli.',
                ], 422);
            }
            $prompt = $request->prompt;
        }

        $maxTokens = $action ? 4000 : 500;
        $claudeReady = $claudeEnabled && trim((string) ($setting->claude_api_key ?? '')) !== '';

        if ($openaiEnabled) {
            try {
                $content = $this->callOpenAI($setting, $prompt, $maxTokens);
                return $this->formatResponse($content, $action);
            } catch (\Exception $e) {
                Log::warning('OpenAI/Groq failed', ['message' => $e->getMessage()]);
                if ($claudeReady) {
                    try {
                        $content = $this->callClaude($setting, $prompt, $maxTokens);
                        return $this->formatResponse($content, $action);
                    } catch (\Exception $e2) {
                        Log::error('AI fallback failed', ['message' => $e2->getMessage()]);
                        return $this->aiErrorResponse($e2->getMessage());
                    }
                }
                return $this->aiErrorResponse($e->getMessage());
            }
        }

        if ($claudeReady) {
            try {
                $content = $this->callClaude($setting, $prompt, $maxTokens);
                return $this->formatResponse($content, $action);
            } catch (\Exception $e) {
                Log::error('AI provider failed', ['message' => $e->getMessage()]);
                return $this->aiErrorResponse($e->getMessage());
            }
        }

        return $this->aiErrorResponse('unavailable');
    }

    private function buildActionPrompt(string $action, array $params): string
    {
        $productName = $params['product_name'] ?? 'Bilinmeyen Ürün';
        $categoryName = $params['category_name'] ?? '';
        $existingContent = $params['existing_content'] ?? [];
        $targetLang = $params['target_lang'] ?? 'en';

        $systemBase = "Sen profesyonel bir berber, kuaför ve güzellik salonu ekipmanları içerik yazarısın. SEO uyumlu, çekici ve satış odaklı ürün içerikleri yazarsın. Türkiye'deki salon profesyonellerine yönelik içerik üretirsin. Berber koltuğu, kuaför malzemesi, tıraş ve bakım ekipmanları gibi ürünlere odaklan. Tüm içerikleri sadece Türkçe olarak yaz, kesinlikle başka dil kullanma.\n";

        $jsonStructure = '{"name":"","short_description":"","long_description":"","seo_title":"","seo_description":"","tags":"","return_policy_text":"","delivery_time_text":""}';

        switch ($action) {
            case 'full':
                $categoryCtx = $categoryName ? "\nÜrün kategorisi: {$categoryName}" : '';
                return $systemBase . "\"{$productName}\" ürünü için eksiksiz ürün içeriği oluştur.{$categoryCtx}

Aşağıdaki alanların HEPSİNİ Türkçe olarak doldur (hiçbiri boş kalamaz):
- name: Ürün adı (SEO uyumlu, çekici)
- short_description: Kısa açıklama (1-2 cümle, ürünün öne çıkan özellikleri)
- long_description: Uzun HTML açıklama (3-5 paragraf, <p>, <ul>, <li>, <strong> etiketleri kullan. Ürün özellikleri, kullanım alanları, avantajları detaylı anlat)
- seo_title: SEO başlığı (max 60 karakter, ana anahtar kelimeyi içersin)
- seo_description: SEO açıklaması (max 155 karakter, eylem çağrısı içersin)
- tags: Virgülle ayrılmış 5-8 anahtar kelime
- return_policy_text: İade politikası metni (1-2 cümle)
- delivery_time_text: Teslimat süresi açıklaması (örn: \"2-4 iş günü\")

SADECE geçerli JSON döndür, markdown veya açıklama ekleme. Şema:
{$jsonStructure}";

            case 'enhance':
                $existingJson = json_encode($existingContent, JSON_UNESCAPED_UNICODE);
                return $systemBase . "Aşağıdaki ürün içeriğini iyileştir ve zenginleştir. Açıklamaları daha çekici yap, SEO'yu optimize et, dilbilgisi hatalarını düzelt.

Mevcut içerik:
{$existingJson}

Aynı yapıyı koru. Kaliteyi artır, kısa açıklamaları genişlet, meta etiketlerini SEO için optimize et.
Tüm alanlar dolu olmalı.
SADECE geçerli JSON döndür, markdown veya açıklama ekleme. Şema:
{$jsonStructure}";

            case 'translate':
                $existingJson = json_encode($existingContent, JSON_UNESCAPED_UNICODE);
                $langNames = ['en' => 'İngilizce', 'de' => 'Almanca', 'fr' => 'Fransızca', 'ar' => 'Arapça', 'tr' => 'Türkçe'];
                $targetName = $langNames[$targetLang] ?? $targetLang;
                return $systemBase . "Aşağıdaki ürün içeriğini {$targetName} diline çevir.

Kaynak içerik:
{$existingJson}

ÖNEMLİ: Tüm alanları {$targetName} dilinde yaz. HTML etiketlerini koru.
Tüm alanlar dolu olmalı.
SADECE geçerli JSON döndür, markdown veya açıklama ekleme. Şema:
{$jsonStructure}";

            case 'generate_meta':
                $existingJson = json_encode($existingContent, JSON_UNESCAPED_UNICODE);
                return $systemBase . "Aşağıdaki ürün içeriğine dayalı olarak SEO meta etiketleri oluştur.

Ürün içeriği:
{$existingJson}

Sadece şu alanları güncelle (diğerlerini mevcut içerikten koru):
- seo_title: SEO başlığı (max 60 karakter, ana anahtar kelimeyi içersin)
- seo_description: SEO açıklaması (max 155 karakter, eylem çağrısı içersin)
- tags: Virgülle ayrılmış 5-8 anahtar kelime

SADECE geçerli JSON döndür, markdown veya açıklama ekleme. Şema:
{$jsonStructure}";

            default:
                return "\"{$productName}\" için ürün içeriği oluştur.";
        }
    }

    private function buildBlogPrompt(string $action, array $params): string
    {
        $title = $params['product_name'] ?? 'Blog Yazısı';
        $categoryName = $params['category_name'] ?? '';
        $existingContent = $params['existing_content'] ?? [];

        $systemBase = "Sen profesyonel bir blog yazarısın. Kuaför ve berber sektörüne yönelik SEO uyumlu, bilgilendirici ve okuyucu dostu blog yazıları yazarsın. Tüm içerikleri sadece Türkçe olarak yaz.\n";

        $jsonStructure = '{"title":"","description":"","seo_title":"","seo_description":""}';

        switch ($action) {
            case 'full':
                $categoryCtx = $categoryName ? "\nBlog kategorisi: {$categoryName}" : '';
                return $systemBase . "\"{$title}\" başlıklı bir blog yazısı oluştur.{$categoryCtx}

Aşağıdaki alanların HEPSİNİ Türkçe olarak doldur:
- title: Blog başlığı (SEO uyumlu, dikkat çekici, max 80 karakter)
- description: Blog içeriği (HTML formatında, en az 800 kelime. <h2>, <h3>, <p>, <ul>, <li>, <strong> etiketleri kullan. Giriş paragrafı, ana bölümler, sonuç paragrafı olsun. Okuyucuya pratik bilgi ve tavsiyeler ver.)
- seo_title: SEO başlığı (max 60 karakter)
- seo_description: SEO açıklaması (max 155 karakter, eylem çağrısı içersin)

SADECE geçerli JSON döndür, markdown veya açıklama ekleme. Şema:
{$jsonStructure}";

            case 'enhance':
                $existingJson = json_encode($existingContent, JSON_UNESCAPED_UNICODE);
                return $systemBase . "Aşağıdaki blog içeriğini iyileştir ve zenginleştir. Daha detaylı, bilgilendirici ve SEO uyumlu hale getir. HTML yapısını koru ve geliştir.

Mevcut içerik:
{$existingJson}

Tüm alanlar dolu olmalı. Kaliteyi artır, içeriği genişlet, alt başlıklar ekle.
SADECE geçerli JSON döndür, markdown veya açıklama ekleme. Şema:
{$jsonStructure}";

            case 'generate_meta':
                $existingJson = json_encode($existingContent, JSON_UNESCAPED_UNICODE);
                return $systemBase . "Aşağıdaki blog içeriğine dayalı olarak SEO meta etiketleri oluştur.

Blog içeriği:
{$existingJson}

Sadece şu alanları güncelle (diğerlerini mevcut içerikten koru):
- seo_title: SEO başlığı (max 60 karakter)
- seo_description: SEO açıklaması (max 155 karakter)

SADECE geçerli JSON döndür, markdown veya açıklama ekleme. Şema:
{$jsonStructure}";

            default:
                return "\"{$title}\" başlıklı blog yazısı oluştur.";
        }
    }

    private function callOpenAI(Setting $setting, string $prompt, int $maxTokens): string
    {
        $apiKey = trim($setting->openai_api_key ?? '');
        $model = $setting->openai_model ?? 'gpt-4o-mini';
        $timeout = $setting->openai_timeout ?? 60;

        if (!$apiKey) {
            throw new \Exception('OpenAI API anahtarı yapılandırılmamış.');
        }

        // Auto-detect Groq keys (gsk_ prefix)
        $endpoint = str_starts_with($apiKey, 'gsk_')
            ? 'https://api.groq.com/openai/v1/chat/completions'
            : 'https://api.openai.com/v1/chat/completions';

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \Exception('OpenAI Error: ' . ($data['error']['message'] ?? 'Bilinmeyen hata'));
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \Exception('Geçersiz OpenAI API yanıt formatı');
        }

        return trim($data['choices'][0]['message']['content']);
    }

    private function callClaude(Setting $setting, string $prompt, int $maxTokens): string
    {
        $apiKey = trim($setting->claude_api_key ?? '');
        $model = $setting->claude_model ?? 'claude-sonnet-4-5-20250929';
        $timeout = $setting->claude_timeout ?? 60;

        if (!$apiKey) {
            throw new \Exception('Claude API anahtarı yapılandırılmamış.');
        }

        $response = Http::timeout($timeout)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \Exception('Claude Error: ' . ($data['error']['message'] ?? 'Bilinmeyen hata'));
        }

        if (!isset($data['content'][0]['text'])) {
            throw new \Exception('Geçersiz Claude API yanıt formatı');
        }

        return trim($data['content'][0]['text']);
    }

    private function formatResponse(string $generatedContent, ?string $action)
    {
        if ($action) {
            $parsed = $this->parseJsonFromResponse($generatedContent);
            if ($parsed) {
                return response()->json([
                    'success' => true,
                    'results' => $parsed,
                    'generated_content' => $generatedContent,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'generated_content' => $generatedContent,
        ]);
    }

    private function parseJsonFromResponse(string $text): ?array
    {
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Extract from markdown code blocks
        if (preg_match('/```(?:json)?\s*\n?([\s\S]*?)\n?```/', $text, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Find first { to last }
        $first = strpos($text, '{');
        $last = strrpos($text, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $decoded = json_decode(substr($text, $first, $last - $first + 1), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function aiErrorResponse(string $rawMessage)
    {
        Log::warning('AI content error', ['message' => $rawMessage]);

        return response()->json([
            'success' => false,
            'message' => 'Açıklama şu an doldurulamadı. Kendiniz yazabilir veya daha sonra tekrar deneyebilirsiniz.',
        ], 500);
    }
}
