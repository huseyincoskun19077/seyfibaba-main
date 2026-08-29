<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SellerAiAssistantService
{
    public function __construct(
        private AiChatPromptGuard $promptGuard,
    ) {}

    /** @var list<array{role:string,content:string}> */
    private array $sessionHistory = [];

    /**
     * @param  list<array{role:string,content:string}>  $history
     * @return array{reply:string, action_taken:?string, history:list<array{role:string,content:string}>}
     */
    public function chat(Vendor $seller, string $message, array $history = []): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['reply' => 'Lütfen bir mesaj yazın.', 'action_taken' => null, 'history' => $history];
        }

        $blockReason = $this->promptGuard->evaluateInput($message);
        if ($blockReason !== null) {
            $this->promptGuard->logBlockedInput($message, $blockReason, 'seller:'.$seller->id, $seller->user_id ?? null);
            $safeReply = $this->promptGuard->refusalMessage($blockReason, 'seller');

            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $safeReply];

            return [
                'reply' => $safeReply,
                'action_taken' => null,
                'history' => array_slice($history, -20),
            ];
        }

        $setting = Setting::first();
        if (! $setting || (! $setting->openai_enabled && ! $setting->claude_enabled)) {
            return [
                'reply' => 'AI asistan şu an kapalı. Admin panelden AI ayarlarını açın.',
                'action_taken' => null,
                'history' => $history,
            ];
        }

        $context = $this->buildSellerContext($seller);
        $systemPrompt = $this->buildSystemPrompt($context);

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach (array_slice($history, -12) as $item) {
            if (in_array($item['role'] ?? '', ['user', 'assistant'], true)) {
                $messages[] = ['role' => $item['role'], 'content' => $item['content']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $raw = $this->callAi($setting, $messages);
        } catch (\Throwable $e) {
            Log::error('Seller AI assistant failed', ['message' => $e->getMessage()]);

            return [
                'reply' => 'Şu an yanıt veremiyorum. Lütfen biraz sonra tekrar deneyin.',
                'action_taken' => null,
                'history' => $history,
            ];
        }

        $action = $this->extractAction($raw);
        $reply = $this->stripActionBlock($raw);
        $reply = $this->promptGuard->sanitizeOutput($reply, 'seller');
        $actionTaken = null;

        if ($action) {
            $result = $this->executeAction($seller, $action);
            $actionTaken = $result['summary'];
            if ($result['summary']) {
                $reply = trim($reply . "\n\n✅ " . $result['summary']);
            }
            if ($result['error']) {
                $reply = trim($reply . "\n\n⚠️ " . $result['error']);
            }
        }

        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $reply];

        return [
            'reply' => $reply,
            'action_taken' => $actionTaken,
            'history' => array_slice($history, -20),
        ];
    }

    private function buildSellerContext(Vendor $seller): array
    {
        $products = Product::query()
            ->where('vendor_id', $seller->id)
            ->orderByDesc('id')
            ->limit(60)
            ->get(['id', 'name', 'price', 'offer_price', 'qty', 'status']);

        $todayOrders = Order::query()
            ->whereHas('orderProducts', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereDate('created_at', today())
            ->count();

        $pendingOrders = Order::query()
            ->where('order_status', 0)
            ->whereHas('orderProducts', fn ($q) => $q->where('seller_id', $seller->id))
            ->count();

        return [
            'shop_name' => $seller->shop_name ?? 'Mağaza',
            'product_count' => Product::where('vendor_id', $seller->id)->count(),
            'published_count' => Product::where('vendor_id', $seller->id)->where('status', 1)->count(),
            'draft_count' => Product::where('vendor_id', $seller->id)->where('status', 0)->count(),
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders,
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->price,
                'offer_price' => (float) $p->offer_price,
                'qty' => (int) $p->qty,
                'status' => (int) $p->status === 1 ? 'yayinda' : 'taslak',
            ])->values()->all(),
        ];
    }

    private function buildSystemPrompt(array $context): string
    {
        $productsJson = json_encode($context['products'], JSON_UNESCAPED_UNICODE);
        $shop = $context['shop_name'];
        $security = $this->promptGuard->sellerSecuritySystemPrompt();

        return <<<PROMPT
{$security}

Sen Seyfibaba satıcı paneli AI asistanısın. Sadece bu satıcının ({$shop}) mağazasına yardım edersin. Türkçe, kısa ve net konuş.

Satıcı verileri:
- Toplam ürün: {$context['product_count']} (yayında: {$context['published_count']}, taslak: {$context['draft_count']})
- Bugünkü sipariş: {$context['today_orders']}
- Bekleyen sipariş: {$context['pending_orders']}

Ürün listesi (SADECE bu ürünlerde işlem yapabilirsin):
{$productsJson}

Yapabileceklerin:
1. Soruları yanıtla (stok, sipariş, ürün sayısı)
2. Ürün güncelle: fiyat, indirimli fiyat, stok (qty), kısa/uzun açıklama, ürün adı, yayına al/kapat (status)

Ürün güncellemesi gerektiğinde yanıtının SONUNA şu JSON bloğunu ekle (başka yerde kullanma):
<!--ACTION{"type":"update_product","product_id":0,"product_name":"ürün adı parçası","fields":{"price":0,"offer_price":0,"qty":0,"short_description":"","long_description":"","name":"","status":1}}-->

Kurallar:
- product_id biliniyorsa kullan, yoksa product_name ile eşleştir
- fields içinde SADECE değiştirilmesi istenen alanları koy
- Başka satıcının ürününe erişemezsin
- Ürün silme — yönlendir: panelden silsin
- Hızlı ürün: /seller/product/quick-create | Toplu Excel: /seller/product-import-page
PROMPT;
    }

    /**
     * @return array{summary:?string,error:?string}
     */
    private function executeAction(Vendor $seller, array $action): array
    {
        $type = $action['type'] ?? '';

        if ($type !== 'update_product') {
            return ['summary' => null, 'error' => 'Bu işlem desteklenmiyor.'];
        }

        $product = $this->findSellerProduct($seller, $action);
        if (! $product) {
            return ['summary' => null, 'error' => 'Ürün bulunamadı. Lütfen ürün adını daha net yazın.'];
        }

        $fields = $action['fields'] ?? [];
        $changes = [];

        if (isset($fields['price']) && is_numeric($fields['price'])) {
            $product->price = (float) $fields['price'];
            $changes[] = 'fiyat ' . $fields['price'] . ' ₺';
        }
        if (array_key_exists('offer_price', $fields) && $fields['offer_price'] !== '' && is_numeric($fields['offer_price'])) {
            $product->offer_price = (float) $fields['offer_price'];
            $changes[] = 'indirimli fiyat ' . $fields['offer_price'] . ' ₺';
        }
        if (isset($fields['qty']) && is_numeric($fields['qty'])) {
            $product->qty = (int) $fields['qty'];
            $changes[] = 'stok ' . $fields['qty'];
        }
        if (! empty($fields['name'])) {
            $product->name = Str::limit($fields['name'], 500, '');
            $changes[] = 'ad güncellendi';
        }
        if (! empty($fields['short_description'])) {
            $product->short_description = $fields['short_description'];
            $changes[] = 'kısa açıklama güncellendi';
        }
        if (! empty($fields['long_description'])) {
            $product->long_description = $fields['long_description'];
            $changes[] = 'açıklama güncellendi';
        }
        if (isset($fields['status']) && in_array((int) $fields['status'], [0, 1], true)) {
            if ((int) $fields['status'] === 1 && empty($product->thumb_image)) {
                return ['summary' => null, 'error' => 'Görsel olmadan ürün yayına alınamaz. Önce fotoğraf ekleyin.'];
            }
            $product->status = (int) $fields['status'];
            $product->approve_by_admin = (int) $fields['status'] === 1 ? 1 : 0;
            $changes[] = (int) $fields['status'] === 1 ? 'yayına alındı' : 'taslak yapıldı';
        }

        if ($changes === []) {
            return ['summary' => null, 'error' => 'Güncellenecek alan belirlenemedi.'];
        }

        $product->save();

        return [
            'summary' => '"' . $product->name . '" güncellendi: ' . implode(', ', $changes) . '.',
            'error' => null,
        ];
    }

    private function findSellerProduct(Vendor $seller, array $action): ?Product
    {
        $productId = (int) ($action['product_id'] ?? 0);
        if ($productId > 0) {
            return Product::query()
                ->where('vendor_id', $seller->id)
                ->where('id', $productId)
                ->first();
        }

        $nameQuery = trim((string) ($action['product_name'] ?? ''));
        if ($nameQuery === '') {
            return null;
        }

        $products = Product::query()
            ->where('vendor_id', $seller->id)
            ->get(['id', 'name']);

        return $this->fuzzyProductMatch($nameQuery, $products);
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function fuzzyProductMatch(string $query, Collection $products): ?Product
    {
        $normalized = Str::lower(Str::ascii($query));
        $best = null;
        $bestScore = 0;

        foreach ($products as $product) {
            $name = Str::lower(Str::ascii($product->name));
            if (str_contains($name, $normalized) || str_contains($normalized, $name)) {
                similar_text($name, $normalized, $pct);
                if ($pct > $bestScore) {
                    $bestScore = $pct;
                    $best = $product;
                }
            }
        }

        if ($best && $bestScore >= 40) {
            return Product::find($best->id);
        }

        foreach ($products as $product) {
            similar_text(Str::lower(Str::ascii($product->name)), $normalized, $pct);
            if ($pct > $bestScore) {
                $bestScore = $pct;
                $best = $product;
            }
        }

        return $bestScore >= 55 ? Product::find($best->id) : null;
    }

    private function extractAction(string $raw): ?array
    {
        if (preg_match('/<!--ACTION(\{[\s\S]*?\})-->/', $raw, $m)) {
            $decoded = json_decode($m[1], true);

            return is_array($decoded) ? $decoded : null;
        }

        if (preg_match('/\{[\s\S]*"type"\s*:\s*"update_product"[\s\S]*\}/', $raw, $m)) {
            $decoded = json_decode($m[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function stripActionBlock(string $raw): string
    {
        $text = preg_replace('/<!--ACTION[\s\S]*?-->/', '', $raw) ?? $raw;
        $text = preg_replace('/```json[\s\S]*?```/', '', $text) ?? $text;

        return trim($text);
    }

    /**
     * @param  list<array{role:string,content:string}>  $messages
     */
    private function callAi(Setting $setting, array $messages): string
    {
        if ($setting->openai_enabled) {
            $apiKey = trim($setting->openai_api_key ?? '');
            $endpoint = str_starts_with($apiKey, 'gsk_')
                ? 'https://api.groq.com/openai/v1/chat/completions'
                : 'https://api.openai.com/v1/chat/completions';

            $response = Http::timeout(max(45, (int) ($setting->openai_timeout ?? 45)))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'model' => $setting->openai_model ?? 'gpt-4o-mini',
                    'messages' => $messages,
                    'max_tokens' => 1200,
                    'temperature' => 0.25,
                ]);

            $data = $response->json();
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }
        }

        if ($setting->claude_enabled) {
            $system = '';
            $claudeMessages = [];
            foreach ($messages as $msg) {
                if ($msg['role'] === 'system') {
                    $system = $msg['content'];
                } else {
                    $claudeMessages[] = $msg;
                }
            }

            $payload = [
                'model' => $setting->claude_model ?? 'claude-sonnet-4-5-20250929',
                'max_tokens' => 1200,
                'messages' => $claudeMessages,
            ];
            if ($system !== '') {
                $payload['system'] = $system;
            }

            $response = Http::timeout(max(45, (int) ($setting->claude_timeout ?? 45)))
                ->withHeaders([
                    'x-api-key' => trim($setting->claude_api_key ?? ''),
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])->post('https://api.anthropic.com/v1/messages', $payload);

            $data = $response->json();
            if (isset($data['content'][0]['text'])) {
                return trim($data['content'][0]['text']);
            }
        }

        throw new \RuntimeException('AI yanıt vermedi');
    }
}
