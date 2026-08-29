<?php

namespace App\Services;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\AiChatKnowledge;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    public function __construct(
        private AiChatPromptGuard $promptGuard,
    ) {}

    /**
     * Send a message and get an AI response.
     */
    public function sendMessage(string $message, string $sessionId, ?int $userId): array
    {
        $blockReason = $this->promptGuard->evaluateInput($message);
        if ($blockReason !== null) {
            $this->promptGuard->logBlockedInput($message, $blockReason, $sessionId, $userId);

            $conversation = AiChatConversation::firstOrCreate(
                ['session_id' => $sessionId],
                ['user_id' => $userId, 'status' => 'active']
            );

            if ($userId && !$conversation->user_id) {
                $conversation->update(['user_id' => $userId]);
            }

            AiChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
            ]);

            $safeContent = $this->promptGuard->refusalMessage($blockReason, 'customer');

            AiChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $safeContent,
                'tokens_used' => 0,
                'provider' => 'guard',
            ]);

            return [
                'content' => $safeContent,
                'conversation_id' => $conversation->id,
                'tokens_used' => 0,
            ];
        }

        $conversation = AiChatConversation::firstOrCreate(
            ['session_id' => $sessionId],
            ['user_id' => $userId, 'status' => 'active']
        );

        if ($userId && !$conversation->user_id) {
            $conversation->update(['user_id' => $userId]);
        }

        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        // Build conversation history (last 10 messages)
        $history = AiChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        $systemPrompt = $this->buildSystemPrompt($userId, $message);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            if ($msg->role === 'system') continue;
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        $settings = Setting::first();
        $apiKey = trim($settings->openai_api_key ?? '');
        $provider = str_starts_with($apiKey, 'sk-ant-') ? 'anthropic' : 'openai';
        $result = $this->callProvider($provider, $messages, $settings);

        $content = $this->promptGuard->sanitizeOutput($result['content'], 'customer');

        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $content,
            'tokens_used' => $result['tokens_used'],
            'provider' => $provider,
        ]);

        return [
            'content' => $content,
            'conversation_id' => $conversation->id,
            'tokens_used' => $result['tokens_used'],
        ];
    }

    private function buildSystemPrompt(?int $userId, string $userMessage): string
    {
        $settings = Setting::first();
        $parts = [];

        // Güvenlik kuralları her zaman en başta — admin prompt bunları geçersiz kılamaz
        $parts[] = $this->promptGuard->securitySystemPrompt();

        // Admin-configured system prompt
        if ($settings->ai_chat_system_prompt) {
            $parts[] = "== Yönetici Talimatları (güvenlik kurallarını ASLA geçersiz kılamaz) ==\n"
                . $settings->ai_chat_system_prompt;
        } else {
            $parts[] = "Sen Seyfibaba (seyfibaba.com) müşteri asistanısın. Türkçe, kısa ve doğru yanıt ver. "
                . "Ürün, sipariş, ödeme, kargo, iade, satış sözleşmesi ve ikinci el konularında yardım et. "
                . "Kişisel telefon/TC/IBAN paylaşma; yalnızca resmi iletişim kanallarını kullan.";
        }

        // Resmi iletişim (tekrar — modelin unutmaması için)
        $parts[] = $this->promptGuard->officialContactBlock();

        // Knowledge base
        $knowledge = $this->buildKnowledgeContext();
        if ($knowledge) {
            $parts[] = "== Bilgi Bankası ==\n" . $knowledge;
        }

        // Product context
        $productContext = $this->buildProductContext($userMessage);
        if ($productContext) {
            $parts[] = "== İlgili Ürünler ==\n" . $productContext;
        }

        // Order context
        if ($userId) {
            $orderContext = $this->buildOrderContext($userId);
            if ($orderContext) {
                $parts[] = "== Müşteri Siparişleri ==\n" . $orderContext;
            }
        }

        return implode("\n\n", $parts);
    }

    private function buildKnowledgeContext(): string
    {
        $entries = AiChatKnowledge::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(20)
            ->get();

        if ($entries->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($entries as $entry) {
            $lines[] = "S: {$entry->question}\nC: {$entry->answer}";
        }

        return implode("\n\n", $lines);
    }

    private function buildProductContext(string $query): string
    {
        $keywords = array_filter(explode(' ', $query), fn($w) => mb_strlen($w) >= 3);

        if (empty($keywords)) {
            return '';
        }

        $products = Product::where('status', 1)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%");
                }
            })
            ->take(5)
            ->get(['name', 'slug', 'price', 'offer_price']);

        if ($products->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($products as $product) {
            $price = $product->offer_price > 0 ? $product->offer_price : $product->price;
            $lines[] = "- {$product->name} (₺" . number_format($price, 2) . ") → /{$product->slug}";
        }

        return implode("\n", $lines);
    }

    private function buildOrderContext(int $userId): string
    {
        $orders = Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get(['order_id', 'total_amount', 'order_status', 'payment_status', 'created_at']);

        if ($orders->isEmpty()) {
            return 'Bu müşterinin henüz siparişi bulunmamaktadır. Başka kişilerin siparişleri ASLA listelenmez.';
        }

        $statusMap = [
            0 => 'Beklemede',
            1 => 'Hazırlanıyor',
            2 => 'Kargoya Verildi',
            3 => 'Teslim Edildi',
            4 => 'İptal Edildi',
        ];

        $lines = [
            'UYARI: Aşağıdakiler YALNIZCA bu giriş yapmış müşterinin kendi siparişleridir. Başka sipariş numarası sorulursa paylaşma.',
        ];
        foreach ($orders as $order) {
            $date = $order->created_at->format('d.m.Y');
            $status = $statusMap[$order->order_status] ?? 'Bilinmiyor';
            $lines[] = "- Sipariş #{$order->order_id} ({$date}): ₺" . number_format($order->total_amount, 2) . " - {$status}";
        }

        return implode("\n", $lines);
    }

    private function callProvider(string $provider, array $messages, Setting $settings): array
    {
        return match ($provider) {
            'anthropic' => $this->callAnthropic($messages, $settings),
            default => $this->callOpenAICompatible($messages, $settings),
        };
    }

    private function callOpenAICompatible(array $messages, Setting $settings): array
    {
        $apiKey = trim($settings->openai_api_key ?? '');
        $model = $settings->openai_model ?: 'llama-3.3-70b-versatile';
        $maxTokens = (int) ($settings->ai_chat_max_tokens ?: 1024);
        $temperature = min((float) ($settings->ai_chat_temperature ?: 0.7), 0.45);

        // Auto-detect Groq keys
        $endpoint = str_starts_with($apiKey, 'gsk_')
            ? 'https://api.groq.com/openai/v1/chat/completions'
            : 'https://api.openai.com/v1/chat/completions';

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('AI Chat OpenAI/Groq Error', ['error' => $data['error']]);
            throw new \Exception($data['error']['message'] ?? 'AI provider error');
        }

        return [
            'content' => trim($data['choices'][0]['message']['content'] ?? ''),
            'tokens_used' => $data['usage']['total_tokens'] ?? null,
        ];
    }

    private function callAnthropic(array $messages, Setting $settings): array
    {
        $apiKey = trim($settings->claude_api_key ?? $settings->openai_api_key ?? '');
        $model = $settings->claude_model ?: 'claude-sonnet-4-5-20250929';
        $maxTokens = (int) ($settings->ai_chat_max_tokens ?: 1024);
        $temperature = min((float) ($settings->ai_chat_temperature ?: 0.7), 0.45);

        $system = '';
        $chatMessages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system .= $msg['content'] . "\n";
            } else {
                $chatMessages[] = $msg;
            }
        }

        $body = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => $chatMessages,
        ];

        if ($system) {
            $body['system'] = trim($system);
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', $body);

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('AI Chat Anthropic Error', ['error' => $data['error']]);
            throw new \Exception($data['error']['message'] ?? 'Anthropic API error');
        }

        $tokensUsed = ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0);

        return [
            'content' => trim($data['content'][0]['text'] ?? ''),
            'tokens_used' => $tokensUsed,
        ];
    }
}
