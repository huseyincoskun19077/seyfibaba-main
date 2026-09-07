<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Kozmetik kategori ağacını kozmetik.md yapısına hizalar ve ürünleri güvenli eşler.
 *
 * - Ürün silinmez.
 * - Eşleşme: child adı ürün adında → child; yoksa sub; yoksa sadece Kozmetik (sub/child=0).
 * - Varsayılan dry-run; yazmak için --apply
 */
class RestructureKozmetikCategories extends Command
{
    protected $signature = 'categories:restructure-kozmetik
                            {--apply : Değişiklikleri veritabanına yaz (yoksa sadece rapor)}
                            {--category-id= : Kozmetik category id (boşsa isim/slug ile bulunur)}
                            {--skip-backup : --apply öncesi otomatik yedeği atla}';

    protected $description = 'Kozmetik alt kategorilerini md yapısına göre oluşturur ve ürünleri child/sub/kozmetik olarak atar (silmeden)';

    /** @var array<string, list<string>> */
    private array $tree = [
        'Saç Bakımı' => [
            'Şampuan', 'Saç Kremi', 'Saç Maskesi', 'Saç Serumu', 'Saç Yağı',
            'Saç Toniği', 'Saç Ampulü', 'Isı Koruyucu', 'Saç Bakım Setleri',
        ],
        'Saç Boyama' => [
            'Saç Boyası', 'Oksidan', 'Saç Açıcı', 'Toner', 'Renk Sökücü',
            'Röfle Ürünleri', 'Boya Yardımcıları',
        ],
        'Saç Şekillendirme' => [
            'Wax', 'Jöle', 'Pomad', 'Saç Spreyi', 'Saç Köpüğü',
            'Şekillendirici Krem', 'Saç Pudrası', 'Deniz Tuzu Spreyi',
        ],
        'Profesyonel Saç İşlemleri' => [
            'Keratin', 'Brezilya Fönü', 'Saç Botoxu', 'Kalıcı Fön', 'Perma',
            'Düzleştirme', 'Bond Onarıcı', 'Profesyonel Bakım Setleri',
        ],
        'Erkek Bakım / Berber' => [
            'Sakal Bakımı', 'Tıraş Ürünleri', 'After Shave', 'Kolonya',
            'Berber Pudrası', 'Erkek Saç Şekillendirme', 'Erkek Cilt Bakımı',
        ],
        'Cilt Bakımı' => [
            'Yüz Temizleme', 'Tonik', 'Serum', 'Nemlendirici', 'Peeling',
            'Yüz Maskesi', 'Göz Çevresi', 'Güneş Koruyucu', 'Profesyonel Cilt Bakımı',
        ],
        'Kirpik & Kaş' => [
            'İpek Kirpik', 'Kirpik Yapıştırıcısı', 'Kirpik Lifting', 'Kirpik Boyası',
            'Kaş Boyası', 'Kaş Laminasyonu', 'Kaş & Kirpik Bakımı',
        ],
        'Tırnak' => [
            'Oje', 'Kalıcı Oje', 'Jel', 'Polygel', 'Akrilik', 'Base Coat',
            'Top Coat', 'Primer', 'Tırnak Süsleme', 'Tırnak Bakımı',
        ],
        'Ağda & Epilasyon' => [
            'Boncuk Ağda', 'Kalıp Ağda', 'Kartuş Ağda', 'Sir Ağda', 'Ağda Bezi',
            'Ağda Öncesi', 'Ağda Sonrası', 'Epilasyon Ürünleri',
        ],
        'Makyaj' => [
            'Fondöten', 'Kapatıcı', 'Pudra', 'Allık', 'Far', 'Eyeliner',
            'Maskara', 'Dudak Ürünleri', 'Makyaj Sabitleyici',
        ],
        'Vücut Bakımı' => [
            'Vücut Peeling', 'Vücut Kremi', 'Vücut Losyonu', 'El Bakımı',
            'Ayak Bakımı', 'Topuk Bakımı', 'Masaj Yağları',
        ],
        'Hijyen & Sarf' => [
            'Eldiven', 'Maske', 'Bone', 'Dezenfektan', 'Sterilizasyon Ürünleri',
            'Tek Kullanımlık Ürünler', 'Hijyen Ürünleri',
        ],
        'Salon Sarf Malzemeleri' => [
            'Alüminyum Folyo', 'Boya Kabı', 'Boya Fırçası', 'Spatula',
            'Sprey Şişesi', 'Karıştırma Kabı', 'Boyun Bandı', 'Kuaför Önlüğü',
            'Tek Kullanımlık Havlu',
        ],
        'Parfüm & Koku' => [
            'Kadın Parfüm', 'Erkek Parfüm', 'Unisex Parfüm', 'Kolonya',
            'Vücut Spreyi', 'Salon Kokuları',
        ],
    ];

    /** Eski sub adı (normalize) => yeni sub adı */
    private array $oldSubToNewSub = [
        'manikur pedikur solusyonlari' => 'Tırnak',
        'sac boyalari' => 'Saç Boyama',
        'sac bakim kremleri kurler argon yaglari' => 'Saç Bakımı',
        'sac bakim kremleri kurler argon yağlari' => 'Saç Bakımı',
        'makyaj malzemeleri' => 'Makyaj',
        'agda ve agda malzemeleri' => 'Ağda & Epilasyon',
        'sac serumlari' => 'Saç Bakımı',
        'oje ve oje cikaricilar' => 'Tırnak',
        'sakal tras malzemeler' => 'Erkek Bakım / Berber',
        'masaj kremleri ve yaglari' => 'Vücut Bakımı',
        'maske ve serum' => 'Cilt Bakımı',
        'akne bakimi' => 'Cilt Bakımı',
        'krem ve losyonlar' => 'Vücut Bakımı',
        'oksidan aktivator' => 'Saç Boyama',
        'toz krem acicilar' => 'Saç Boyama',
        'kolonyalar' => 'Parfüm & Koku',
        'vucut peelingleri' => 'Vücut Bakımı',
        'perma form urunleri' => 'Profesyonel Saç İşlemleri',
        'tonik ve peeling' => 'Cilt Bakımı',
        'banyo urunleri ve dus jelleri' => 'Vücut Bakımı',
        'el ayak bakim kremleri' => 'Vücut Bakımı',
        'dudak bakimi' => 'Makyaj',
        'el ve ayak peelingleri' => 'Vücut Bakımı',
        'sac sampuanlari' => 'Saç Bakımı',
    ];

    /** Ürün adı anahtar kelime => child adı (öncelikli) */
    private array $productKeywordToChild = [
        'sampuan' => 'Şampuan',
        'şampuan' => 'Şampuan',
        'sac krem' => 'Saç Kremi',
        'sac maske' => 'Saç Maskesi',
        'sac serum' => 'Saç Serumu',
        'argon' => 'Saç Yağı',
        'sac yag' => 'Saç Yağı',
        'oksidan' => 'Oksidan',
        'sac boya' => 'Saç Boyası',
        'toz acici' => 'Saç Açıcı',
        'sac acici' => 'Saç Açıcı',
        'perma' => 'Perma',
        'keratin' => 'Keratin',
        'oje' => 'Oje',
        'jel oje' => 'Kalıcı Oje',
        'polygel' => 'Polygel',
        'akrilik' => 'Akrilik',
        'agda' => 'Boncuk Ağda',
        'epilasyon' => 'Epilasyon Ürünleri',
        'fondoten' => 'Fondöten',
        'kapatıcı' => 'Kapatıcı',
        'kapatci' => 'Kapatıcı',
        'maskara' => 'Maskara',
        'eyeliner' => 'Eyeliner',
        'ruj' => 'Dudak Ürünleri',
        'dudak' => 'Dudak Ürünleri',
        'sakal' => 'Sakal Bakımı',
        'tiras' => 'Tıraş Ürünleri',
        'tıraş' => 'Tıraş Ürünleri',
        'after shave' => 'After Shave',
        'kolonya' => 'Kolonya',
        'parfum' => 'Kadın Parfüm',
        'parfüm' => 'Kadın Parfüm',
        'masaj' => 'Masaj Yağları',
        'peeling' => 'Peeling',
        'tonik' => 'Tonik',
        'nemlendirici' => 'Nemlendirici',
        'akne' => 'Yüz Temizleme',
        'kirpik' => 'İpek Kirpik',
        'kas boya' => 'Kaş Boyası',
        'kaş' => 'Kaş & Kirpik Bakımı',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'MOD: APPLY (yazılacak)' : 'MOD: DRY-RUN (sadece rapor)');

        $category = $this->resolveCategory();
        if (! $category) {
            $this->error('Kozmetik kategorisi bulunamadı.');

            return self::FAILURE;
        }

        if ($apply && ! $this->option('skip-backup')) {
            $this->call('categories:backup-kozmetik', [
                'action' => 'backup',
                '--category-id' => (string) $category->id,
            ]);
        }

        $this->line("Kategori: #{$category->id} {$category->name}");

        $subMap = [];
        $childMap = [];
        $createdSubs = 0;
        $createdChildren = 0;

        foreach ($this->tree as $subName => $children) {
            $sub = $this->findSubByName($category->id, $subName);
            if (! $sub) {
                if ($apply) {
                    $sub = $this->createSub($category->id, $subName);
                } else {
                    $sub = (object) ['id' => 0, 'name' => $subName];
                }
                $createdSubs++;
                $this->line(($apply ? '  + sub' : '  [dry] sub').": {$subName}");
            }
            $subMap[$this->normalize($subName)] = $sub;

            foreach ($children as $childName) {
                $child = $sub->id
                    ? $this->findChildByName($category->id, (int) $sub->id, $childName)
                    : null;
                if (! $child) {
                    if ($apply && $sub->id) {
                        $child = $this->createChild($category->id, (int) $sub->id, $childName);
                    } else {
                        $child = (object) ['id' => 0, 'name' => $childName, 'sub_category_id' => $sub->id ?? 0];
                    }
                    $createdChildren++;
                    $this->line(($apply ? '    + child' : '    [dry] child').": {$subName} > {$childName}");
                }
                $childMap[$this->normalize($childName)] = [
                    'child' => $child,
                    'sub' => $sub,
                    'sub_name' => $subName,
                ];
            }
        }

        if ($apply) {
            // Yeniden yükle (gerçek id’ler)
            $subMap = [];
            $childMap = [];
            foreach ($this->tree as $subName => $children) {
                $sub = $this->findSubByName($category->id, $subName);
                if (! $sub) {
                    continue;
                }
                $subMap[$this->normalize($subName)] = $sub;
                foreach ($children as $childName) {
                    $child = $this->findChildByName($category->id, (int) $sub->id, $childName);
                    if ($child) {
                        $childMap[$this->normalize($childName)] = [
                            'child' => $child,
                            'sub' => $sub,
                            'sub_name' => $subName,
                        ];
                    }
                }
            }
        }

        $products = Product::query()
            ->where('category_id', $category->id)
            ->get(['id', 'name', 'category_id', 'sub_category_id', 'child_category_id']);

        $stats = [
            'to_child' => 0,
            'to_sub' => 0,
            'to_category_only' => 0,
            'unchanged' => 0,
            'updated' => 0,
        ];

        $reportLines = [];
        $oldSubs = SubCategory::query()
            ->where('category_id', $category->id)
            ->get()
            ->keyBy('id');

        foreach ($products as $product) {
            $target = $this->resolveProductTarget(
                $product,
                $category->id,
                $subMap,
                $childMap,
                $oldSubs
            );

            $newSub = (int) ($target['sub_id'] ?? 0);
            $newChild = (int) ($target['child_id'] ?? 0);
            $level = (string) $target['level'];
            $reason = (string) $target['reason'];

            $curSub = (int) ($product->sub_category_id ?? 0);
            $curChild = (int) ($product->child_category_id ?? 0);

            if ($curSub === $newSub && $curChild === $newChild && (int) $product->category_id === (int) $category->id) {
                $stats['unchanged']++;
                continue;
            }

            $stats[$level === 'child' ? 'to_child' : ($level === 'sub' ? 'to_sub' : 'to_category_only')]++;
            $reportLines[] = "#{$product->id} | {$level} | sub {$curSub}->{$newSub} child {$curChild}->{$newChild} | {$reason} | {$product->name}";

            if ($apply) {
                $product->category_id = $category->id;
                $product->sub_category_id = $newSub;
                $product->child_category_id = $newChild;
                $product->save();
                $stats['updated']++;
            }
        }

        $this->newLine();
        $this->info('Özet');
        $this->table(
            ['Metrik', 'Adet'],
            [
                ['Ürün toplam', $products->count()],
                ['Yeni sub (oluşturulacak/olan)', $createdSubs],
                ['Yeni child (oluşturulacak/olan)', $createdChildren],
                ['Child’a atanacak', $stats['to_child']],
                ['Sub’a atanacak', $stats['to_sub']],
                ['Sadece Kozmetik', $stats['to_category_only']],
                ['Değişmeyecek', $stats['unchanged']],
                ['Güncellenen (apply)', $stats['updated']],
            ]
        );

        $reportPath = storage_path('logs/kozmetik-restructure-'.date('Ymd-His').'.txt');
        file_put_contents(
            $reportPath,
            implode(PHP_EOL, array_merge(
                [
                    'apply='.($apply ? '1' : '0'),
                    'category_id='.$category->id,
                    'total='.$products->count(),
                    '---',
                ],
                $reportLines
            ))
        );
        $this->line("Rapor: {$reportPath}");

        if (! $apply) {
            $this->warn('Henüz yazılmadı. Uygulamak için: php artisan categories:restructure-kozmetik --apply');
        } else {
            $this->info('Tamamlandı. Ürün silinmedi.');
        }

        return self::SUCCESS;
    }

    private function resolveCategory(): ?Category
    {
        $id = $this->option('category-id');
        if ($id) {
            return Category::query()->find((int) $id);
        }

        return Category::query()
            ->where('slug', 'kozmetik')
            ->orWhere('name', 'like', '%Kozmetik%')
            ->orderBy('id')
            ->first();
    }

    private function findSubByName(int $categoryId, string $name): ?SubCategory
    {
        $norm = $this->normalize($name);
        $subs = SubCategory::query()->where('category_id', $categoryId)->get();
        foreach ($subs as $sub) {
            if ($this->normalize((string) $sub->name) === $norm) {
                return $sub;
            }
        }

        return null;
    }

    private function findChildByName(int $categoryId, int $subId, string $name): ?ChildCategory
    {
        $norm = $this->normalize($name);
        $children = ChildCategory::query()
            ->where('category_id', $categoryId)
            ->where('sub_category_id', $subId)
            ->get();
        foreach ($children as $child) {
            if ($this->normalize((string) $child->name) === $norm) {
                return $child;
            }
        }

        return null;
    }

    private function createSub(int $categoryId, string $name): SubCategory
    {
        $sub = new SubCategory();
        $sub->category_id = $categoryId;
        $sub->name = $name;
        $sub->slug = $this->uniqueSlug('sub_categories', $name);
        $sub->status = 1;
        $sub->save();

        return $sub;
    }

    private function createChild(int $categoryId, int $subId, string $name): ChildCategory
    {
        $child = new ChildCategory();
        $child->category_id = $categoryId;
        $child->sub_category_id = $subId;
        $child->name = $name;
        $child->slug = $this->uniqueSlug('child_categories', $name);
        $child->status = 1;
        $child->save();

        return $child;
    }

    private function uniqueSlug(string $table, string $name): string
    {
        $base = Str::slug($name, '-', 'tr');
        if ($base === '') {
            $base = 'kat-'.substr(md5($name), 0, 8);
        }
        $slug = $base;
        $i = 2;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * @param  array<string, object>  $subMap
     * @param  array<string, array{child: object, sub: object, sub_name: string}>  $childMap
     * @param  \Illuminate\Support\Collection<int, SubCategory>  $oldSubs
     * @return array{level: string, sub_id: int, child_id: int, reason: string}
     */
    private function resolveProductTarget(
        Product $product,
        int $categoryId,
        array $subMap,
        array $childMap,
        $oldSubs
    ): array {
        $haystack = $this->normalize((string) $product->name);
        $oldSub = $oldSubs->get((int) ($product->sub_category_id ?? 0));
        $oldSubName = $oldSub ? (string) $oldSub->name : '';
        $preferredSubName = $this->oldSubToNewSub[$this->normalize($oldSubName)] ?? null;

        // 1) Anahtar kelime → child
        foreach ($this->productKeywordToChild as $keyword => $childName) {
            $kn = $this->normalize($keyword);
            if ($kn !== '' && str_contains($haystack, $kn)) {
                $hit = $childMap[$this->normalize($childName)] ?? null;
                if ($hit && ($hit['child']->id ?? 0)) {
                    return [
                        'level' => 'child',
                        'sub_id' => (int) $hit['sub']->id,
                        'child_id' => (int) $hit['child']->id,
                        'reason' => "keyword:{$keyword}→{$childName}",
                    ];
                }
                // dry-run’da id=0 olabilir: yine de sub’a düş
                if ($hit) {
                    $subId = (int) ($hit['sub']->id ?? 0);

                    return [
                        'level' => $subId > 0 ? 'sub' : 'category',
                        'sub_id' => $subId,
                        'child_id' => 0,
                        'reason' => "keyword-sub:{$keyword}→{$hit['sub_name']}",
                    ];
                }
            }
        }

        // 2) Ürün adında child adı geçiyor mu
        $bestChild = null;
        $bestLen = 0;
        foreach ($childMap as $normChild => $hit) {
            if ($normChild === '' || ! str_contains($haystack, $normChild)) {
                continue;
            }
            if (mb_strlen($normChild) > $bestLen) {
                $bestLen = mb_strlen($normChild);
                $bestChild = $hit;
            }
        }
        if ($bestChild && ($bestChild['child']->id ?? 0)) {
            return [
                'level' => 'child',
                'sub_id' => (int) $bestChild['sub']->id,
                'child_id' => (int) $bestChild['child']->id,
                'reason' => 'name→child:'.$bestChild['child']->name,
            ];
        }

        // 3) Eski sub alias → yeni sub
        if ($preferredSubName) {
            $sub = $subMap[$this->normalize($preferredSubName)] ?? null;
            if ($sub && ($sub->id ?? 0)) {
                return [
                    'level' => 'sub',
                    'sub_id' => (int) $sub->id,
                    'child_id' => 0,
                    'reason' => 'old-sub→'.$preferredSubName,
                ];
            }
        }

        // 4) Ürün adında sub adı
        foreach ($subMap as $normSub => $sub) {
            if ($normSub !== '' && str_contains($haystack, $normSub) && ($sub->id ?? 0)) {
                return [
                    'level' => 'sub',
                    'sub_id' => (int) $sub->id,
                    'child_id' => 0,
                    'reason' => 'name→sub:'.$sub->name,
                ];
            }
        }

        // 5) Sadece Kozmetik
        return [
            'level' => 'category',
            'sub_id' => 0,
            'child_id' => 0,
            'reason' => 'fallback-kozmetik',
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $map = [
            'ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c',
            'â' => 'a', 'î' => 'i', 'û' => 'u', '&' => ' ',
        ];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
