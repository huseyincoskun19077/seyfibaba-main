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
 * Kuaför Malzemeleri (#2) ağacını malzemeleri.md’ye hizalar; ürünleri child/sub/ana kategoriye atar.
 * Ürün silinmez. Varsayılan dry-run; --apply yazar. --apply öncesi otomatik yedek.
 */
class RestructureMalzemeleriCategories extends Command
{
    protected $signature = 'categories:restructure-malzemeleri
                            {--apply : Değişiklikleri yaz}
                            {--category-id=2 : Kuaför Malzemeleri category id}
                            {--skip-backup : Otomatik yedeği atla}';

    protected $description = 'Kuaför Malzemeleri alt kategorilerini md yapısına göre oluşturur ve ürünleri atar (silmeden)';

    /** @var array<string, list<string>> */
    private array $tree = [
        'Makaslar' => [
            'Saç Kesim Makasları', 'Berber Makasları', 'Ara Makaslar', 'Efile Makasları',
            'Solak Makaslar', 'Çocuk Saç Kesim Makasları', 'Makas Setleri', 'Makas Aksesuarları',
        ],
        'Taraklar' => [
            'Kesim Tarakları', 'Fön Tarakları', 'Boyama Tarakları', 'Röfle Tarakları',
            'Saç Açma Tarakları', 'Sık Dişli Taraklar', 'Seyrek Dişli Taraklar', 'Saplı Taraklar',
            'Profesyonel Tarak Setleri', 'Diğer Taraklar',
        ],
        'Saç Fırçaları' => [
            'Fön Fırçaları', 'Yuvarlak Fırçalar', 'Düz Saç Fırçaları', 'Saç Açma Fırçaları',
            'Topuz Fırçaları', 'Profesyonel Saç Fırçaları', 'Ense Fırçaları', 'Sakal Fırçaları',
            'Fırça Setleri',
        ],
        'Saç Kesim Malzemeleri' => [
            'Saç Kesim Setleri', 'Saç Kesim Önlükleri', 'Saç Kesim Pelerinleri', 'Boyun Bantları',
            'Ense Temizleme Fırçaları', 'El Aynaları', 'Saç Kesim Aparatları', 'Diğer Saç Kesim Malzemeleri',
        ],
        'Saç Tutucular & Şekillendirme Aksesuarları' => [
            'Saç Pensleri', 'Saç Klipsleri', 'Saç Mandalları', 'Fırkete', 'Saç Tokaları',
            'Saç Lastikleri', 'Saç Fileleri', 'Topuz Aparatları', 'Bigudiler', 'Saç Ruloları',
            'Perma Çubukları', 'Şekillendirme Aksesuar Setleri',
        ],
        'Elektrikli Kuaför Aletleri' => [
            'Fön Makineleri', 'Saç Kurutma Makineleri', 'Saç Kesme Makineleri', 'Saç Sakal Makineleri',
            'Ense Makineleri', 'Tıraş Makineleri', 'Saç Düzleştiriciler', 'Saç Maşaları',
            'Saç Tost Makineleri', 'Saç Şekillendiriciler', 'Profesyonel Buhar Makineleri',
            'Ağda Isıtıcıları', 'Diğer Elektrikli Kuaför Aletleri',
        ],
        'Tıraş & Berber Malzemeleri' => [
            'Usturalar', 'Ustura Sapları', 'Jiletler', 'Tıraş Fırçaları', 'Tıraş Tasları',
            'Tıraş Kapları', 'Sakal Tarakları', 'Sakal Fırçaları', 'Ense Fırçaları',
            'Berber Boyunlukları', 'Berber Pelerinleri', 'Tıraş Aksesuarları', 'Berber Setleri',
        ],
        'Saç Boyama & Röfle Malzemeleri' => [
            'Boya Kapları', 'Boya Fırçaları', 'Boya Karıştırma Kapları', 'Ölçü Kapları',
            'Ölçü Şişeleri', 'Uygulama Şişeleri', 'Röfle Şapkaları', 'Röfle İğneleri',
            'Röfle Aparatları', 'Folyo', 'Folyo Aparatları', 'Boya Önlükleri', 'Boyama Setleri',
        ],
        'Perma & Saç İşlem Malzemeleri' => [
            'Perma Çubukları', 'Perma Kağıtları', 'Perma Lastikleri', 'Perma Şapkaları',
            'Saç İşlem Kapları', 'Uygulama Şişeleri', 'İşlem Fırçaları', 'İşlem Aparatları',
            'Profesyonel İşlem Setleri',
        ],
        'Manikür & Pedikür Malzemeleri' => [
            'Manikür Pensleri', 'Tırnak Makasları', 'Tırnak Törpüleri', 'Ayak Törpüleri',
            'Nasır Aletleri', 'Tırnak İticiler', 'Tırnak Temizleme Aletleri', 'Manikür Setleri',
            'Pedikür Setleri', 'Manikür Kapları', 'Pedikür Küvetleri', 'Parmak Ayırıcılar',
            'Manikür & Pedikür Aksesuarları',
        ],
        'Ağda & Epilasyon Malzemeleri' => [
            'Ağda Spatulaları', 'Ağda Kapları', 'Ağda Bezleri', 'Ağda Setleri',
            'Kartuş Ağda Aparatları', 'Ağda Uygulama Aparatları', 'Epilasyon Setleri',
            'Epilasyon Aksesuarları', 'Diğer Ağda & Epilasyon Malzemeleri',
        ],
        'Cilt Bakım Malzemeleri' => [
            'Cilt Bakım Spatulaları', 'Cilt Bakım Fırçaları', 'Cilt Bakım Süngerleri',
            'Cilt Bakım Kapları', 'Komedon Aletleri', 'Cilt Temizleme Aletleri',
            'Uygulama Aparatları', 'Maske Uygulama Aparatları', 'Cilt Bakım Setleri',
            'Cilt Bakım Aksesuarları',
        ],
        'Kirpik & Kaş Malzemeleri' => [
            'Kirpik Pensleri', 'Kirpik Uygulama Setleri', 'Kirpik Paletleri', 'Kirpik Pedleri',
            'Kirpik Bantları', 'Kirpik Aparatları', 'Kirpik Lifting Aparatları', 'Kaş Pensleri',
            'Kaş Makasları', 'Kaş Şekillendirme Aletleri', 'Kaş & Kirpik Aksesuarları',
        ],
        'Makyaj Malzemeleri' => [
            'Makyaj Fırçaları', 'Makyaj Süngerleri', 'Makyaj Paletleri', 'Makyaj Aynaları',
            'Kirpik Kıvırıcılar', 'Makyaj Spatulaları', 'Makyaj Karıştırma Paletleri',
            'Makyaj Çantaları', 'Profesyonel Makyaj Setleri',
        ],
        'Salon Hijyen & Koruyucu Malzemeleri' => [
            'Tek Kullanımlık Önlükler', 'Müşteri Pelerinleri', 'Kuaför Önlükleri', 'Boyun Bantları',
            'Eldivenler', 'Maskeler', 'Bone', 'Tek Kullanımlık Havlular', 'Alet Koruyucular',
            'Diğer Koruyucu Malzemeler',
        ],
        'Salon Yardımcı Malzemeleri' => [
            'Sprey Şişeleri', 'Suluklar', 'Pompalı Şişeler', 'Alet Çantaları', 'Kuaför Çantaları',
            'Alet Organizerleri', 'Kozmetik Organizerleri', 'Ürün Tepsileri', 'Malzeme Kapları',
            'Karıştırma Kapları', 'El Aynaları', 'Masa Aynaları', 'Askılıklar',
            'Diğer Salon Yardımcı Malzemeleri',
        ],
        'Profesyonel Setler' => [
            'Kuaför Setleri', 'Berber Setleri', 'Saç Kesim Setleri', 'Boyama Setleri', 'Fön Setleri',
            'Manikür Setleri', 'Pedikür Setleri', 'Makyaj Setleri', 'Kirpik & Kaş Setleri',
            'Salon Başlangıç Setleri',
        ],
    ];

    private array $oldSubToNewSub = [
        'makaslar' => 'Makaslar',
        'taraklar' => 'Taraklar',
        'fircalar' => 'Saç Fırçaları',
        'masa ve duzlestiriciler' => 'Elektrikli Kuaför Aletleri',
        'fon makineleri' => 'Elektrikli Kuaför Aletleri',
        'tiras makineleri' => 'Elektrikli Kuaför Aletleri',
        'agda makineleri' => 'Elektrikli Kuaför Aletleri',
        'sac ve sakal usturalari' => 'Tıraş & Berber Malzemeleri',
        'manikur pedikur setleri' => 'Manikür & Pedikür Malzemeleri',
        'profesyonel et tirnak pensleri' => 'Manikür & Pedikür Malzemeleri',
        'manikur tirnak makaslari' => 'Manikür & Pedikür Malzemeleri',
        'torpuler ponzalar fircalar raspalar' => 'Manikür & Pedikür Malzemeleri',
        'tirnak susleri' => 'Manikür & Pedikür Malzemeleri',
        'takma tirnaklar' => 'Manikür & Pedikür Malzemeleri',
        'firkete toka ve pensler' => 'Saç Tutucular & Şekillendirme Aksesuarları',
        'bigudiler' => 'Saç Tutucular & Şekillendirme Aksesuarları',
        'tek kullanimlik urunler' => 'Salon Hijyen & Koruyucu Malzemeleri',
        'tezgah ustu malzemeler' => 'Salon Yardımcı Malzemeleri',
        'cimbiz' => 'Kirpik & Kaş Malzemeleri',
        'pamuklar' => 'Salon Hijyen & Koruyucu Malzemeleri',
    ];

    private array $productKeywordToChild = [
        'makas' => 'Saç Kesim Makasları',
        'efile' => 'Efile Makasları',
        'tarak' => 'Kesim Tarakları',
        'fon firca' => 'Fön Fırçaları',
        'firca' => 'Profesyonel Saç Fırçaları',
        'fon makine' => 'Fön Makineleri',
        'fon ' => 'Fön Makineleri',
        'masa' => 'Saç Maşaları',
        'duzlestir' => 'Saç Düzleştiriciler',
        'tiras makine' => 'Tıraş Makineleri',
        'ustura' => 'Usturalar',
        'jilet' => 'Jiletler',
        'agda makine' => 'Ağda Isıtıcıları',
        'agda isit' => 'Ağda Isıtıcıları',
        'agda' => 'Ağda Spatulaları',
        'manikur' => 'Manikür Setleri',
        'pedikur' => 'Pedikür Setleri',
        'tirnak pens' => 'Manikür Pensleri',
        'tirnak makas' => 'Tırnak Makasları',
        'torpu' => 'Tırnak Törpüleri',
        'takma tirnak' => 'Manikür & Pedikür Aksesuarları',
        'firkete' => 'Fırkete',
        'toka' => 'Saç Tokaları',
        'pens' => 'Saç Pensleri',
        'bigudi' => 'Bigudiler',
        'eldiven' => 'Eldivenler',
        'bone' => 'Bone',
        'onluk' => 'Kuaför Önlükleri',
        'pelerin' => 'Müşteri Pelerinleri',
        'tek kullanim' => 'Tek Kullanımlık Önlükler',
        'kirpik' => 'Kirpik Pensleri',
        'kas pens' => 'Kaş Pensleri',
        'makyaj firca' => 'Makyaj Fırçaları',
        'sprey' => 'Sprey Şişeleri',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'MOD: APPLY' : 'MOD: DRY-RUN');

        $category = Category::query()->find((int) $this->option('category-id'));
        if (! $category) {
            $this->error('Kategori bulunamadı.');

            return self::FAILURE;
        }

        if ($apply && ! $this->option('skip-backup')) {
            $this->call('categories:backup-kozmetik', [
                'action' => 'backup',
                '--category-id' => (string) $category->id,
            ]);
        }

        $this->line("Kategori: #{$category->id} {$category->name}");

        $createdSubs = 0;
        $createdChildren = 0;
        $subMap = [];
        $childMap = [];

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
                $child = ($sub->id ?? 0)
                    ? $this->findChildByName($category->id, (int) $sub->id, $childName)
                    : null;
                if (! $child) {
                    if ($apply && ($sub->id ?? 0)) {
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

        $oldSubs = SubCategory::query()
            ->where('category_id', $category->id)
            ->get()
            ->keyBy('id');

        $stats = [
            'to_child' => 0,
            'to_sub' => 0,
            'to_category_only' => 0,
            'unchanged' => 0,
            'updated' => 0,
        ];
        $reportLines = [];

        foreach ($products as $product) {
            $target = $this->resolveProductTarget($product, $subMap, $childMap, $oldSubs);
            $newSub = (int) $target['sub_id'];
            $newChild = (int) $target['child_id'];
            $level = $target['level'];
            $curSub = (int) ($product->sub_category_id ?? 0);
            $curChild = (int) ($product->child_category_id ?? 0);

            if ($curSub === $newSub && $curChild === $newChild && (int) $product->category_id === (int) $category->id) {
                $stats['unchanged']++;
                continue;
            }

            $stats[$level === 'child' ? 'to_child' : ($level === 'sub' ? 'to_sub' : 'to_category_only')]++;
            $reportLines[] = "#{$product->id} | {$level} | sub {$curSub}->{$newSub} child {$curChild}->{$newChild} | {$target['reason']} | {$product->name}";

            if ($apply) {
                $product->category_id = $category->id;
                $product->sub_category_id = $newSub;
                $product->child_category_id = $newChild;
                $product->save();
                $stats['updated']++;
            }
        }

        $this->newLine();
        $this->table(['Metrik', 'Adet'], [
            ['Ürün toplam', $products->count()],
            ['Yeni sub', $createdSubs],
            ['Yeni child', $createdChildren],
            ['Child’a', $stats['to_child']],
            ['Sub’a', $stats['to_sub']],
            ['Sadece ana kategori', $stats['to_category_only']],
            ['Değişmeyecek', $stats['unchanged']],
            ['Güncellenen', $stats['updated']],
        ]);

        $reportPath = storage_path('logs/malzemeleri-restructure-'.date('Ymd-His').'.txt');
        file_put_contents($reportPath, implode(PHP_EOL, array_merge(
            ['apply='.($apply ? '1' : '0'), 'category_id='.$category->id, '---'],
            $reportLines
        )));
        $this->line("Rapor: {$reportPath}");

        if (! $apply) {
            $this->warn('Yazılmadı. Uygula: php artisan categories:restructure-malzemeleri --apply');
        } else {
            $this->info('Tamamlandı. Ürün silinmedi.');
        }

        return self::SUCCESS;
    }

    private function findSubByName(int $categoryId, string $name): ?SubCategory
    {
        $norm = $this->normalize($name);
        foreach (SubCategory::query()->where('category_id', $categoryId)->get() as $sub) {
            if ($this->normalize((string) $sub->name) === $norm) {
                return $sub;
            }
        }

        return null;
    }

    private function findChildByName(int $categoryId, int $subId, string $name): ?ChildCategory
    {
        $norm = $this->normalize($name);
        foreach (ChildCategory::query()->where('category_id', $categoryId)->where('sub_category_id', $subId)->get() as $child) {
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

    private function resolveProductTarget(Product $product, array $subMap, array $childMap, $oldSubs): array
    {
        $haystack = $this->normalize((string) $product->name);
        $oldSub = $oldSubs->get((int) ($product->sub_category_id ?? 0));
        $oldSubName = $oldSub ? (string) $oldSub->name : '';
        $preferredSubName = $this->oldSubToNewSub[$this->normalize($oldSubName)] ?? null;

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
                if ($hit) {
                    $subId = (int) ($hit['sub']->id ?? 0);

                    return [
                        'level' => $subId > 0 ? 'sub' : 'category',
                        'sub_id' => $subId,
                        'child_id' => 0,
                        'reason' => "keyword-sub:{$keyword}",
                    ];
                }
            }
        }

        $bestChild = null;
        $bestLen = 0;
        foreach ($childMap as $normChild => $hit) {
            if ($normChild !== '' && str_contains($haystack, $normChild) && mb_strlen($normChild) > $bestLen) {
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

        return [
            'level' => 'category',
            'sub_id' => 0,
            'child_id' => 0,
            'reason' => 'fallback-malzemeleri',
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $map = [
            'ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c',
            '&' => ' ',
        ];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
