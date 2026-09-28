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

        $extracted = $this->extractAction($raw);
        // Net toplu komutlarda model yanlış/tekil ACTION üretse bile forced intent kazanır
        $action = ($forcedAction && in_array($forcedAction['type'] ?? '', ['bulk_delete_products', 'bulk_set_status', 'bulk_set_category', 'bulk_adjust_price'], true))
            ? $forcedAction
            : ($extracted ?? $forcedAction);
        $reply = $this->stripActionBlock($raw);
        $reply = $this->promptGuard->sanitizeOutput($reply, 'seller');
        $actionTaken = null;

        if ($action) {
            $result = $this->executeAction($seller, $action);
            $actionTaken = $result['summary'];
            if ($result['summary']) {
                if (in_array($action['type'] ?? '', ['bulk_set_category', 'bulk_delete_products', 'bulk_set_status', 'bulk_adjust_price'], true)) {
                    $reply = '✅ '.$result['summary'];
                } else {
                    $reply = trim($reply."\n\n✅ ".$result['summary']);
                }
            }
            if ($result['error']) {
                $reply = trim($reply."\n\n⚠️ ".$result['error']);
            }
        } elseif ($this->looksLikeUnsupportedPanelRedirect($reply)) {
            $reply = trim($reply."\n\nNot: Desteklenen mağaza işlemlerini (ürün pasife/yayına alma, toplu fiyat/indirim, stok, kategori taşıma, renk varyantı) burada doğrudan yapabilirim. Komutu net yazmanız yeterli.");
        } elseif ($this->looksLikeFakeCategorySuccess($reply) && ! $action) {
            $reply = 'Kategori taşıma henüz uygulanmadı. Örnek: «VENÜS LİNE FIRÇA ürünlerini Kuaför Malzemeleri - Fırçalar kategorisine al»';
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
2. Tek ürün güncelle: fiyat, indirim (offer_price), stok, ad, açıklama, yayına/pasife, kategori, SEO
3. Toplu durum: tüm veya kategori/alt/child bazlı yayına al / pasife al
4. Toplu fiyat/indirim: tüm mağaza, kategori veya isim/seri — zam, düşürme, özel indirim, indirim kaldırma
5. Ürün sil (soft): tek SN / SKU / barkod / ad VEYA SN aralığı
6. Kategori yerleştir: tek veya isim/seri ile toplu
7. SEO üret/güncelle
8. Renk varyantı ekle (tek ürün): örn. "Siyah, Beyaz, Kırmızı"

Tek ürün ACTION:
<!--ACTION{"type":"update_product","sn":0,"product_id":0,"product_name":"","sku":"","fields":{"price":0,"offer_price":0,"qty":0,"status":0,"category_name":"","sub_category_name":"","child_category_name":"","seo_title":"","seo_description":"","generate_seo":true}}-->

Toplu fiyat / indirim:
<!--ACTION{"type":"bulk_adjust_price","mode":"percent_up","value":10,"scope":"all"}-->
<!--ACTION{"type":"bulk_adjust_price","mode":"percent_down","value":5,"scope":"category","category_name":"Makas"}-->
<!--ACTION{"type":"bulk_adjust_price","mode":"offer_percent","value":15,"scope":"name","name_contains":"VENÜS LİNE"}-->
<!--ACTION{"type":"bulk_adjust_price","mode":"fixed_up","value":50,"scope":"all"}-->
<!--ACTION{"type":"bulk_adjust_price","mode":"fixed_down","value":20,"scope":"category","category_name":"Fırçalar"}-->
<!--ACTION{"type":"bulk_adjust_price","mode":"clear_offer","scope":"all"}-->
mode: percent_up|percent_down|offer_percent|fixed_up|fixed_down|clear_offer
scope: all|category|name — kategori için category_name/sub_category_name; isim için name_contains
"tüm ürünlere %10 zam" / "Makas kategorisine %15 indirim bırak" / "indirimleri kaldır" → bulk_adjust_price (tek tek update YAZMA)

Renk varyantı (tek ürün):
<!--ACTION{"type":"add_color_variants","product_name":"Berber Koltuğu","colors":[{"name":"Siyah","qty":5},{"name":"Beyaz","qty":3}]}-->

Kategori ata (tek):
<!--ACTION{"type":"set_category","sn":0,"product_name":"","category_name":"Makas","sub_category_name":"","child_category_name":""}-->

Toplu kategori (isim içeren tüm ürünler):
<!--ACTION{"type":"bulk_set_category","name_contains":"VENÜS LİNE FIRÇA","category_name":"Kuaför Malzemeleri","sub_category_name":"Fırçalar"}-->
"X ürünlerini/serilerini Y kategorisine al/taşı" → mutlaka bulk_set_category (tek set_category YAZMA, işlemi yapmadan "yaptım" DEME)

SEO:
<!--ACTION{"type":"generate_seo","sn":0,"product_name":"","scope":"one"}-->
<!--ACTION{"type":"generate_seo","scope":"category","category_name":"Makas"}-->
<!--ACTION{"type":"generate_seo","scope":"all"}-->

Silme (tek):
<!--ACTION{"type":"delete_product","sn":5}-->

Toplu silme (SN aralığı — panel sırası, id DESC):
<!--ACTION{"type":"bulk_delete_products","sn_from":1,"sn_to":400}-->
"1 ile 400 arası sil" / "1-400 sil" → mutlaka bulk_delete_products (tek tek delete_product YAZMA)

Toplu durum:
<!--ACTION{"type":"bulk_set_status","status":1,"scope":"draft","category_name":"Makas","sub_category_name":"","child_category_name":""}-->
status 0=pasif 1=yayında | scope published|draft|all
Kategori verilirse yalnız o kategori/alt/child etkilenir.

ZORUNLU:
- Kategori adını ağaçtan eşleştir ("Üst - Alt" yolunu ayır)
- "X kategorisini yayına/pasife al" → bulk_set_status + category_name
- Zam/indirim/toplu fiyat → bulk_adjust_price
- SEO isteğinde generate_seo ACTION
- SN = sıra no (SKU değil)
- SN aralığı silmede tek ACTION: bulk_delete_products
- Seri/marka kategori taşımada tek ACTION: bulk_set_category
- ACTION yazmadan "yaptım/zam yaptım" DEME
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

        // "yaptın mı?" → önceki kullanıcı komutunu yeniden uygula
        if (preg_match('/^(yaptin\s*mi|yapildi\s*mi|basardin\s*mi|gercekten\s*yaptin|neden\s*yapmadin)\b/u', trim($text))) {
            foreach (array_reverse($history) as $item) {
                if (($item['role'] ?? '') === 'user') {
                    $prevMsg = trim((string) ($item['content'] ?? ''));
                    if ($prevMsg !== '' && ! preg_match('/^(yaptin\s*mi|yapildi\s*mi)/u', Str::lower(Str::ascii($prevMsg)))) {
                        return $this->detectForcedAction($prevMsg, []);
                    }
                }
            }
        }

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

        $bulkCategory = $this->detectBulkSetCategoryIntent($text, $wantsDelete, $wantsPassive, $wantsPublish);
        if ($bulkCategory) {
            return $bulkCategory;
        }

        $bulkPrice = $this->detectBulkPriceIntent($text, $wantsDelete, $wantsPassive, $wantsPublish);
        if ($bulkPrice) {
            return $bulkPrice;
        }

        $colorVariants = $this->detectAddColorVariantsIntent($text, $message, $wantsDelete);
        if ($colorVariants) {
            return $colorVariants;
        }

        if ($wantsDelete) {
            // "1 ile 400 arası sil" / "1-400 sil" / "SN 1 den 400 e kadar"
            if (preg_match('/\b(\d{1,5})\s*(?:ile|-|–|—)\s*(\d{1,5})\b/u', $text, $m)
                || preg_match('/\b(\d{1,5})\s*(?:den|dan)\s*(\d{1,5})\s*(?:e|ye|a|ya)?\s*(?:kadar)?/u', $text, $m)) {
                $from = (int) $m[1];
                $to = (int) $m[2];
                if ($from > $to) {
                    [$from, $to] = [$to, $from];
                }
                if ($from >= 1 && $to > $from) {
                    return [
                        'type' => 'bulk_delete_products',
                        'sn_from' => $from,
                        'sn_to' => $to,
                    ];
                }
            }
        }

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

    private function looksLikeFakeCategorySuccess(string $reply): bool
    {
        $t = Str::lower(Str::ascii($reply));

        return (bool) preg_match('/(tasidim|tasiyorum|kategori(?:sine|ye)\s*(?:aldim|aliyorum)|yerlestirdim)/u', $t);
    }

    /**
     * "X ürünlerini/serilerini Y kategorisine al" → bulk_set_category
     */
    private function detectBulkSetCategoryIntent(
        string $text,
        bool $wantsDelete,
        bool $wantsPassive,
        bool $wantsPublish
    ): ?array {
        if ($wantsDelete || $wantsPassive || $wantsPublish) {
            return null;
        }

        $mentionsMove = (bool) preg_match('/kategori(?:sine|ye|si)?|kismina|tas[iy]|yerlestir/u', $text);
        if (! $mentionsMove) {
            return null;
        }

        $name = null;
        $path = null;

        // Ascii metin üzerinde eşle (Türkçe karakter / tüm / kısmına)
        if (preg_match('/^(.+?)\s+(?:tum\s+)?(?:serilerini|urunlerini|urunleri|serisi)\s+(.+?)\s+(?:kismina|kategorisine|kategoriye)/u', $text, $m)) {
            $name = trim($m[1]);
            $path = trim($m[2]);
        } elseif (preg_match('/(.+?)\s+(?:urunlerini|urunleri|serilerini)\s+["\']([^"\']+)["\']/u', $text, $m)) {
            $name = trim($m[1]);
            $path = trim($m[2]);
        } elseif (preg_match('/(.+?)\s+(?:urunlerini|urunleri|serilerini)\s+(.+?)\s+kategori(?:sine|ye)/u', $text, $m)) {
            $name = trim($m[1]);
            $path = trim($m[2]);
        }

        if ($name === null || $path === null || mb_strlen($name) < 2 || mb_strlen($path) < 2) {
            return null;
        }

        // "kısmına kategorisine" artıkları
        $path = trim(preg_replace('/\s*(kismina|kategorisine|kategoriye|kategorisi)\s*$/u', '', $path) ?? $path);
        $name = trim(preg_replace('/\s+(tum|bu|su)$/u', '', $name) ?? $name);

        $action = [
            'type' => 'bulk_set_category',
            'name_contains' => $name,
            'category_name' => $path,
        ];

        if (preg_match('/\s*[-–—\/|>]\s*/u', $path)) {
            $parts = preg_split('/\s*[-–—\/|>]\s*/u', $path);
            $parts = array_values(array_filter(array_map('trim', $parts)));
            if (count($parts) >= 2) {
                $action['category_name'] = $parts[0];
                $action['sub_category_name'] = $parts[1];
                if (isset($parts[2])) {
                    $action['child_category_name'] = $parts[2];
                }
            }
        }

        return $action;
    }

    /**
     * Toplu zam / indirim / fiyat düşürme.
     */
    private function detectBulkPriceIntent(
        string $text,
        bool $wantsDelete,
        bool $wantsPassive,
        bool $wantsPublish
    ): ?array {
        if ($wantsDelete || $wantsPassive || $wantsPublish) {
            return null;
        }

        $clearOffer = (bool) preg_match('/indirim(?:i|leri|leri)?\s*(?:kaldir|sil|iptal)|offer\s*(?:kaldir|sil)/u', $text);
        $priceTalk = $clearOffer
            || (bool) preg_match('/\bzam\b|indirim|fiyat|ucuzlat|dusur|art[iı]r|yukselt|ucret|kampanya|%\s*\d|\byuzde\s*\d/u', $text);
        if (! $priceTalk) {
            return null;
        }

        // Kategori taşıma cümlelerini fiyat sanma
        if (preg_match('/kategori(?:sine|ye)\s*(?:al|tasi)|kismina\s*kategori/u', $text)
            && ! preg_match('/zam|indirim|fiyat|ucuzlat|%\s*\d/u', $text)) {
            return null;
        }

        $percent = null;
        if (preg_match('/(?:%|yuzde)\s*(\d{1,3}(?:[.,]\d+)?)/u', $text, $m)
            || preg_match('/(\d{1,3}(?:[.,]\d+)?)\s*%/u', $text, $m)) {
            $percent = (float) str_replace(',', '.', $m[1]);
        }

        $fixedTl = null;
        if (preg_match('/\b(\d{1,7}(?:[.,]\d+)?)\s*(?:tl|₺)\b/u', $text, $m)) {
            $fixedTl = (float) str_replace(',', '.', $m[1]);
        }

        $isUp = (bool) preg_match('/\bzam\b|art[iı]r|yukselt|yukari/u', $text);
        $isDown = (bool) preg_match('/dusur|ucuzlat|azalt|fiyat(?:lari|i)?\s*(?:dus|cek)/u', $text);
        $isOffer = (bool) preg_match('/indirim\s*(?:birak|yap|ver|uygula|koy)|ozel\s*indirim|kampanya\s*(?:yap|baslat)|offer/u', $text)
            || ((bool) preg_match('/\bindirim\b/u', $text) && ! $isUp && ! $isDown && ! $clearOffer);

        $mode = null;
        $value = 0.0;
        if ($clearOffer) {
            $mode = 'clear_offer';
        } elseif ($isOffer && $percent !== null && $percent > 0) {
            $mode = 'offer_percent';
            $value = $percent;
        } elseif ($isUp && $percent !== null && $percent > 0) {
            $mode = 'percent_up';
            $value = $percent;
        } elseif ($isDown && $percent !== null && $percent > 0) {
            $mode = 'percent_down';
            $value = $percent;
        } elseif ($isUp && $fixedTl !== null && $fixedTl > 0) {
            $mode = 'fixed_up';
            $value = $fixedTl;
        } elseif ($isDown && $fixedTl !== null && $fixedTl > 0) {
            $mode = 'fixed_down';
            $value = $fixedTl;
        } elseif ($percent !== null && $percent > 0 && (bool) preg_match('/\bzam\b/u', $text)) {
            $mode = 'percent_up';
            $value = $percent;
        }

        if ($mode === null) {
            return null;
        }

        if (in_array($mode, ['percent_up', 'percent_down', 'offer_percent'], true) && ($value <= 0 || $value > 90)) {
            return null;
        }
        if (in_array($mode, ['fixed_up', 'fixed_down'], true) && ($value <= 0 || $value > 100000)) {
            return null;
        }

        $action = [
            'type' => 'bulk_adjust_price',
            'mode' => $mode,
            'value' => $value,
            'scope' => 'all',
        ];

        if (preg_match('/([\w\s\-çğıöşü]{2,50}?)\s*(?:kategor(?:i|isi|isinde|isindeki|isine))/u', $text, $m)
            || preg_match('/(?:kategor(?:i|isi|isinde))\s+([\w\s\-çğıöşü]{2,50})/u', $text, $m)) {
            $cat = trim(preg_replace('/\b(tum|tumu|bu|su|olan|urunleri|urunlerin|urunler|icin)\b/u', '', $m[1]) ?? $m[1]);
            if ($cat !== '') {
                $action['scope'] = 'category';
                if (preg_match('/\s*[-–—\/|>]\s*/u', $cat)) {
                    $parts = array_values(array_filter(array_map('trim', preg_split('/\s*[-–—\/|>]\s*/u', $cat))));
                    $action['category_name'] = $parts[0] ?? $cat;
                    if (isset($parts[1])) {
                        $action['sub_category_name'] = $parts[1];
                    }
                } else {
                    $action['category_name'] = $cat;
                }
            }
        } elseif (preg_match('/(.+?)\s+(?:urunlerine|urunlerini|serisine|serisini|serilerine)\b/u', $text, $m)
            || preg_match('/\b(?:icin|adli)\s+(.+?)\s+(?:urun|seri)/u', $text, $m)) {
            $name = trim($m[1]);
            $name = trim(preg_replace('/\b(tum|tumu|bu|su|olan|kategori.*?|yuzde.*|%\d+)\b/u', '', $name) ?? $name);
            if (mb_strlen($name) >= 2 && ! preg_match('/^(tum|hepsi|butun)$/u', $name)) {
                $action['scope'] = 'name';
                $action['name_contains'] = $name;
            }
        }

        if ($action['scope'] === 'all' && ! (bool) preg_match('/\b(tum|tumu|hepsi|hepsini|butun|magaza)\b/u', $text)
            && empty($action['category_name']) && empty($action['name_contains'])) {
            // Tekil ürün cümlesi olabilir; yine de "ürünlere" yoksa ve kategori yoksa all kabul etme
            if (! (bool) preg_match('/urunler|seri|kategor/u', $text)) {
                return null;
            }
        }

        return $action;
    }

    /**
     * "X ürününe siyah, beyaz renk ekle"
     */
    private function detectAddColorVariantsIntent(string $text, string $message, bool $wantsDelete): ?array
    {
        if ($wantsDelete) {
            return null;
        }
        if (! preg_match('/varyant|renk/u', $text)) {
            return null;
        }
        if (! preg_match('/ekle|ekleyebilir|olustur|tanimla/u', $text)) {
            return null;
        }

        $productName = null;
        if (preg_match('/(.+?)\s+(?:urunune|urunune|adli\s+urune)\s+/u', $text, $m)
            || preg_match('/(.+?)\s+(?:icin)\s+(?:renk|varyant)/u', $text, $m)
            || preg_match('/(.+?)(?:na|ne|ya|ye)\s+(?:siyah|beyaz|kirmizi|mavi|yesil|renk)/u', $text, $m)) {
            $productName = trim($m[1]);
            $productName = trim(preg_replace('/\b(lutfen|bana)\b/u', '', $productName) ?? $productName);
        } elseif (preg_match('/(?:sn|sira)\s*[:=#]?\s*(\d{1,6})/u', $text, $m)) {
            return [
                'type' => 'add_color_variants',
                'sn' => (int) $m[1],
                'colors' => $this->parseColorNamesFromText($text, $message),
            ];
        }

        $colors = $this->parseColorNamesFromText($text, $message);
        if ($colors === [] || $productName === null || mb_strlen($productName) < 2) {
            return null;
        }

        return [
            'type' => 'add_color_variants',
            'product_name' => $productName,
            'colors' => $colors,
        ];
    }

    /**
     * @return list<array{name:string,qty:int}>
     */
    private function parseColorNamesFromText(string $text, string $message): array
    {
        $colors = [];
        $chunk = '';
        if (preg_match('/(?:renk(?:ler)?|varyant(?:lar)?)\s*[:=]?\s*(.+)$/u', $message, $m)
            || preg_match('/(?:renk(?:ler)?|varyant(?:lar)?)\s*[:=]?\s*(.+)$/u', $text, $m)) {
            $chunk = $m[1];
        } elseif (preg_match('/\b(siyah|beyaz|kirmizi|mavi|yesil|gri|pembe|mor|turuncu|kahverengi|altin|gumush)(?:\s*,\s*|\s+ve\s+|\s+)(.+?)(?:\s+renk|\s+varyant|\s+ekle|$)/u', $text, $m)) {
            $chunk = $m[1].', '.$m[2];
        }

        if ($chunk === '') {
            return [];
        }

        $chunk = preg_replace('/\b(ekle|ekleyin|olustur|tanimla|lutfen|varyant(?:i|lar)?|renk(?:i|ler)?)\b/iu', '', $chunk) ?? $chunk;
        $parts = preg_split('/[,;\/|]+|\s+ve\s+/u', $chunk);
        foreach ($parts as $part) {
            $name = trim($part);
            $name = trim(preg_replace('/\b(ve|ile|rengi)\b/iu', '', $name) ?? $name);
            if (mb_strlen($name) >= 2 && mb_strlen($name) <= 40 && ! preg_match('/^\d+$/u', $name)) {
                $colors[] = ['name' => mb_substr($name, 0, 80), 'qty' => 0];
            }
        }

        return $colors;
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

        if ($type === 'bulk_delete_products') {
            return $this->executeBulkDeleteProducts($seller, $action);
        }

        if ($type === 'set_category') {
            return $this->executeSetCategory($seller, $action);
        }

        if ($type === 'bulk_set_category') {
            return $this->executeBulkSetCategory($seller, $action);
        }

        if ($type === 'bulk_adjust_price') {
            return $this->executeBulkAdjustPrice($seller, $action);
        }

        if ($type === 'add_color_variants') {
            return $this->executeAddColorVariants($seller, $action);
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
     * Panel SN aralığına göre soft-delete (orderByDesc id, 1-based).
     *
     * @return array{summary:?string,error:?string}
     */
    private function executeBulkDeleteProducts(Vendor $seller, array $action): array
    {
        $from = (int) ($action['sn_from'] ?? $action['from'] ?? 0);
        $to = (int) ($action['sn_to'] ?? $action['to'] ?? 0);
        if ($from < 1 || $to < 1) {
            return ['summary' => null, 'error' => 'Geçersiz SN aralığı. Örnek: 1 ile 400 arası sil.'];
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $count = $to - $from + 1;
        if ($count > 2000) {
            return ['summary' => null, 'error' => 'Tek seferde en fazla 2000 ürün silinebilir. Aralığı bölün.'];
        }

        $products = Product::query()
            ->where('vendor_id', $seller->id)
            ->orderByDesc('id')
            ->skip($from - 1)
            ->take($count)
            ->get();

        if ($products->isEmpty()) {
            return ['summary' => null, 'error' => "SN {$from}–{$to} aralığında ürün bulunamadı."];
        }

        $publishStatus = app(ProductSellerPublishStatus::class);
        $softDelete = app(SellerProductSoftDelete::class);
        $deleted = 0;
        $skippedAdmin = 0;

        foreach ($products as $product) {
            if ($publishStatus->isBlockedByAdmin($product)) {
                $skippedAdmin++;
                continue;
            }
            $softDelete->hide($product);
            $deleted++;
        }

        if ($deleted === 0) {
            return [
                'summary' => null,
                'error' => $skippedAdmin > 0
                    ? 'Bu aralıktaki ürünler admin kilidi nedeniyle silinemedi.'
                    : "SN {$from}–{$to} aralığında silinecek ürün yok.",
            ];
        }

        $summary = "SN {$from}–{$to}: {$deleted} ürün satıcı listesinden kaldırıldı (admin kaydı duruyor).";
        if ($skippedAdmin > 0) {
            $summary .= " {$skippedAdmin} ürün admin kilidi nedeniyle atlandı.";
        }
        $found = $products->count();
        if ($found < $count) {
            $summary .= " (Listede {$found} ürün vardı; istenen aralık {$count}.)";
        }

        return ['summary' => $summary, 'error' => null];
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
     * İsim içeren ürünleri toplu kategoriye taşı.
     *
     * @return array{summary:?string,error:?string}
     */
    private function executeBulkSetCategory(Vendor $seller, array $action): array
    {
        $needle = trim((string) ($action['name_contains'] ?? $action['product_name'] ?? $action['query'] ?? ''));
        if ($needle === '') {
            return ['summary' => null, 'error' => 'Hangi ürün/seri? Örnek: VENÜS LİNE FIRÇA'];
        }

        $resolved = app(SellerAiCatalogHelper::class)->resolveFromAction($action);
        if (! $resolved || empty($resolved['category_id'])) {
            return ['summary' => null, 'error' => 'Hedef kategori bulunamadı. Platformdaki kategori adını net yazın (ör. Kuaför Malzemeleri - Fırçalar).'];
        }

        $needleAscii = Str::lower(Str::ascii($needle));
        $candidates = Product::query()
            ->where('vendor_id', $seller->id)
            ->orderByDesc('id')
            ->limit(4000)
            ->get(['id', 'name', 'category_id', 'sub_category_id', 'child_category_id']);

        $matched = $candidates->filter(function (Product $p) use ($needle, $needleAscii) {
            $name = (string) $p->name;
            if (stripos($name, $needle) !== false) {
                return true;
            }

            return str_contains(Str::lower(Str::ascii($name)), $needleAscii);
        })->take(800)->values();

        if ($matched->isEmpty()) {
            return ['summary' => null, 'error' => '"'.$needle.'" içeren ürün bulunamadı.'];
        }

        $updated = 0;
        $already = 0;
        $catId = (int) $resolved['category_id'];
        $subId = (int) ($resolved['sub_category_id'] ?? 0);
        $childId = (int) ($resolved['child_category_id'] ?? 0);

        foreach ($matched as $product) {
            if ((int) $product->category_id === $catId
                && (int) $product->sub_category_id === $subId
                && (int) $product->child_category_id === $childId) {
                $already++;
                continue;
            }
            $product->category_id = $catId;
            $product->sub_category_id = $subId;
            $product->child_category_id = $childId;
            $product->save();
            $updated++;
        }

        $summary = '"'.$needle.'" içeren '.$updated.' ürün «'.$resolved['label'].'» kategorisine taşındı.';
        if ($already > 0) {
            $summary .= " {$already} ürün zaten bu kategorideydi.";
        }

        return ['summary' => $summary, 'error' => null];
    }

    /**
     * Toplu fiyat zam/indirim/düşürme.
     *
     * @return array{summary:?string,error:?string}
     */
    private function executeBulkAdjustPrice(Vendor $seller, array $action): array
    {
        $mode = strtolower(trim((string) ($action['mode'] ?? '')));
        $value = (float) ($action['value'] ?? 0);
        $scope = strtolower(trim((string) ($action['scope'] ?? 'all')));

        if (! in_array($mode, ['percent_up', 'percent_down', 'offer_percent', 'fixed_up', 'fixed_down', 'clear_offer'], true)) {
            return ['summary' => null, 'error' => 'Geçersiz fiyat işlemi.'];
        }

        $query = Product::query()->where('vendor_id', $seller->id)->orderByDesc('id');
        $label = 'mağaza';

        if ($scope === 'category' || trim((string) ($action['category_name'] ?? '')) !== '') {
            $resolved = app(SellerAiCatalogHelper::class)->resolveFromAction($action);
            if (! $resolved) {
                return ['summary' => null, 'error' => 'Kategori bulunamadı.'];
            }
            app(SellerAiCatalogHelper::class)->applyToQuery($query, $resolved);
            $label = $resolved['label'];
        } elseif ($scope === 'name' || trim((string) ($action['name_contains'] ?? '')) !== '') {
            $needle = trim((string) ($action['name_contains'] ?? $action['product_name'] ?? ''));
            if ($needle === '') {
                return ['summary' => null, 'error' => 'Ürün/seri adı gerekli.'];
            }
            $needleAscii = Str::lower(Str::ascii($needle));
            $ids = Product::query()
                ->where('vendor_id', $seller->id)
                ->orderByDesc('id')
                ->limit(4000)
                ->get(['id', 'name'])
                ->filter(function (Product $p) use ($needle, $needleAscii) {
                    $name = (string) $p->name;
                    if (stripos($name, $needle) !== false) {
                        return true;
                    }

                    return str_contains(Str::lower(Str::ascii($name)), $needleAscii);
                })
                ->pluck('id')
                ->take(800)
                ->all();
            if ($ids === []) {
                return ['summary' => null, 'error' => '"'.$needle.'" içeren ürün bulunamadı.'];
            }
            $query->whereIn('id', $ids);
            $label = '"'.$needle.'" içeren ürünler';
        }

        $products = $query->limit(2000)->get();
        if ($products->isEmpty()) {
            return ['summary' => null, 'error' => 'Güncellenecek ürün bulunamadı.'];
        }

        $updated = 0;
        foreach ($products as $product) {
            $price = (float) $product->price;
            $offer = (float) $product->offer_price;

            if ($mode === 'percent_up') {
                $price = round($price * (1 + $value / 100), 2);
                if ($offer > 0) {
                    $offer = round($offer * (1 + $value / 100), 2);
                }
            } elseif ($mode === 'percent_down') {
                $price = round($price * (1 - $value / 100), 2);
                if ($offer > 0) {
                    $offer = round($offer * (1 - $value / 100), 2);
                }
            } elseif ($mode === 'offer_percent') {
                $offer = round($price * (1 - $value / 100), 2);
            } elseif ($mode === 'fixed_up') {
                $price = round($price + $value, 2);
                if ($offer > 0) {
                    $offer = round($offer + $value, 2);
                }
            } elseif ($mode === 'fixed_down') {
                $price = max(0, round($price - $value, 2));
                if ($offer > 0) {
                    $offer = max(0, round($offer - $value, 2));
                }
            } elseif ($mode === 'clear_offer') {
                $offer = 0.0;
            }

            $price = max(0, $price);
            $offer = max(0, $offer);
            if ($offer > 0 && $offer >= $price && $price > 0) {
                $offer = max(0, round($price * 0.99, 2));
            }

            $product->price = $price;
            $product->offer_price = $offer;
            $product->save();
            $updated++;
        }

        $modeLabel = match ($mode) {
            'percent_up' => "%{$value} zam",
            'percent_down' => "%{$value} fiyat düşürme",
            'offer_percent' => "%{$value} özel indirim (offer)",
            'fixed_up' => "+{$value} ₺ zam",
            'fixed_down' => "-{$value} ₺ düşürme",
            'clear_offer' => 'indirim kaldırma',
            default => $mode,
        };

        return [
            'summary' => "{$label}: {$updated} ürüne {$modeLabel} uygulandı.",
            'error' => null,
        ];
    }

    /**
     * Tek ürüne Renk varyantı ekle/birleştir.
     *
     * @return array{summary:?string,error:?string}
     */
    private function executeAddColorVariants(Vendor $seller, array $action): array
    {
        $product = $this->findSellerProduct($seller, $action);
        if (! $product) {
            return ['summary' => null, 'error' => 'Varyant eklenecek ürün bulunamadı.'];
        }

        $colorsIn = $action['colors'] ?? [];
        if (! is_array($colorsIn) || $colorsIn === []) {
            return ['summary' => null, 'error' => 'Renk listesi boş. Örnek: Siyah, Beyaz, Kırmızı'];
        }

        $service = app(SimpleProductColorService::class);
        $existing = $service->existingRows($product);
        $byName = [];
        foreach ($existing as $row) {
            $key = Str::lower(Str::ascii((string) ($row['name'] ?? '')));
            if ($key !== '') {
                $byName[$key] = $row;
            }
        }

        $added = [];
        foreach ($colorsIn as $row) {
            if (is_string($row)) {
                $row = ['name' => $row, 'qty' => 0];
            }
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = Str::lower(Str::ascii($name));
            if (isset($byName[$key])) {
                continue;
            }
            $byName[$key] = [
                'name' => mb_substr($name, 0, 80),
                'price' => isset($row['price']) && is_numeric($row['price']) ? (float) $row['price'] : 0,
                'qty' => max(0, (int) ($row['qty'] ?? 0)),
            ];
            $added[] = $name;
        }

        if ($added === []) {
            return [
                'summary' => '"'.$product->name.'" için yeni renk yok (hepsi zaten tanımlı).',
                'error' => null,
            ];
        }

        $result = $service->sync($product, array_values($byName), false);
        if (! ($result['ok'] ?? false)) {
            return ['summary' => null, 'error' => $result['message'] ?? 'Varyant kaydedilemedi.'];
        }

        return [
            'summary' => '"'.$product->name.'" ürününe renk eklendi: '.implode(', ', $added).'.',
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

        if (preg_match('/\{[\s\S]*"type"\s*:\s*"(?:update_product|bulk_set_status|bulk_set_category|bulk_adjust_price|bulk_delete_products|delete_product|set_category|generate_seo|add_color_variants)"[\s\S]*\}/', $raw, $m)) {
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
