<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Support\ProductSellerPublishStatus;
use App\Support\SellerProductSoftDelete;
use App\Support\SellerAiCatalogHelper;
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
            ->with(['category:id,name', 'subCategory:id,name', 'childCategory:id,name'])
            ->orderByDesc('id')
            ->limit(80)
            ->get(['id', 'name', 'sku', 'barcode', 'price', 'offer_price', 'qty', 'status', 'approve_by_admin', 'thumb_image', 'category_id', 'sub_category_id', 'child_category_id', 'seo_title']);

        $todayOrders = Order::query()
            ->whereHas('orderProducts', fn ($q) => $q->where('seller_id', $seller->id))
            ->whereDate('created_at', today())
            ->count();

        $pendingOrders = Order::query()
            ->where('order_status', 0)
            ->whereHas('orderProducts', fn ($q) => $q->where('seller_id', $seller->id))
            ->count();

        $categoryTree = Category::query()
            ->where('status', 1)
            ->with(['activeSubCategories.activeChildCategories'])
            ->ordered()
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'subs' => $c->activeSubCategories->take(30)->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'children' => $s->activeChildCategories->take(30)->map(fn ($ch) => [
                        'id' => $ch->id,
                        'name' => $ch->name,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all();

        return [
            'shop_name' => $seller->shop_name ?? 'Mağaza',
            'product_count' => Product::where('vendor_id', $seller->id)->count(),
            'published_count' => Product::where('vendor_id', $seller->id)->where('status', 1)->count(),
            'draft_count' => Product::where('vendor_id', $seller->id)->where('status', 0)->count(),
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders,
            'categories' => $categoryTree,
            'products' => $products->values()->map(fn (Product $p, int $i) => [
                'sn' => $i + 1,
                'id' => $p->id,
                'name' => $p->name,
                'sku' => (string) ($p->sku ?? ''),
                'barcode' => (string) ($p->barcode ?? ''),
                'category' => $p->category?->name,
                'sub_category' => $p->subCategory?->name,
                'child_category' => $p->childCategory?->name,
                'has_seo' => trim((string) ($p->seo_title ?? '')) !== '',
                'price' => (float) $p->price,
                'offer_price' => (float) $p->offer_price,
                'qty' => (int) $p->qty,
                'status' => (int) $p->status === 1 ? 'yayinda' : 'pasif',
            ])->all(),
        ];
    }

    private function buildSystemPrompt(array $context): string
    {
        $productsJson = json_encode($context['products'], JSON_UNESCAPED_UNICODE);
        $categoriesJson = json_encode($context['categories'] ?? [], JSON_UNESCAPED_UNICODE);
        $shop = $context['shop_name'];
        $security = $this->promptGuard->sellerSecuritySystemPrompt();

        return <<<PROMPT
{$security}

Sen Kuaför Tedarik satıcı paneli AI asistanısın. Sadece bu satıcının ({$shop}) mağazasına yardım edersin. Türkçe, kısa ve net konuş.

Satıcı verileri (yalnızca bu mağaza):
- Toplam ürün: {$context['product_count']} (yayında: {$context['published_count']}, pasif: {$context['draft_count']})
- Bugünkü sipariş: {$context['today_orders']}
- Bekleyen sipariş: {$context['pending_orders']}

Platform kategori ağacı (doğru kategoriye yerleştirmek için):
{$categoriesJson}

Örnek ürün listesi (SN = panel sıra numarası):
{$productsJson}

Yapabileceklerin (HEPSİNİ sen uygularsın — "panelden yapın" DEME):
1. Bilgi: stok, sipariş, ürün sayısı
2. Tek ürün güncelle: fiyat, indirim, stok, ad, açıklama, yayına/pasife, kategori, SEO
3. Toplu durum: tüm veya kategori/alt/child bazlı yayına al / pasife al
4. Ürün sil (soft): SN / SKU / barkod / ad
5. Kategori yerleştir: ürünü doğru kategori + alt + child'a taşı
6. SEO üret/güncelle: tek ürün veya kategori/tüm mağaza

Tek ürün ACTION:
<!--ACTION{"type":"update_product","sn":0,"product_id":0,"product_name":"","sku":"","fields":{"price":0,"offer_price":0,"qty":0,"status":0,"category_name":"","sub_category_name":"","child_category_name":"","seo_title":"","seo_description":"","generate_seo":true}}-->

Kategori ata:
<!--ACTION{"type":"set_category","sn":0,"product_name":"","category_name":"Makas","sub_category_name":"","child_category_name":""}-->

SEO:
<!--ACTION{"type":"generate_seo","sn":0,"product_name":"","scope":"one"}-->
<!--ACTION{"type":"generate_seo","scope":"category","category_name":"Makas"}-->
<!--ACTION{"type":"generate_seo","scope":"all"}-->

Silme:
<!--ACTION{"type":"delete_product","sn":5}-->

Toplu durum:
<!--ACTION{"type":"bulk_set_status","status":1,"scope":"draft","category_name":"Makas","sub_category_name":"","child_category_name":""}-->
status 0=pasif 1=yayında | scope published|draft|all
Kategori verilirse yalnız o kategori/alt/child etkilenir.

ZORUNLU:
- Kategori adını ağaçtan eşleştir
- "X kategorisini yayına/pasife al" → bulk_set_status + category_name
- SEO isteğinde generate_seo ACTION
- SN = sıra no (SKU değil)
- Admin kilidini açma
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
            // Panel SN = sıra numarası (1,2,3…), SKU değil
            if (preg_match('/(?:sira\s*numara(?:si)?|sira\s*no|\bsn\b)\s*[:=#]?\s*(\d{1,6})\b/u', $text, $m)
                || preg_match('/\b(\d{1,6})\s*(?:\.|inci|nci|uncu|uncu)?\s*sira/u', $text, $m)) {
                return ['type' => 'delete_product', 'sn' => (int) $m[1]];
            }

            $code = null;
            if (preg_match('/(?:sku|barkod|kod)\s*[:=]?\s*([0-9A-Za-z\-]{4,})/u', $text, $m)) {
                $code = $m[1];
            } elseif (preg_match('/\b([0-9]{8,14})\b/', $text, $m)) {
                $code = $m[1];
            }
            if ($code) {
                return ['type' => 'delete_product', 'sku' => $code, 'barcode' => $code];
            }
        }

        $wantsSeo = (bool) preg_match('/\bseo\b|meta\s*baslik|meta\s*aciklama|arama\s*motor/u', $text);
        $categoryHint = null;
        if (preg_match('/([\w\sçğıöşüÇĞİÖŞÜ\-]{2,60}?)\s*(?:kategor(?:i|isi|isini|isindeki)|alt\s*kategor|child)/u', $message, $m)) {
            $categoryHint = trim(preg_replace('/\b(tum|tumu|bu|su|olan|urunleri|urunlerin|urunler)\b/iu', '', $m[1]));
            $categoryHint = trim($categoryHint);
        } elseif (preg_match('/([a-z0-9\s\-]{2,40}?)\s*kategor/u', $text, $m)) {
            $categoryHint = trim(preg_replace('/\b(tum|tumu|bu|su|olan|urunleri|urunlerin|urunler)\b/u', '', $m[1]));
        }

        if ($wantsSeo && ! $wantsDelete) {
            if ($categoryHint) {
                return ['type' => 'generate_seo', 'scope' => 'category', 'category_name' => $categoryHint];
            }
            if ($allScope || (bool) preg_match('/\b(tum|hepsi|butun)\b/u', $text)) {
                return ['type' => 'generate_seo', 'scope' => 'all'];
            }
        }

        if (($wantsPassive || $wantsPublish) && $categoryHint) {
            return [
                'type' => 'bulk_set_status',
                'status' => $wantsPublish ? 1 : 0,
                'scope' => $wantsPublish ? 'draft' : 'published',
                'category_name' => $categoryHint,
            ];
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

        if ($type === 'set_category') {
            return $this->executeSetCategory($seller, $action);
        }

        if ($type === 'generate_seo') {
            return $this->executeGenerateSeo($seller, $action);
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
        $catalog = app(SellerAiCatalogHelper::class);
        $resolvedCat = $catalog->resolveFromAction($action, $fields);
        if ($resolvedCat) {
            $product->category_id = (int) $resolvedCat['category_id'];
            $product->sub_category_id = (int) ($resolvedCat['sub_category_id'] ?? 0);
            $product->child_category_id = (int) ($resolvedCat['child_category_id'] ?? 0);
            $changes[] = 'kategori: '.$resolvedCat['label'];
        }

        $wantSeo = ! empty($fields['generate_seo'])
            || ! empty($fields['seo_title'])
            || ! empty($fields['seo_description'])
            || array_key_exists('seo_title', $fields)
            || array_key_exists('seo_description', $fields);
        if ($wantSeo) {
            $seo = app(ProductSeoAutoFill::class)->resolve(
                $fields['seo_title'] ?? $product->seo_title,
                $fields['seo_description'] ?? $product->seo_description,
                (string) $product->name,
                $product->short_description,
                $product->long_description
            );
            $product->seo_title = $seo['seo_title'];
            $product->seo_description = $seo['seo_description'];
            $changes[] = 'SEO güncellendi';
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
            return ['summary' => null, 'error' => 'Silinecek ürün bulunamadı. Sıra numarası (SN), SKU, barkod veya ürün adı yazın.'];
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

        $catalog = app(SellerAiCatalogHelper::class);
        $resolvedCat = $catalog->resolveFromAction($action);
        $catLabel = '';
        if ($resolvedCat) {
            $catalog->applyToQuery($query, $resolvedCat);
            $catLabel = $resolvedCat['label'];
        } elseif (trim((string) ($action['category_name'] ?? $action['sub_category_name'] ?? $action['child_category_name'] ?? '')) !== '') {
            return ['summary' => null, 'error' => 'Kategori bulunamadı. Lütfen kategori adını net yazın.'];
        }

        $ids = $query->pluck('id');
        if ($ids->isEmpty()) {
            $where = $catLabel !== '' ? ('"'.$catLabel.'" kategorisinde ') : '';
            return [
                'summary' => $status === 0
                    ? ($where.'pasife alınacak yayında ürün bulunamadı.')
                    : ($where.'yayına alınacak pasif ürün bulunamadı.'),
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
        $summary = "{$updated} ürün {$label} (yalnızca sizin mağazanız".($catLabel !== '' ? ', kategori: '.$catLabel : '').").";
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

        // Panel "SN / sıra numarası" = id DESC listedeki 1-based sıra (SKU değil)
        $sn = (int) ($action['sn'] ?? $action['sira_no'] ?? $action['row'] ?? 0);
        if ($sn > 0) {
            $bySn = $this->findSellerProductByListSn($seller, $sn);
            if ($bySn) {
                return $bySn;
            }
        }

        $sku = trim((string) ($action['sku'] ?? ''));
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

            // "sn:12" yanlışlıkla sku gelirse küçük sayıyı sıra no dene
            if (ctype_digit($sku) && (int) $sku > 0 && (int) $sku < 100000) {
                $bySn = $this->findSellerProductByListSn($seller, (int) $sku);
                if ($bySn) {
                    return $bySn;
                }
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

        // "5. ürün" / sadece sayı → sıra no
        if (preg_match('/^(\d{1,6})(?:\s*\.?\s*urun)?$/u', $nameQuery, $m)) {
            $bySn = $this->findSellerProductByListSn($seller, (int) $m[1]);
            if ($bySn) {
                return $bySn;
            }
        }

        $products = Product::query()
            ->where('vendor_id', $seller->id)
            ->get(['id', 'name']);

        return $this->fuzzyProductMatch($nameQuery, $products);
    }

    /**
     * @return array{summary:?string,error:?string}
     */
    private function executeSetCategory(Vendor $seller, array $action): array
    {
        $product = $this->findSellerProduct($seller, $action);
        if (! $product) {
            return ['summary' => null, 'error' => 'Ürün bulunamadı.'];
        }

        $resolved = app(SellerAiCatalogHelper::class)->resolveFromAction($action);
        if (! $resolved || empty($resolved['category_id'])) {
            return ['summary' => null, 'error' => 'Kategori bulunamadı. Platformdaki kategori adını yazın.'];
        }

        $product->category_id = (int) $resolved['category_id'];
        $product->sub_category_id = (int) ($resolved['sub_category_id'] ?? 0);
        $product->child_category_id = (int) ($resolved['child_category_id'] ?? 0);
        $product->save();

        return [
            'summary' => '"'.$product->name.'" kategorisi güncellendi: '.$resolved['label'].'.',
            'error' => null,
        ];
    }

    /**
     * @return array{summary:?string,error:?string}
     */
    private function executeGenerateSeo(Vendor $seller, array $action): array
    {
        $scope = strtolower(trim((string) ($action['scope'] ?? 'one')));
        $seoService = app(ProductSeoAutoFill::class);
        $catalog = app(SellerAiCatalogHelper::class);

        if ($scope === 'one' || isset($action['sn']) || isset($action['product_id']) || isset($action['product_name']) || isset($action['sku'])) {
            $product = $this->findSellerProduct($seller, $action);
            if (! $product) {
                return ['summary' => null, 'error' => 'SEO için ürün bulunamadı.'];
            }
            $seo = $seoService->resolve(null, null, (string) $product->name, $product->short_description, $product->long_description);
            $product->seo_title = $seo['seo_title'];
            $product->seo_description = $seo['seo_description'];
            $product->save();

            return [
                'summary' => '"'.$product->name.'" SEO güncellendi: '.$seo['seo_title'],
                'error' => null,
            ];
        }

        $query = Product::query()->where('vendor_id', $seller->id)->orderByDesc('id')->limit(80);
        $label = 'mağaza';
        if ($scope === 'category') {
            $resolved = $catalog->resolveFromAction($action);
            if (! $resolved) {
                return ['summary' => null, 'error' => 'SEO için kategori bulunamadı.'];
            }
            $catalog->applyToQuery($query, $resolved);
            $label = $resolved['label'];
        }

        $products = $query->get();
        if ($products->isEmpty()) {
            return ['summary' => null, 'error' => 'SEO uygulanacak ürün yok.'];
        }

        $n = 0;
        foreach ($products as $product) {
            $seo = $seoService->resolve(null, null, (string) $product->name, $product->short_description, $product->long_description);
            $product->seo_title = $seo['seo_title'];
            $product->seo_description = $seo['seo_description'];
            $product->save();
            $n++;
        }

        return [
            'summary' => "{$n} ürün için SEO güncellendi ({$label}).",
            'error' => null,
        ];
    }

    /** Satıcı ürün listesi ile aynı sıra: orderByDesc(id), 1-based SN */
    private function findSellerProductByListSn(Vendor $seller, int $sn): ?Product
    {
        if ($sn < 1) {
            return null;
        }

        return Product::query()
            ->where('vendor_id', $seller->id)
            ->orderByDesc('id')
            ->skip($sn - 1)
            ->take(1)
            ->first();
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

        if (preg_match('/\{[\s\S]*"type"\s*:\s*"(?:update_product|bulk_set_status|delete_product|set_category|generate_seo)"[\s\S]*\}/', $raw, $m)) {
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
