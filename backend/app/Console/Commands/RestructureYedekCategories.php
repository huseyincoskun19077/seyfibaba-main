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
 * Kuaför Yedek Parçaları (#4) — yedek.md ağacı + ürün eşleme (silmeden).
 */
class RestructureYedekCategories extends Command
{
    protected $signature = 'categories:restructure-yedek
                            {--apply : Yaz}
                            {--category-id=4}
                            {--skip-backup}';

    protected $description = 'Kuaför Yedek Parçaları alt kategorilerini yedek.md’ye göre oluşturur ve ürünleri atar';

    /** @var array<string, list<string>> */
    private array $tree = [
        'Kuaför & Berber Koltuğu Yedek Parçaları' => [
            'Hidrolik Sistemler', 'Koltuk Ayakları', 'Koltuk Tabanları', 'Taban Sacları',
            'Sırt Amortisörleri', 'Koltuk Yatırma Mekanizmaları', 'Kolçaklar', 'Ayaklıklar',
            'Ayak Basma Yerleri', 'Koltuk Mekanizmaları', 'Diğer Koltuk Parçaları',
        ],
        'Yıkama Ünitesi Yedek Parçaları' => [
            'Yıkama Lavaboları', 'Yıkama Hazneleri', 'Musluklar', 'Duş Başlıkları',
            'Duş Hortumları', 'Gider & Sifon', 'Süzgeçler', 'Su Bağlantı Parçaları',
            'Diğer Yıkama Parçaları',
        ],
        'Kuaför Makine & Cihaz Yedek Parçaları' => [
            'Fön Makinesi Parçaları', 'Saç & Sakal Makinesi Parçaları', 'Saç Düzleştirici Parçaları',
            'Saç Maşası Parçaları', 'Manikür & Pedikür Cihazı Parçaları', 'Cilt Bakım Cihazı Parçaları',
            'Sterilizasyon Cihazı Parçaları', 'Buhar & Ozon Cihazı Parçaları',
            'Makine Bıçakları & Başlıkları', 'Makine Motorları', 'Makine Kablo & Adaptörleri',
            'Diğer Makine & Cihaz Parçaları',
        ],
    ];

    private array $productKeywordToChild = [
        'hidrolik' => 'Hidrolik Sistemler',
        'koltuk ayak' => 'Koltuk Ayakları',
        'ayak basma' => 'Ayak Basma Yerleri',
        'ayaklik' => 'Ayaklıklar',
        'taban sac' => 'Taban Sacları',
        'taban lastik' => 'Diğer Koltuk Parçaları',
        'koltuk taban' => 'Koltuk Tabanları',
        'amatisor' => 'Sırt Amortisörleri',
        'amortisor' => 'Sırt Amortisörleri',
        'sirt' => 'Sırt Amortisörleri',
        'kolcak' => 'Kolçaklar',
        'yatirma' => 'Koltuk Yatırma Mekanizmaları',
        'mekanizma' => 'Koltuk Mekanizmaları',
        'lavabo' => 'Yıkama Lavaboları',
        'yikama' => 'Yıkama Lavaboları',
        'musluk' => 'Musluklar',
        'dus baslik' => 'Duş Başlıkları',
        'hortum' => 'Duş Hortumları',
        'sifon' => 'Gider & Sifon',
        'suzgec' => 'Süzgeçler',
        'fon askilik' => 'Fön Makinesi Parçaları',
        'fon ' => 'Fön Makinesi Parçaları',
        'bıcak' => 'Makine Bıçakları & Başlıkları',
        'baslik' => 'Makine Bıçakları & Başlıkları',
        'motor' => 'Makine Motorları',
        'adapto' => 'Makine Kablo & Adaptörleri',
        'kablo' => 'Makine Kablo & Adaptörleri',
        'masa' => 'Saç Maşası Parçaları',
        'duzlestir' => 'Saç Düzleştirici Parçaları',
    ];

    private array $productKeywordToSub = [
        'koltuk' => 'Kuaför & Berber Koltuğu Yedek Parçaları',
        'ayak' => 'Kuaför & Berber Koltuğu Yedek Parçaları',
        'taban' => 'Kuaför & Berber Koltuğu Yedek Parçaları',
        'amatisor' => 'Kuaför & Berber Koltuğu Yedek Parçaları',
        'yikama' => 'Yıkama Ünitesi Yedek Parçaları',
        'lavabo' => 'Yıkama Ünitesi Yedek Parçaları',
        'musluk' => 'Yıkama Ünitesi Yedek Parçaları',
        'fon' => 'Kuaför Makine & Cihaz Yedek Parçaları',
        'makine' => 'Kuaför Makine & Cihaz Yedek Parçaları',
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
                        $child = (object) ['id' => 0, 'name' => $childName];
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

        $stats = [
            'to_child' => 0,
            'to_sub' => 0,
            'to_category_only' => 0,
            'unchanged' => 0,
            'updated' => 0,
        ];
        $reportLines = [];

        foreach ($products as $product) {
            $target = $this->resolveProductTarget($product, $subMap, $childMap);
            $newSub = (int) $target['sub_id'];
            $newChild = (int) $target['child_id'];
            $level = $target['level'];
            $curSub = (int) ($product->sub_category_id ?? 0);
            $curChild = (int) ($product->child_category_id ?? 0);

            if ($curSub === $newSub && $curChild === $newChild) {
                $stats['unchanged']++;
                continue;
            }

            $stats[$level === 'child' ? 'to_child' : ($level === 'sub' ? 'to_sub' : 'to_category_only')]++;
            $reportLines[] = "#{$product->id} | {$level} | {$target['reason']} | {$product->name}";

            if ($apply) {
                $product->category_id = $category->id;
                $product->sub_category_id = $newSub;
                $product->child_category_id = $newChild;
                $product->save();
                $stats['updated']++;
            }
        }

        $this->table(['Metrik', 'Adet'], [
            ['Ürün', $products->count()],
            ['Yeni sub', $createdSubs],
            ['Yeni child', $createdChildren],
            ['Child’a', $stats['to_child']],
            ['Sub’a', $stats['to_sub']],
            ['Sadece ana', $stats['to_category_only']],
            ['Değişmeyecek', $stats['unchanged']],
            ['Güncellenen', $stats['updated']],
        ]);

        $path = storage_path('logs/yedek-restructure-'.date('Ymd-His').'.txt');
        file_put_contents($path, implode(PHP_EOL, array_merge(
            ['apply='.($apply ? '1' : '0'), '---'],
            $reportLines
        )));
        $this->line("Rapor: {$path}");

        if (! $apply) {
            $this->warn('Yazılmadı. php artisan categories:restructure-yedek --apply');
        } else {
            $this->info('Tamam. Ürün silinmedi.');
        }

        return self::SUCCESS;
    }

    private function resolveProductTarget(Product $product, array $subMap, array $childMap): array
    {
        $haystack = $this->normalize((string) $product->name);

        foreach ($this->productKeywordToChild as $keyword => $childName) {
            $kn = $this->normalize($keyword);
            if ($kn === '' || ! str_contains($haystack, $kn)) {
                continue;
            }
            $hit = $childMap[$this->normalize($childName)] ?? null;
            if ($hit && ($hit['child']->id ?? 0)) {
                return [
                    'level' => 'child',
                    'sub_id' => (int) $hit['sub']->id,
                    'child_id' => (int) $hit['child']->id,
                    'reason' => "keyword→{$childName}",
                ];
            }
            if ($hit && ($hit['sub']->id ?? 0)) {
                return [
                    'level' => 'sub',
                    'sub_id' => (int) $hit['sub']->id,
                    'child_id' => 0,
                    'reason' => "keyword-sub→{$hit['sub_name']}",
                ];
            }
        }

        foreach ($this->productKeywordToSub as $keyword => $subName) {
            $kn = $this->normalize($keyword);
            if ($kn === '' || ! str_contains($haystack, $kn)) {
                continue;
            }
            $sub = $subMap[$this->normalize($subName)] ?? null;
            if ($sub && ($sub->id ?? 0)) {
                return [
                    'level' => 'sub',
                    'sub_id' => (int) $sub->id,
                    'child_id' => 0,
                    'reason' => "keyword→sub:{$subName}",
                ];
            }
        }

        return [
            'level' => 'category',
            'sub_id' => 0,
            'child_id' => 0,
            'reason' => 'fallback-yedek',
        ];
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

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $map = ['ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c', '&' => ' '];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
