<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Vendor;
use App\Support\ProductSellerPublishStatus;
use App\Support\SellerProductSoftDelete;
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
        $forcedAction = $this->detectForcedAction($message, $history);

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

            // Model cevap vermese bile net toplu mağaza komutunu çalıştır
            if ($forcedAction) {
                $result = $this->executeAction($seller, $forcedAction);
                $reply = $result['error']
                    ? ('⚠️ '.$result['error'])
                    : ('✅ '.($result['summary'] ?? 'İşlem tamamlandı.'));
                $history[] = ['role' => 'user', 'content' => $message];
                $history[] = ['role' => 'assistant', 'content' => $reply];

                return [
                    'reply' => $reply,
                    'action_taken' => $result['summary'],
                    'history' => array_slice($history, -20),
                ];
            }

            return [
                'reply' => 'Şu an yanıt veremiyorum. Lütfen biraz sonra tekrar deneyin.',
                'action_taken' => null,
                'history' => $history,
            ];
        }

        $action = $this->extractAction($raw) ?? $forcedAction;
        $reply = $this->stripActionBlock($raw);
        $reply = $this->promptGuard->sanitizeOutput($reply, 'seller');
        $actionTaken = null;

        if ($action) {
            $result = $this->executeAction($seller, $action);
            $actionTaken = $result['summary'];
            if ($result['summary']) {
                $reply = trim($reply."\n\n✅ ".$result['summary']);
            }
            if ($result['error']) {
                $reply = trim($reply."\n\n⚠️ ".$result['error']);
            }
        } elseif ($this->looksLikeUnsupportedPanelRedirect($reply)) {
            $reply = trim($reply."\n\nNot: Desteklenen mağaza işlemlerini (ürün pasife/yayına alma, fiyat, stok) burada doğrudan yapabilirim. Komutu net yazmanız yeterli.");
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
            ->limit(80)
            ->get(['id', 'name', 'sku', 'barcode', 'price', 'offer_price', 'qty', 'status', 'approve_by_admin', 'thumb_image']);

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
                'sku' => (string) ($p->sku ?? ''),
                'barcode' => (string) ($p->barcode ?? ''),
                'price' => (float) $p->price,
                'offer_price' => (float) $p->offer_price,
                'qty' => (int) $p->qty,
                'status' => (int) $p->status === 1 ? 'yayinda' : 'pasif',
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

Sen Kuaför Tedarik satıcı paneli AI asistanısın. Sadece bu satıcının ({$shop}) mağazasına yardım edersin. Türkçe, kısa ve net konuş.

Satıcı verileri (yalnızca bu mağaza):
- Toplam ürün: {$context['product_count']} (yayında: {$context['published_count']}, pasif: {$context['draft_count']})
- Bugünkü sipariş: {$context['today_orders']}
- Bekleyen sipariş: {$context['pending_orders']}

Örnek ürün listesi (eşleştirme için; toplu işlemde tüm mağaza ürünleri kullanılır):
{$productsJson}

Yapabileceklerin (HEPSİNİ sen uygularsın — "panelden yapın" DEME):
1. Bilgi: stok, sipariş özeti, ürün sayısı (sadece bu mağaza)
2. Tek ürün güncelle: fiyat, indirimli fiyat, stok, ad, kısa/uzun açıklama, yayına al/pasife al
3. Toplu durum: tüm yayındakileri pasife al; tüm pasifleri (görseli olanları) yayına al
4. Ürün sil (soft): SKU / barkod / SN / ürün adı veya id ile — satıcı listesinden kalkar, admin kaydı görür

Tek ürün ACTION:
<!--ACTION{"type":"update_product","product_id":0,"product_name":"","sku":"","barcode":"","fields":{"price":0,"offer_price":0,"qty":0,"status":0}}-->

Silme ACTION:
<!--ACTION{"type":"delete_product","product_id":0,"sku":"","barcode":"","product_name":""}-->

Toplu durum ACTION:
<!--ACTION{"type":"bulk_set_status","status":0,"scope":"published"}-->
scope: "published" | "draft" | "all"
status: 0 = pasif (satıcı pasifi), 1 = yayında
NOT: Pasife alırken approve/admin kilidi koyma. Admin kilidi varsa yayına alma.

ZORUNLU KURALLAR:
- Satıcı "pasife al / sil" derse ACTION ekle; paneli önerme
- product_id, sku, barcode (SN) biliniyorsa kullan; yoksa product_name
- Başka satıcının ürününe erişme
- Kalıcı hard-delete yok — soft silme kullan
PROMPT;
    }

    /**
     * Net toplu komutlarda model ACTION üretmese bile işlemi uygula.
     *
     * @param  list<array{role:string,content:string}>  $history
     */
    private function detectForcedAction(string $message, array $history = []): ?array
    {
        $text = Str::lower(Str::ascii($message));
        $prev = '';
        foreach (array_reverse($history) as $item) {
            if (($item['role'] ?? '') === 'assistant') {
                $prev = Str::lower(Str::ascii((string) ($item['content'] ?? '')));
                break;
            }
        }

        $wantsPassive = (bool) preg_match('/pasif|taslak\s*(yap|al|et)|yayindan\s*kaldir|yayin\s*kapat/u', $text);
        $wantsPublish = (bool) preg_match('/yayina\s*al|yayinla|aktif\s*(et|hale|yap)/u', $text);
        $wantsDelete = (bool) preg_match('/\b(sil|kaldir|remove|delete)\b/u', $text);
        $allScope = (bool) preg_match('/\b(tum|tumu|hepsi|hepsini|butun|butunu)\b/u', $text)
            || (bool) preg_match('/yayinda/u', $text)
            || (bool) preg_match('/\b(tum|tumu|hepsi|hepsini)\b|yayinda|2629|urunleriniz var/u', $prev);

        if ($wantsDelete && ! $allScope) {
            $code = null;
            if (preg_match('/(?:sku|barkod|sn|kod)\s*[:=]?\s*([0-9A-Za-z\-]{4,})/u', $text, $m)) {
                $code = $m[1];
            } elseif (preg_match('/\b([0-9]{8,14})\b/', $text, $m)) {
                $code = $m[1];
            }
            if ($code) {
                return ['type' => 'delete_product', 'sku' => $code, 'barcode' => $code];
            }
        }

        if ($wantsPassive && $allScope) {
            return ['type' => 'bulk_set_status', 'status' => 0, 'scope' => 'published'];
        }

        if ($wantsPublish && $allScope) {
            return ['type' => 'bulk_set_status', 'status' => 1, 'scope' => 'draft'];
        }

        // Önceki turda "yayındakileri pasife" konuşulduysa: "sen yap / sen pasif hale getir"
        if ($wantsPassive && (bool) preg_match('/\b(sen|kendin|gerceklestir|uygula)\b/u', $text)
            && (bool) preg_match('/pasif|yayinda|panelden|urun/u', $prev)) {
            return ['type' => 'bulk_set_status', 'status' => 0, 'scope' => 'published'];
        }

        return null;
    }

    private function looksLikeUnsupportedPanelRedirect(string $reply): bool
    {
        $t = Str::lower(Str::ascii($reply));

        return str_contains($t, 'panelden')
            && (
                str_contains($t, 'yapamam')
                || str_contains($t, 'yapamiyor')
                || str_contains($t, 'gerceklestiremi')
                || str_contains($t, 'yapmaniz gerekir')
                || str_contains($t, 'islemi yap')
            );
    }

    /**
     * @return array{summary:?string,error:?string}
     */
    private function executeAction(Vendor $seller, array $action): array
    {
        $type = $action['type'] ?? '';

        if ($type === 'bulk_set_status') {
            return $this->executeBulkSetStatus($seller, $action);
        }

        if ($type === 'delete_product') {
            return $this->executeDeleteProduct($seller, $action);
        }

        if ($type !== 'update_product') {
            return ['summary' => null, 'error' => 'Bu işlem desteklenmiyor.'];
        }

        $product = $this->findSellerProduct($seller, $action);
        if (! $product) {
            return ['summary' => null, 'error' => 'Ürün bulunamadı. Lütfen ürün adı, SKU veya barkod yazın.'];
        }

        $fields = $action['fields'] ?? [];
        $changes = [];
        $publishStatus = app(ProductSellerPublishStatus::class);

        if (isset($fields['price']) && is_numeric($fields['price'])) {
            $product->price = (float) $fields['price'];
            $changes[] = 'fiyat '.$fields['price'].' ₺';
        }
        if (array_key_exists('offer_price', $fields) && $fields['offer_price'] !== '' && is_numeric($fields['offer_price'])) {
            $product->offer_price = (float) $fields['offer_price'];
            $changes[] = 'indirimli fiyat '.$fields['offer_price'].' ₺';
        }
        if (isset($fields['qty']) && is_numeric($fields['qty'])) {
            $product->qty = (int) $fields['qty'];
            $changes[] = 'stok '.$fields['qty'];
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
            if ((int) $fields['status'] === 1) {
                if ($publishStatus->isBlockedByAdmin($product)) {
                    return ['summary' => null, 'error' => 'Bu ürün admin tarafından pasife alındı; yayına alınamaz.'];
                }
                $issues = $publishStatus->issues($product);
                if ($issues !== []) {
                    return ['summary' => null, 'error' => 'Yayına almak için eksikleri tamamlayın: '.implode(', ', $issues)];
                }
                $product->status = 1;
                if ((int) $product->approve_by_admin === 0) {
                    $product->approve_by_admin = 1;
                }
                $changes[] = 'yayına alındı';
            } else {
                $product->status = 0;
                $changes[] = 'pasife alındı';
            }
        }

        if ($changes === []) {
            return ['summary' => null, 'error' => 'Güncellenecek alan belirlenemedi.'];
        }

        $product->save();

        return [
            'summary' => '"'.$product->name.'" güncellendi: '.implode(', ', $changes).'.',
            'error' => null,
        ];
    }

    /**
     * @return array{summary:?string,error:?string}
     */
    private function executeDeleteProduct(Vendor $seller, array $action): array
    {
        $product = $this->findSellerProduct($seller, $action);
        if (! $product) {
            return ['summary' => null, 'error' => 'Silinecek ürün bulunamadı. SKU, barkod (SN) veya ürün adı yazın.'];
        }

        if (app(ProductSellerPublishStatus::class)->isBlockedByAdmin($product)) {
            return ['summary' => null, 'error' => 'Admin tarafından pasife alınan ürün silinemez.'];
        }

        $name = $product->name;
        app(SellerProductSoftDelete::class)->hide($product);

        return [
            'summary' => '"'.$name.'" satıcı listesinden kaldırıldı (admin kaydı duruyor).',
            'error' => null,
        ];
    }

    /**
     * @return array{summary:?string,error:?string}
     */
    private function executeBulkSetStatus(Vendor $seller, array $action): array
    {
        $status = (int) ($action['status'] ?? -1);
        if (! in_array($status, [0, 1], true)) {
            return ['summary' => null, 'error' => 'Geçersiz durum. status 0 (pasif) veya 1 (yayında) olmalı.'];
        }

        $scope = strtolower(trim((string) ($action['scope'] ?? ($status === 0 ? 'published' : 'draft'))));
        if (! in_array($scope, ['published', 'draft', 'all'], true)) {
            $scope = $status === 0 ? 'published' : 'draft';
        }

        $query = Product::query()->where('vendor_id', $seller->id);
        if ($scope === 'published') {
            $query->where('status', 1);
        } elseif ($scope === 'draft') {
            $query->where('status', 0);
        }

        $ids = $query->pluck('id');
        if ($ids->isEmpty()) {
            return [
                'summary' => $status === 0
                    ? 'Pasife alınacak yayında ürün bulunamadı.'
                    : 'Yayına alınacak pasif ürün bulunamadı.',
                'error' => null,
            ];
        }

        $skippedNoImage = 0;
        $skippedAdmin = 0;
        if ($status === 1) {
            $publishStatus = app(ProductSellerPublishStatus::class);
            $candidates = Product::query()
                ->where('vendor_id', $seller->id)
                ->whereIn('id', $ids)
                ->get();
            $allowed = [];
            foreach ($candidates as $p) {
                if ($publishStatus->isBlockedByAdmin($p)) {
                    $skippedAdmin++;
                    continue;
                }
                if ($publishStatus->issues($p) !== []) {
                    $skippedNoImage++;
                    continue;
                }
                $allowed[] = (int) $p->id;
            }
            $ids = collect($allowed);
            if ($ids->isEmpty()) {
                return [
                    'summary' => null,
                    'error' => $skippedAdmin > 0
                        ? 'Yayına alınacak uygun ürün yok (bazıları admin kilidi veya eksik bilgi).'
                        : 'Yayına alınacak ürünlerde görsel/bilgi eksik.',
                ];
            }
        }

        if ($status === 1) {
            $updated = Product::query()
                ->where('vendor_id', $seller->id)
                ->whereIn('id', $ids)
                ->update(['status' => 1, 'approve_by_admin' => 1]);
        } else {
            $updated = Product::query()
                ->where('vendor_id', $seller->id)
                ->whereIn('id', $ids)
                ->update(['status' => 0]);
        }

        $label = $status === 1 ? 'yayına alındı' : 'pasife alındı';
        $summary = "{$updated} ürün {$label} (yalnızca sizin mağazanız).";
        if ($skippedNoImage > 0) {
            $summary .= " {$skippedNoImage} ürün eksik bilgi/görsel nedeniyle atlandı.";
        }
        if ($skippedAdmin > 0) {
            $summary .= " {$skippedAdmin} ürün admin kilidi nedeniyle atlandı.";
        }

        return ['summary' => $summary, 'error' => null];
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

        $sku = trim((string) ($action['sku'] ?? $action['sn'] ?? ''));
        if ($sku !== '') {
            $bySku = Product::query()
                ->where('vendor_id', $seller->id)
                ->where(function ($q) use ($sku) {
                    $q->where('sku', $sku)->orWhere('barcode', $sku);
                })
                ->first();
            if ($bySku) {
                return $bySku;
            }
        }

        $barcode = trim((string) ($action['barcode'] ?? ''));
        if ($barcode !== '') {
            $byBarcode = Product::query()
                ->where('vendor_id', $seller->id)
                ->where(function ($q) use ($barcode) {
                    $q->where('barcode', $barcode)->orWhere('sku', $barcode);
                })
                ->first();
            if ($byBarcode) {
                return $byBarcode;
            }
        }

        $nameQuery = trim((string) ($action['product_name'] ?? ''));
        if ($nameQuery === '') {
            return null;
        }

        if (preg_match('/^[0-9A-Za-z\-]{6,}$/', $nameQuery)) {
            $byCode = Product::query()
                ->where('vendor_id', $seller->id)
                ->where(function ($q) use ($nameQuery) {
                    $q->where('sku', $nameQuery)->orWhere('barcode', $nameQuery);
                })
                ->first();
            if ($byCode) {
                return $byCode;
            }
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

        if (preg_match('/\{[\s\S]*"type"\s*:\s*"(?:update_product|bulk_set_status|delete_product)"[\s\S]*\}/', $raw, $m)) {
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
