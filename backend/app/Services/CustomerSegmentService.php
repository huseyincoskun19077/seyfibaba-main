<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\CustomerSegment;
use App\Models\CustomerSegmentProduct;
use App\Models\CustomerSegmentTaxonomy;
use App\Models\Product;
use App\Models\SegmentAssignmentQueue;
use App\Models\SubCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Müşteri alanları — mevcut kategori ağacının üstüne eşleme katmanı.
 * Kategori slug/URL silmez veya değiştirmez.
 */
class CustomerSegmentService
{
    /**
     * Alt kategori adı → önerilen alan kodları (çoklu serbest).
     * Tek alana kilitleme yok; mobilya gibi belirsizler boş → kuyruk.
     */
    public const SUGGESTED_SUB_MAP = [
        // Saç — kadın + erkek (kilitleme yok)
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
        // Erkek ağırlıklı (yine çoklu olabilir)
        'tıraş & berber malzemeleri' => ['men_barber'],
        'erkek bakım / berber' => ['men_barber'],
        'erkek bakım' => ['men_barber'],
        // Güzellik
        'cilt bakımı' => ['beauty_salon'],
        'cilt bakım malzemeleri' => ['beauty_salon'],
        'ağda & epilasyon' => ['beauty_salon'],
        'ağda & epilasyon malzemeleri' => ['beauty_salon'],
        'kirpik & kaş' => ['beauty_salon'],
        'kirpik & kaş malzemeleri' => ['beauty_salon'],
        'makyaj' => ['beauty_salon'],
        'makyaj malzemeleri' => ['beauty_salon'],
        'vücut bakımı' => ['beauty_salon'],
        // Tırnak — tırnak + güzellik (yalnız tırnak kilidi yok)
        'tırnak' => ['nail', 'beauty_salon'],
        'manikür & pedikür malzemeleri' => ['nail', 'beauty_salon'],
        // Ortak
        'hijyen & sarf' => ['shared', 'women_salon', 'men_barber', 'beauty_salon', 'nail'],
        'salon hijyen' => ['shared'],
        'salon hijyen & koruyucu' => ['shared'],
        'salon hijyen & koruyucu malzemeleri' => ['shared'],
        'salon sarf malzemeleri' => ['shared'],
        'salon yardımcı malzemeleri' => ['shared'],
        'parfüm & koku' => ['shared', 'beauty_salon', 'men_barber'],
        // Karışık setler — otomatik tek alan yok (inceleme)
        // 'profesyonel setler' kasıtlı olarak map'te yok
        // Yedek
        'kuaför & berber koltuğu yedek' => ['spare_parts'],
        'yıkama ünitesi yedek' => ['spare_parts'],
        'kuaför makine & cihaz yedek' => ['spare_parts', 'women_salon', 'men_barber'],
    ];

    /** Üst kategori — mobilya otomatik atama yok (ürün adına bakılır) */
    public const SUGGESTED_CATEGORY_MAP = [
        'kuaför yedek parçaları' => ['spare_parts'],
        'kuaför mobilyaları' => null,
    ];

    /**
     * Mobilya ürün adı → güvenilir alanlar; aksi belirsiz [].
     *
     * @return list<string>
     */
    public function suggestCodesForFurnitureName(string $nameAsciiLower): array
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
        // Ortak salon mobilyası — bilinen ad/yazım varyantları (toplu kategori ataması değil)
        if (preg_match('/\b(bekleme|musteri\s*koltuk|sehpa?|sepha|etejer|etajer|komodin|komidin|dolap|raf)\b/u', $nameAsciiLower)) {
            return ['shared'];
        }

        // "kuaför koltuğu" tek başına belirsiz
        return [];
    }

    /**
     * @return list<string> segment codes
     */
    public function suggestCodesForNames(string $subAsciiLower, string $catAsciiLower, ?string $productNameAsciiLower = null): array
    {
        // Mobilya: yalnızca ürün adına güven; belirsiz → [] (inceleme kuyruğu)
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
     * Boş eşleşen alt kategori için inceleme önerisi (yazmaz / uygulamaz).
     *
     * @return array{suggested:list<string>, confidence:string, rationale:string}
     */
    public function reviewSubMapping(string $subName, string $catName, int $activeProducts = 0): array
    {
        $subNorm = Str::lower(Str::ascii($subName));
        $catNorm = Str::lower(Str::ascii($catName));
        $codes = $this->suggestCodesForNames($subNorm, $catNorm, null);

        if ($codes !== []) {
            return [
                'suggested' => $codes,
                'confidence' => 'confident',
                'rationale' => 'İsim eşlemesi SUGGESTED_SUB_MAP / kategori kurallarına uyuyor.',
            ];
        }

        if (str_contains($catNorm, 'mobilya')) {
            return [
                'suggested' => [],
                'confidence' => 'admin_review',
                'rationale' => 'Mobilya alt kategorileri otomatik alana bağlanmaz; ürün adına göre (berber/bayan/yıkama/sehpa…) veya admin kuyruğu.',
            ];
        }

        if (str_contains($subNorm, 'profesyonel set') || $subNorm === 'profesyonel setler') {
            return [
                'suggested' => ['women_salon', 'men_barber', 'beauty_salon', 'nail'],
                'confidence' => 'admin_review',
                'rationale' => 'Karışık setler; tek alana kilitleme doğru değil. Admin ürün bazlı veya çoklu alan seçmeli.',
            ];
        }

        if (str_contains($subNorm, 'servis') || str_contains($subNorm, 'boya araba')) {
            return [
                'suggested' => ['shared'],
                'confidence' => 'admin_review',
                'rationale' => 'Servis/boya arabası ortak ekipman olabilir; ürün az/sıfırsa önceliksiz.',
            ];
        }

        return [
            'suggested' => [],
            'confidence' => 'admin_review',
            'rationale' => 'Güvenilir otomatik kural yok'
                .($activeProducts > 0 ? " ({$activeProducts} aktif ürün)." : '.'),
        ];
    }

    public function ensureDefaults(): void
    {
        foreach (CustomerSegment::DEFAULTS as $row) {
            CustomerSegment::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'short_name' => $row['short_name'],
                    'serial' => $row['serial'],
                    'is_active' => true,
                    'show_on_guest_home' => (bool) $row['is_primary_home'],
                    'is_primary_home' => (bool) $row['is_primary_home'],
                    'business_type_key' => $row['business_type_key'],
                    'vendor_diversity' => 3,
                    'home_product_limit' => 12,
                ]
            );
        }
    }

    public function listForApi(?string $guestOnly = null): array
    {
        $q = CustomerSegment::query()->active()->orderBy('serial');
        if ($guestOnly === 'home') {
            $q->where('show_on_guest_home', true);
        }

        return $q->get()->map(fn (CustomerSegment $s) => $this->segmentPayload($s))->all();
    }

    public function segmentPayload(CustomerSegment $s, bool $withTaxonomy = false): array
    {
        $payload = [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'slug' => $s->slug,
            'short_name' => $s->short_name ?: $s->name,
            'description' => $s->description,
            'icon' => $s->icon,
            'image' => $s->image,
            'is_primary_home' => (bool) $s->is_primary_home,
            'business_type_key' => $s->business_type_key,
            'vendor_diversity' => (int) $s->vendor_diversity,
            'home_product_limit' => (int) $s->home_product_limit,
        ];

        if ($withTaxonomy) {
            $payload['taxonomies'] = $s->taxonomies()
                ->where('is_active', true)
                ->with(['category:id,name,slug', 'subCategory:id,name,slug', 'childCategory:id,name,slug'])
                ->get()
                ->map(function (CustomerSegmentTaxonomy $t) {
                    return [
                        'category' => $t->category ? ['id' => $t->category->id, 'name' => $t->category->name, 'slug' => $t->category->slug] : null,
                        'sub_category' => $t->subCategory ? ['id' => $t->subCategory->id, 'name' => $t->subCategory->name, 'slug' => $t->subCategory->slug] : null,
                        'child_category' => $t->childCategory ? ['id' => $t->childCategory->id, 'name' => $t->childCategory->name, 'slug' => $t->childCategory->slug] : null,
                    ];
                })->all();
        }

        return $payload;
    }

    /**
     * Segmentteki ürün sorgusu: taxonomy eşleşmesi ∪ forced product map.
     * Ürün kopyalanmaz; aynı product_id birden fazla segmentte görünebilir.
     */
    public function productQueryForSegment(CustomerSegment $segment)
    {
        $taxonomies = $segment->taxonomies()->where('is_active', true)->get();
        $forcedIds = CustomerSegmentProduct::query()
            ->where('customer_segment_id', $segment->id)
            ->pluck('product_id')
            ->all();

        return Product::query()
            ->where('status', 1)
            ->where(function ($q) use ($taxonomies, $forcedIds) {
                if ($forcedIds !== []) {
                    $q->orWhereIn('id', $forcedIds);
                }
                foreach ($taxonomies as $t) {
                    $q->orWhere(function ($inner) use ($t) {
                        if ($t->child_category_id) {
                            $inner->where('child_category_id', $t->child_category_id);
                        } elseif ($t->sub_category_id) {
                            $inner->where('sub_category_id', $t->sub_category_id);
                        } elseif ($t->category_id) {
                            $inner->where('category_id', $t->category_id);
                        }
                    });
                }
                if ($taxonomies->isEmpty() && $forcedIds === []) {
                    $q->whereRaw('0 = 1');
                }
            });
    }

    /**
     * Önizleme: mevcut alt kategorileri önerilen alanlara eşle (yazmaz).
     *
     * @return list<array{sub_id:int,sub_name:string,category:string,product_count:int,suggested:list<string>,ambiguous:bool}>
     */
    public function previewSubCategoryMapping(): array
    {
        $rows = [];
        $subs = SubCategory::query()->with('category:id,name')->orderBy('category_id')->orderBy('id')->get();

        foreach ($subs as $sub) {
            $nameNorm = Str::lower(Str::ascii((string) $sub->name));
            $catNorm = Str::lower(Str::ascii((string) ($sub->category->name ?? '')));
            $suggested = $this->suggestCodesForNames($nameNorm, $catNorm);
            $count = Product::query()->where('sub_category_id', $sub->id)->count();

            $rows[] = [
                'sub_id' => $sub->id,
                'sub_name' => $sub->name,
                'category' => $sub->category->name ?? '',
                'product_count' => $count,
                'suggested' => $suggested,
                'ambiguous' => $suggested === [],
            ];
        }

        return $rows;
    }

    /**
     * Belirsiz ürünleri kuyruğa yaz (dry-run false iken).
     */
    public function queueAmbiguousProducts(bool $dryRun = true, int $limit = 500): array
    {
        $queued = [];
        $products = Product::query()
            ->with(['category:id,name', 'subCategory:id,name'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'name', 'category_id', 'sub_category_id', 'child_category_id']);

        foreach ($products as $p) {
            $subNorm = Str::lower(Str::ascii((string) ($p->subCategory->name ?? '')));
            $catNorm = Str::lower(Str::ascii((string) ($p->category->name ?? '')));
            $nameNorm = Str::lower(Str::ascii((string) $p->name));
            $codes = $this->suggestCodesForNames($subNorm, $catNorm, $nameNorm);

            $nameBoost = [];
            if (preg_match('/\b(bayan|kadin|kadinlar)\b/u', $nameNorm)) {
                $nameBoost[] = 'women_salon';
            }
            if (preg_match('/\b(erkek|berber|sakal|tiras)\b/u', $nameNorm)) {
                $nameBoost[] = 'men_barber';
            }
            if (preg_match('/\b(oje|manikur|pedikur|protez\s*tirnak|jel\s*tirnak)\b/u', $nameNorm)) {
                $nameBoost[] = 'nail';
            }

            if ($codes === [] && $nameBoost !== []) {
                $codes = array_values(array_unique($nameBoost));
            } elseif ($codes !== [] && $nameBoost !== []) {
                $codes = array_values(array_unique(array_merge($codes, $nameBoost)));
            }

            if ($codes !== []) {
                continue;
            }

            $row = [
                'product_id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name ?? '',
                'sub_category' => $p->subCategory->name ?? '',
                'suggested_codes' => [],
                'reason' => 'no_taxonomy_match',
            ];
            $queued[] = $row;

            if (! $dryRun) {
                SegmentAssignmentQueue::query()->updateOrCreate(
                    ['product_id' => $p->id, 'status' => SegmentAssignmentQueue::STATUS_PENDING],
                    [
                        'suggested_segment_ids' => [],
                        'reason' => $row['reason'],
                    ]
                );
            }
        }

        return $queued;
    }

    /**
     * Önerilen alt kategori eşleşmelerini uygula (admin onayı sonrası).
     */
    public function applySuggestedSubMappings(bool $onlyUnambiguous = true): int
    {
        $this->ensureDefaults();
        $byCode = CustomerSegment::query()->get()->keyBy('code');
        $applied = 0;

        foreach ($this->previewSubCategoryMapping() as $row) {
            if ($onlyUnambiguous && $row['ambiguous']) {
                continue;
            }
            if ($row['suggested'] === [] || $row['product_count'] < 1) {
                continue;
            }
            foreach ($row['suggested'] as $code) {
                $seg = $byCode->get($code);
                if (! $seg) {
                    continue;
                }
                $sub = SubCategory::query()->find($row['sub_id']);
                if (! $sub) {
                    continue;
                }
                CustomerSegmentTaxonomy::query()->updateOrCreate(
                    [
                        'customer_segment_id' => $seg->id,
                        'category_id' => (int) $sub->category_id,
                        'sub_category_id' => (int) $sub->id,
                        'child_category_id' => null,
                    ],
                    ['is_active' => true, 'serial' => 0]
                );
                $applied++;
            }
        }

        return $applied;
    }

    public function dumpCategoryTreeWithCounts(): array
    {
        $tree = [];
        $cats = Category::query()->orderBy('serial')->orderBy('id')->get(['id', 'name', 'slug', 'status']);
        foreach ($cats as $c) {
            $subsOut = [];
            $subs = SubCategory::query()->where('category_id', $c->id)->orderBy('serial')->orderBy('id')->get(['id', 'name', 'slug', 'status']);
            foreach ($subs as $s) {
                $childrenOut = [];
                $children = ChildCategory::query()->where('sub_category_id', $s->id)->orderBy('serial')->orderBy('id')->get(['id', 'name', 'slug', 'status']);
                foreach ($children as $ch) {
                    $childrenOut[] = [
                        'id' => $ch->id,
                        'name' => $ch->name,
                        'slug' => $ch->slug,
                        'status' => (int) $ch->status,
                        'products' => Product::query()->where('child_category_id', $ch->id)->count(),
                    ];
                }
                $subsOut[] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                    'status' => (int) $s->status,
                    'products' => Product::query()->where('sub_category_id', $s->id)->count(),
                    'children' => $childrenOut,
                ];
            }
            $tree[] = [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'status' => (int) $c->status,
                'products' => Product::query()->where('category_id', $c->id)->count(),
                'subs' => $subsOut,
            ];
        }

        return $tree;
    }
}
