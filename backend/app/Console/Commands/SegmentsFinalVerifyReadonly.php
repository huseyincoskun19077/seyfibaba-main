<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Canlı öncesi salt okunur: ROW_NUMBER sayfalama + 101/102 mobilya örnek önizleme.
 * Yazma / migrate / eşleştirme yok.
 */
class SegmentsFinalVerifyReadonly extends Command
{
    protected $signature = 'segments:final-verify
                            {--page-size=24 : Sayfa boyutu}
                            {--json : JSON çıktı}';

    protected $description = 'Salt okunur: önerilen sıralama sayfa 1–3 + fiyat + sub 101/102 ürün örnekleri';

    public function handle(): int
    {
        if (! Schema::hasTable('products')) {
            $this->error('products tablosu yok.');

            return self::FAILURE;
        }

        $pageSize = min(48, max(5, (int) $this->option('page-size')));

        $scopes = [
            ['label' => 'all_active', 'wheres' => []],
            ['label' => 'category_1_mobilya', 'wheres' => ['category_id' => 1]],
            ['label' => 'sub_101_erkek_mobilya', 'wheres' => ['sub_category_id' => 101]],
            ['label' => 'sub_102_bayan_mobilya', 'wheres' => ['sub_category_id' => 102]],
        ];

        $pagination = [];
        foreach ($scopes as $scope) {
            $pagination[] = $this->verifyRecommendedPages($scope['label'], $scope['wheres'], $pageSize);
        }

        $priceCheck = $this->verifyPriceAsc($pageSize);
        $samples101 = $this->sampleFurnitureSub(101, 20);
        $samples102 = $this->sampleFurnitureSub(102, 20);

        $payload = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'read_only' => true,
                'auto_apply' => false,
                'notes' => 'Müşteri/sipariş yok. Yazma yok.',
            ],
            'recommended_pagination' => $pagination,
            'price_asc_check' => $priceCheck,
            'admin_preview_sub_101' => $samples101,
            'admin_preview_sub_102' => $samples102,
            'browser_size_ozel' => [
                'verified_in_browser' => false,
                'code_path' => 'frontend/src/components/Home/PopularProductsStrip.jsx',
                'triggers' => ['pathname change', 'pageshow', 'focus', 'visibilitychange'],
                'api' => 'GET api/personalized-products?scope=home',
            ],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->renderHuman($payload);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $wheres
     * @return array<string, mixed>
     */
    private function verifyRecommendedPages(string $label, array $wheres, int $pageSize): array
    {
        $base = Product::query()->where('status', 1)->where('approve_by_admin', 1);
        foreach ($wheres as $col => $val) {
            $base->where($col, $val);
        }
        $total = (clone $base)->count();

        $sorted = $this->applyRowNumberSort(clone $base);
        $allIds = $sorted->limit($pageSize * 3)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $p1 = array_slice($allIds, 0, $pageSize);
        $p2 = array_slice($allIds, $pageSize, $pageSize);
        $p3 = array_slice($allIds, $pageSize * 2, $pageSize);

        $overlap12 = array_values(array_intersect($p1, $p2));
        $overlap23 = array_values(array_intersect($p2, $p3));
        $overlap13 = array_values(array_intersect($p1, $p3));

        $vendorCounts = [];
        if ($p1 !== []) {
            foreach (Product::query()->whereIn('id', $p1)->pluck('vendor_id') as $vid) {
                $vid = (int) $vid;
                $vendorCounts[$vid] = ($vendorCounts[$vid] ?? 0) + 1;
            }
        }
        $maxVendorShare = $vendorCounts === [] ? 0 : max($vendorCounts);
        $distinctVendors = count($vendorCounts);

        $t0 = microtime(true);
        $this->applyRowNumberSort(clone $base)->limit($pageSize)->pluck('id');
        $elapsedMs = round((microtime(true) - $t0) * 1000, 2);

        $pass = $overlap12 === [] && $overlap23 === [] && $overlap13 === [];
        if ($total >= $pageSize * 3) {
            $pass = $pass && count($p1) === $pageSize && count($p2) === $pageSize && count($p3) === $pageSize;
        }
        // Çok satıcılı havuzda tek satıcı tüm sayfayı kaplamasın
        if ($distinctVendors >= 2 && count($p1) >= 12) {
            $pass = $pass && $maxVendorShare < count($p1);
        }

        return [
            'scope' => $label,
            'filters' => $wheres,
            'active_total' => $total,
            'page_size' => $pageSize,
            'elapsed_ms_page1' => $elapsedMs,
            'page1_ids' => $p1,
            'page2_ids' => $p2,
            'page3_ids' => $p3,
            'overlap_1_2' => $overlap12,
            'overlap_2_3' => $overlap23,
            'overlap_1_3' => $overlap13,
            'page1_vendor_counts' => $vendorCounts,
            'page1_max_vendor_share' => $maxVendorShare,
            'page1_distinct_vendors' => $distinctVendors,
            'pass_no_overlap_skip' => $pass,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyPriceAsc(int $limit): array
    {
        $q = Product::query()
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->reorder()
            ->orderByRaw('COALESCE(NULLIF(offer_price, 0), price) ASC')
            ->orderByDesc('id')
            ->limit($limit);

        $sql = $q->toSql();
        $rows = $q->get(['id', 'price', 'offer_price', 'vendor_id']);
        $effective = $rows->map(function ($p) {
            $offer = (float) $p->offer_price;
            $price = (float) $p->price;

            return $offer > 0 ? $offer : $price;
        })->all();

        $sorted = $effective;
        sort($sorted, SORT_NUMERIC);
        $ok = $effective === $sorted;

        return [
            'sql_contains_offer_price' => str_contains($sql, 'offer_price'),
            'sql_contains_row_number' => str_contains($sql, 'ROW_NUMBER'),
            'prices_non_decreasing' => $ok,
            'sample_effective_prices' => array_slice($effective, 0, 8),
            'pass' => $ok && str_contains($sql, 'offer_price') && ! str_contains($sql, 'ROW_NUMBER'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleFurnitureSub(int $subId, int $limit): array
    {
        $sub = SubCategory::query()->with('category:id,name')->find($subId);
        $products = Product::query()
            ->where('sub_category_id', $subId)
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'name', 'vendor_id', 'category_id', 'sub_category_id']);

        $rows = [];
        $suggestedTally = [];
        foreach ($products as $p) {
            $nameNorm = Str::lower(Str::ascii((string) $p->name));
            $codes = $this->suggestFurniture($nameNorm);
            $key = $codes === [] ? '(belirsiz)' : implode(',', $codes);
            $suggestedTally[$key] = ($suggestedTally[$key] ?? 0) + 1;
            $rows[] = [
                'product_id' => (int) $p->id,
                'name' => (string) $p->name,
                'vendor_id' => (int) $p->vendor_id,
                'suggested_codes' => $codes,
                'confidence' => $codes === [] ? 'admin_review' : 'name_rule',
            ];
        }

        $subSuggested = [];
        $confidence = 'admin_review';
        if ($subId === 101) {
            $subSuggested = ['men_barber'];
            $confidence = 'admin_preview';
        } elseif ($subId === 102) {
            $subSuggested = ['women_salon'];
            $confidence = 'admin_preview';
        }

        return [
            'sub_category_id' => $subId,
            'sub_category_name' => (string) ($sub->name ?? ''),
            'category_name' => (string) ($sub->category->name ?? ''),
            'recommended_segment_for_sub' => $subSuggested,
            'confidence' => $confidence,
            'auto_apply' => false,
            'rationale' => $subId === 101
                ? 'Alt ad “Erkek Kuaför Mobilyaları”; ürün örnekleri aşağıda. Toplu apply yok — admin onaylı önizleme.'
                : ($subId === 102
                    ? 'Alt ad “Bayan Kuaför Mobilyaları”; ürün örnekleri aşağıda. Toplu apply yok — admin onaylı önizleme.'
                    : 'Önizleme'),
            'suggestion_tally' => $suggestedTally,
            'products' => $rows,
        ];
    }

    /**
     * @return list<string>
     */
    private function suggestFurniture(string $nameAsciiLower): array
    {
        $men = (bool) preg_match('/\b(erkek|berber|sakal)\b/u', $nameAsciiLower);
        $women = (bool) preg_match('/\b(bayan|kadin|kadinlar)\b/u', $nameAsciiLower);
        if ($men && ! $women) {
            return ['men_barber'];
        }
        if ($women && ! $men) {
            return ['women_salon'];
        }
        if (preg_match('/\b(yikama|wash|basin)\b/u', $nameAsciiLower)) {
            return ['women_salon', 'men_barber', 'shared'];
        }
        if (preg_match('/\b(bekleme|musteri\s*koltuk|sehpa?|sepha|etejer|etajer|komodin|komidin|dolap|raf)\b/u', $nameAsciiLower)) {
            return ['shared'];
        }

        return [];
    }

    private function applyRowNumberSort($query)
    {
        $table = (new Product)->getTable();

        return $query->reorder()
            ->orderByRaw("ROW_NUMBER() OVER (PARTITION BY {$table}.vendor_id ORDER BY {$table}.id DESC)")
            ->orderByDesc($table.'.id');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderHuman(array $payload): void
    {
        $this->info('=== ÖNERİLEN SIRALAMA — SAYFA 1/2/3 ===');
        foreach ($payload['recommended_pagination'] as $row) {
            $this->line(sprintf(
                '%s | toplam=%d | ms=%.2f | pass=%s | p1_vendors=%d max_share=%d | overlap12=%d overlap23=%d',
                $row['scope'],
                $row['active_total'],
                $row['elapsed_ms_page1'],
                $row['pass_no_overlap_skip'] ? 'EVET' : 'HAYIR',
                $row['page1_distinct_vendors'],
                $row['page1_max_vendor_share'],
                count($row['overlap_1_2']),
                count($row['overlap_2_3'])
            ));
        }

        $this->newLine();
        $this->info('=== FİYAT ARTAN ===');
        $p = $payload['price_asc_check'];
        $this->line('pass='.($p['pass'] ? 'EVET' : 'HAYIR').' | ROW_NUMBER yok='.(! $p['sql_contains_row_number'] ? 'EVET' : 'HAYIR'));
        $this->line('örnek fiyatlar: '.implode(', ', $p['sample_effective_prices']));

        foreach (['admin_preview_sub_101', 'admin_preview_sub_102'] as $key) {
            $s = $payload[$key];
            $this->newLine();
            $this->info("=== ADMIN ÖNİZLEME sub {$s['sub_category_id']} {$s['sub_category_name']} → ".implode(',', $s['recommended_segment_for_sub']).' (auto_apply=false) ===');
            $this->line($s['rationale']);
            $this->line('Ürün adı kuralı dağılımı: '.json_encode($s['suggestion_tally'], JSON_UNESCAPED_UNICODE));
            $this->table(
                ['Ürün ID', 'Satıcı', 'Öneri', 'Ad'],
                collect($s['products'])->take(12)->map(fn ($r) => [
                    $r['product_id'],
                    $r['vendor_id'],
                    $r['suggested_codes'] ? implode(',', $r['suggested_codes']) : 'belirsiz',
                    mb_substr($r['name'], 0, 50),
                ])->all()
            );
        }

        $this->newLine();
        $this->warn('Tarayıcı Size Özel: bu komut doğrulamaz. Kod yolu hazır; tarayıcı testi ayrı.');
        $this->comment('Salt okunur — yazma/migrate/eşleştirme yok.');
    }
}
