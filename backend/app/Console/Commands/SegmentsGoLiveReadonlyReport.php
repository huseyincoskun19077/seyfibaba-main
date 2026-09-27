<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Canlı öncesi salt okunur rapor — tek dosya, harici segment servisi gerekmez.
 * customer_segments / CustomerSegmentService / migrate yok; yazma yok.
 */
class SegmentsGoLiveReadonlyReport extends Command
{
    protected $signature = 'segments:go-live-report
                            {--json : JSON çıktı}
                            {--ambiguous-limit=50 : Belirsiz mobilya örnek üst sınırı (max 50)}
                            {--sort-limit=24 : Sıralama ölçümü satır üst sınırı}';

    protected $description = 'Salt okunur: kategori ağacı, ürün/satıcı sayıları, alan eşleşmesi, belirsiz mobilya, sıralama EXPLAIN';

    /** @var array<string, list<string>> */
    private const SUGGESTED_SUB_MAP = [
        'saç bakımı' => ['women_salon', 'men_barber'],
        'saç boyama' => ['women_salon', 'men_barber'],
        'saç şekillendirme' => ['women_salon', 'men_barber'],
        'profesyonel saç işlemleri' => ['women_salon'],
        'saç boyama & röfle malzemeleri' => ['women_salon', 'men_barber'],
        'perma & saç işlem malzemeleri' => ['women_salon'],
        'saç tutucular' => ['women_salon', 'men_barber'],
        'saç tutucular & şekillendirme aksesuarları' => ['women_salon', 'men_barber'],
        'şekillendirme aksesuar' => ['women_salon', 'men_barber'],
        'saç fırçaları' => ['women_salon', 'men_barber'],
        'fırça setleri' => ['women_salon', 'men_barber'],
        'taraklar' => ['women_salon', 'men_barber'],
        'makaslar' => ['women_salon', 'men_barber'],
        'saç kesim malzemeleri' => ['women_salon', 'men_barber'],
        'elektrikli kuaför aletleri' => ['women_salon', 'men_barber'],
        'tıraş & berber malzemeleri' => ['men_barber'],
        'erkek bakım / berber' => ['men_barber'],
        'erkek bakım' => ['men_barber'],
        'cilt bakımı' => ['beauty_salon'],
        'cilt bakım malzemeleri' => ['beauty_salon'],
        'ağda & epilasyon' => ['beauty_salon'],
        'ağda & epilasyon malzemeleri' => ['beauty_salon'],
        'kirpik & kaş' => ['beauty_salon'],
        'kirpik & kaş malzemeleri' => ['beauty_salon'],
        'makyaj' => ['beauty_salon'],
        'makyaj malzemeleri' => ['beauty_salon'],
        'vücut bakımı' => ['beauty_salon'],
        'tırnak' => ['nail', 'beauty_salon'],
        'manikür & pedikür malzemeleri' => ['nail', 'beauty_salon'],
        'hijyen & sarf' => ['shared', 'women_salon', 'men_barber', 'beauty_salon', 'nail'],
        'salon hijyen' => ['shared'],
        'salon hijyen & koruyucu' => ['shared'],
        'salon hijyen & koruyucu malzemeleri' => ['shared'],
        'salon sarf malzemeleri' => ['shared'],
        'salon yardımcı malzemeleri' => ['shared'],
        'parfüm & koku' => ['shared', 'beauty_salon', 'men_barber'],
        'kuaför & berber koltuğu yedek' => ['spare_parts'],
        'yıkama ünitesi yedek' => ['spare_parts'],
        'kuaför makine & cihaz yedek' => ['spare_parts', 'women_salon', 'men_barber'],
    ];

    /** @var array<string, list<string>|null> */
    private const SUGGESTED_CATEGORY_MAP = [
        'kuaför yedek parçaları' => ['spare_parts'],
        'kuaför mobilyaları' => null,
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            $this->error('Gerekli tablolar yok (categories / products).');

            return self::FAILURE;
        }

        $ambiguousLimit = min(50, max(1, (int) $this->option('ambiguous-limit')));
        $sortLimit = min(48, max(5, (int) $this->option('sort-limit')));

        $payload = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'read_only' => true,
                'customer_segments_required' => false,
                'customer_segment_service_required' => false,
                'notes' => 'Şifre/token/müşteri verisi içermez. Yazma işlemi yapılmaz.',
            ],
            'category_tree' => $this->buildCategoryTree(),
            'suggested_mappings' => $mappings = $this->buildSuggestedMappings(),
            'ambiguous_sub_review' => $this->buildAmbiguousSubReview($mappings),
            'ambiguous_furniture' => $this->buildAmbiguousFurniture($ambiguousLimit),
            'default_sort_probe' => $this->probeDefaultSort($sortLimit),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->renderHuman($payload);

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildCategoryTree(): array
    {
        $tree = [];
        $catQuery = Category::query()->select(['id', 'name', 'slug', 'status']);
        if (Schema::hasColumn('categories', 'serial')) {
            $catQuery->orderBy('serial');
        }
        $categories = $catQuery->orderBy('id')->get();

        foreach ($categories as $cat) {
            $catStats = $this->productStats(['category_id' => (int) $cat->id]);
            $subsOut = [];

            $subQuery = SubCategory::query()
                ->where('category_id', $cat->id)
                ->select(['id', 'name', 'slug', 'status', 'category_id']);
            if (Schema::hasColumn('sub_categories', 'serial')) {
                $subQuery->orderBy('serial');
            }
            $subs = $subQuery->orderBy('id')->get();

            foreach ($subs as $sub) {
                $subStats = $this->productStats(['sub_category_id' => (int) $sub->id]);
                $childrenOut = [];

                if (Schema::hasTable('child_categories')) {
                    $childQuery = ChildCategory::query()
                        ->where('sub_category_id', $sub->id)
                        ->select(['id', 'name', 'slug', 'status', 'sub_category_id']);
                    if (Schema::hasColumn('child_categories', 'serial')) {
                        $childQuery->orderBy('serial');
                    }
                    foreach ($childQuery->orderBy('id')->get() as $child) {
                        $childStats = $this->productStats(['child_category_id' => (int) $child->id]);
                        $childrenOut[] = [
                            'id' => (int) $child->id,
                            'name' => (string) $child->name,
                            'slug' => (string) $child->slug,
                            'status' => (int) $child->status,
                            'active_products' => $childStats['active_products'],
                            'distinct_vendors' => $childStats['distinct_vendors'],
                        ];
                    }
                }

                $subsOut[] = [
                    'id' => (int) $sub->id,
                    'name' => (string) $sub->name,
                    'slug' => (string) $sub->slug,
                    'status' => (int) $sub->status,
                    'active_products' => $subStats['active_products'],
                    'distinct_vendors' => $subStats['distinct_vendors'],
                    'children' => $childrenOut,
                ];
            }

            $tree[] = [
                'id' => (int) $cat->id,
                'name' => (string) $cat->name,
                'slug' => (string) $cat->slug,
                'status' => (int) $cat->status,
                'active_products' => $catStats['active_products'],
                'distinct_vendors' => $catStats['distinct_vendors'],
                'sub_categories' => $subsOut,
            ];
        }

        return $tree;
    }

    /**
     * @param  array<string, int>  $where
     * @return array{active_products:int, distinct_vendors:int}
     */
    private function productStats(array $where): array
    {
        $q = Product::query()
            ->where('status', 1)
            ->where('approve_by_admin', 1);

        foreach ($where as $col => $val) {
            $q->where($col, $val);
        }

        $row = $q->selectRaw('COUNT(*) as active_products, COUNT(DISTINCT vendor_id) as distinct_vendors')->first();

        return [
            'active_products' => (int) ($row->active_products ?? 0),
            'distinct_vendors' => (int) ($row->distinct_vendors ?? 0),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildSuggestedMappings(): array
    {
        $rows = [];
        $subs = SubCategory::query()
            ->with(['category:id,name'])
            ->orderBy('category_id')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'category_id', 'status']);

        foreach ($subs as $sub) {
            $catName = (string) ($sub->category->name ?? '');
            $subNorm = Str::lower(Str::ascii((string) $sub->name));
            $catNorm = Str::lower(Str::ascii($catName));
            $suggested = $this->suggestCodesForNames($subNorm, $catNorm, null);
            $stats = $this->productStats(['sub_category_id' => (int) $sub->id]);

            $rows[] = [
                'category_id' => (int) $sub->category_id,
                'category_name' => $catName,
                'sub_category_id' => (int) $sub->id,
                'sub_category_name' => (string) $sub->name,
                'sub_category_slug' => (string) $sub->slug,
                'active_products' => $stats['active_products'],
                'distinct_vendors' => $stats['distinct_vendors'],
                'suggested_segment_codes' => $suggested,
                'ambiguous' => $suggested === [],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{product_id:int,name:string,category_id:int,category_name:string,vendor_id:int}>
     */
    private function buildAmbiguousFurniture(int $limit): array
    {
        $furnitureCategoryIds = Category::query()
            ->get(['id', 'name'])
            ->filter(fn ($c) => str_contains(Str::lower(Str::ascii((string) $c->name)), 'mobilya'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($furnitureCategoryIds === []) {
            return [];
        }

        $catNames = Category::query()
            ->whereIn('id', $furnitureCategoryIds)
            ->pluck('name', 'id');

        $candidates = Product::query()
            ->whereIn('category_id', $furnitureCategoryIds)
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->orderByDesc('id')
            ->limit(max(200, $limit * 4))
            ->get(['id', 'name', 'category_id', 'vendor_id']);

        $out = [];
        foreach ($candidates as $p) {
            $nameNorm = Str::lower(Str::ascii((string) $p->name));
            if ($this->suggestCodesForFurnitureName($nameNorm) !== []) {
                continue;
            }
            $out[] = [
                'product_id' => (int) $p->id,
                'name' => (string) $p->name,
                'category_id' => (int) $p->category_id,
                'category_name' => (string) ($catNames[$p->category_id] ?? ''),
                'vendor_id' => (int) $p->vendor_id,
            ];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function suggestCodesForFurnitureName(string $nameAsciiLower): array
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

    /**
     * @param  list<array<string, mixed>>  $mappings
     * @return list<array<string, mixed>>
     */
    private function buildAmbiguousSubReview(array $mappings): array
    {
        $out = [];
        foreach ($mappings as $r) {
            if (! ($r['ambiguous'] ?? false)) {
                continue;
            }
            $review = $this->reviewSubMapping(
                (string) $r['sub_category_name'],
                (string) $r['category_name'],
                (int) $r['active_products']
            );
            $out[] = [
                'sub_category_id' => $r['sub_category_id'],
                'category_id' => $r['category_id'],
                'category_name' => $r['category_name'],
                'sub_category_name' => $r['sub_category_name'],
                'active_products' => $r['active_products'],
                'recommended_codes' => $review['suggested'],
                'confidence' => $review['confidence'],
                'rationale' => $review['rationale'],
                'auto_apply' => false,
            ];
        }

        return $out;
    }

    /**
     * @return array{suggested:list<string>, confidence:string, rationale:string}
     */
    private function reviewSubMapping(string $subName, string $catName, int $activeProducts = 0): array
    {
        $subNorm = Str::lower(Str::ascii($subName));
        $catNorm = Str::lower(Str::ascii($catName));
        $codes = $this->suggestCodesForNames($subNorm, $catNorm, null);

        if ($codes !== []) {
            return [
                'suggested' => $codes,
                'confidence' => 'confident',
                'rationale' => 'İsim eşlemesi kurallara uyuyor (otomatik apply edilmedi; yalnızca öneri).',
            ];
        }

        if (str_contains($catNorm, 'mobilya')) {
            return [
                'suggested' => [],
                'confidence' => 'admin_review',
                'rationale' => 'Mobilya alt kategorisi otomatik alana bağlanmaz; ürün adına göre veya admin kuyruğu.',
            ];
        }

        if (str_contains($subNorm, 'profesyonel set')) {
            return [
                'suggested' => ['women_salon', 'men_barber', 'beauty_salon', 'nail'],
                'confidence' => 'admin_review',
                'rationale' => 'Karışık setler; tek alana kilitlemeyin.',
            ];
        }

        if (str_contains($subNorm, 'servis') || str_contains($subNorm, 'boya araba')) {
            return [
                'suggested' => ['shared'],
                'confidence' => 'admin_review',
                'rationale' => 'Ortak ekipman olabilir; ürün sayısına bakarak karar verin.',
            ];
        }

        return [
            'suggested' => [],
            'confidence' => 'admin_review',
            'rationale' => 'Güvenilir otomatik kural yok'
                .($activeProducts > 0 ? " ({$activeProducts} aktif ürün)." : '.'),
        ];
    }

    /**
     * @return list<string>
     */
    private function suggestCodesForNames(string $subAsciiLower, string $catAsciiLower, ?string $productNameAsciiLower = null): array
    {
        if (str_contains($catAsciiLower, 'mobilya')) {
            if ($productNameAsciiLower) {
                return $this->suggestCodesForFurnitureName($productNameAsciiLower);
            }

            return [];
        }

        foreach (self::SUGGESTED_CATEGORY_MAP as $needle => $codes) {
            if ($codes !== null && str_contains($catAsciiLower, Str::ascii($needle))) {
                return $codes;
            }
        }

        if ($subAsciiLower !== '') {
            foreach (self::SUGGESTED_SUB_MAP as $needle => $codes) {
                $n = Str::lower(Str::ascii($needle));
                if ($n === '') {
                    continue;
                }
                if (str_contains($subAsciiLower, $n) || str_contains($n, $subAsciiLower)) {
                    return $codes;
                }
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function probeDefaultSort(int $limit): array
    {
        $driver = DB::connection()->getDriverName();
        $table = (new Product)->getTable();

        $query = Product::query()
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->reorder()
            ->orderByRaw(
                "ROW_NUMBER() OVER (PARTITION BY {$table}.vendor_id ORDER BY {$table}.id DESC)"
            )
            ->orderByDesc($table.'.id')
            ->limit($limit)
            ->select([$table.'.id', $table.'.vendor_id']);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $explain = [];
        try {
            if ($driver === 'mysql') {
                $explain = array_map(static fn ($row) => (array) $row, DB::select('EXPLAIN '.$sql, $bindings));
            } else {
                $explain = [['note' => 'EXPLAIN yalnızca mysql için üretildi', 'driver' => $driver]];
            }
        } catch (\Throwable $e) {
            $explain = [['error' => 'EXPLAIN alınamadı', 'message' => $this->sanitizeError($e->getMessage())]];
        }

        $elapsedMs = null;
        $rowCount = 0;
        $sampleIds = [];
        $vendorCounts = [];
        try {
            $t0 = microtime(true);
            $rows = $query->get();
            $elapsedMs = round((microtime(true) - $t0) * 1000, 2);
            $rowCount = $rows->count();
            $sampleIds = $rows->take(5)->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($rows as $row) {
                $vid = (int) $row->vendor_id;
                $vendorCounts[$vid] = ($vendorCounts[$vid] ?? 0) + 1;
            }
        } catch (\Throwable $e) {
            $explain[] = ['error' => 'Süre ölçümü başarısız', 'message' => $this->sanitizeError($e->getMessage())];
        }

        return [
            'driver' => $driver,
            'limit' => $limit,
            'algorithm' => 'ROW_NUMBER() OVER (PARTITION BY vendor_id ORDER BY id DESC), id DESC',
            'sql_shape' => "{$table} WHERE status=1 AND approve_by_admin=1 ORDER BY vendor_rank ASC, id DESC LIMIT {$limit}",
            'elapsed_ms' => $elapsedMs,
            'rows_returned' => $rowCount,
            'sample_product_ids' => $sampleIds,
            'vendor_counts_in_page' => $vendorCounts,
            'explain' => $explain,
        ];
    }

    private function sanitizeError(string $message): string
    {
        $message = preg_replace('/password[=:].+/i', 'password=***', $message) ?? $message;
        $message = preg_replace('/pdo_mysql:host=[^;\s]+/i', 'pdo_mysql:host=***', $message) ?? $message;

        return mb_substr($message, 0, 240);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderHuman(array $payload): void
    {
        $this->info('=== 1) KATEGORİ AĞACI (aktif ürün / farklı satıcı) ===');
        foreach ($payload['category_tree'] as $cat) {
            $this->line(sprintf(
                '[%d] %s (slug=%s, status=%d) — ürün=%d, satıcı=%d',
                $cat['id'],
                $cat['name'],
                $cat['slug'],
                $cat['status'],
                $cat['active_products'],
                $cat['distinct_vendors']
            ));
            foreach ($cat['sub_categories'] as $sub) {
                $this->line(sprintf(
                    '  [%d] %s — ürün=%d, satıcı=%d',
                    $sub['id'],
                    $sub['name'],
                    $sub['active_products'],
                    $sub['distinct_vendors']
                ));
                foreach ($sub['children'] as $child) {
                    $this->line(sprintf(
                        '    [%d] %s — ürün=%d, satıcı=%d',
                        $child['id'],
                        $child['name'],
                        $child['active_products'],
                        $child['distinct_vendors']
                    ));
                }
            }
        }

        $this->newLine();
        $this->info('=== 2–3) ÖNERİLEN MÜŞTERİ ALANI EŞLEŞMELERİ ===');
        $this->table(
            ['Cat ID', 'Üst kategori', 'Sub ID', 'Alt kategori', 'Ürün', 'Satıcı', 'Öneri', 'Belirsiz'],
            collect($payload['suggested_mappings'])->map(fn ($r) => [
                $r['category_id'],
                $r['category_name'],
                $r['sub_category_id'],
                $r['sub_category_name'],
                $r['active_products'],
                $r['distinct_vendors'],
                $r['suggested_segment_codes'] ? implode(',', $r['suggested_segment_codes']) : '—',
                $r['ambiguous'] ? 'EVET' : 'hayır',
            ])->all()
        );

        $this->newLine();
        $this->info('=== BOŞ EŞLEŞME İNCELEME (otomatik apply YOK) ===');
        $this->table(
            ['Sub ID', 'Üst', 'Alt', 'Ürün', 'Öneri', 'Güven', 'Gerekçe'],
            collect($payload['ambiguous_sub_review'] ?? [])->map(fn ($r) => [
                $r['sub_category_id'],
                $r['category_name'],
                $r['sub_category_name'],
                $r['active_products'],
                $r['recommended_codes'] ? implode(',', $r['recommended_codes']) : '—',
                $r['confidence'],
                mb_substr($r['rationale'], 0, 60),
            ])->all()
        );

        $this->newLine();
        $this->info('=== 4) BELİRSİZ MOBİLYA ÖRNEKLERİ (max 50) ===');
        $amb = $payload['ambiguous_furniture'];
        if ($amb === []) {
            $this->line('(örnek yok veya mobilya kategorisi bulunamadı)');
        } else {
            $this->table(
                ['Ürün ID', 'Ad', 'Kategori ID', 'Kategori', 'Satıcı ID'],
                collect($amb)->map(fn ($r) => [
                    $r['product_id'],
                    mb_substr($r['name'], 0, 80),
                    $r['category_id'],
                    $r['category_name'],
                    $r['vendor_id'],
                ])->all()
            );
            $this->line('Toplam örnek: '.count($amb));
        }

        $this->newLine();
        $this->info('=== 5) VARSAYILAN SIRALAMA (Önerilen) EXPLAIN + SÜRE ===');
        $probe = $payload['default_sort_probe'];
        $this->line('Şekil: '.$probe['sql_shape']);
        $this->line('Süre: '.($probe['elapsed_ms'] === null ? 'ölçülemedi' : $probe['elapsed_ms'].' ms'));
        $this->line('Dönen satır: '.$probe['rows_returned'].' | örnek id: '.implode(',', $probe['sample_product_ids']));
        foreach ($probe['explain'] as $i => $row) {
            $this->line('EXPLAIN['.$i.']: '.json_encode($row, JSON_UNESCAPED_UNICODE));
        }

        $this->newLine();
        $this->comment('Salt okunur rapor tamamlandı. Yazma / migrate / eşleştirme yapılmadı.');
    }
}
