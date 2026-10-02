<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Aktif kategori → alt kategori → child ağacını markdown/json olarak basar.
 *
 * Kullanım (sunucu):
 *   php artisan categories:export-tree
 *   php artisan categories:export-tree --ids
 *   php artisan categories:export-tree --format=json --output=storage/app/category-tree.json
 */
class ExportCategoryTree extends Command
{
    protected $signature = 'categories:export-tree
                            {--format=md : md|json}
                            {--ids : İsimlerin yanına id yaz}
                            {--all : Pasif kayıtları da dahil et}
                            {--category= : Tek ana kategori (id veya isim)}
                            {--output= : Dosya yolu (boşsa stdout)}';

    protected $description = 'Aktif kategori / alt kategori / child ağacını markdown veya JSON basar';

    public function handle(): int
    {
        $includeInactive = (bool) $this->option('all');
        $withIds = (bool) $this->option('ids');
        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['md', 'json'], true)) {
            $this->error('format: md | json');

            return self::FAILURE;
        }

        $query = Category::query()->ordered();
        if (! $includeInactive) {
            $query->active();
        }

        $filter = trim((string) $this->option('category'));
        if ($filter !== '') {
            if (ctype_digit($filter)) {
                $query->where('id', (int) $filter);
            } else {
                $query->where('name', $filter);
            }
        }

        $categories = $query
            ->with([
                $includeInactive ? 'subCategories.childCategories' : 'activeSubCategories.activeChildCategories',
            ])
            ->get();

        if ($categories->isEmpty()) {
            $this->error('Kategori bulunamadı.');

            return self::FAILURE;
        }

        $tree = [];
        foreach ($categories as $category) {
            $subs = $includeInactive ? $category->subCategories : $category->activeSubCategories;
            $subNodes = [];
            foreach ($subs as $sub) {
                $children = $includeInactive ? $sub->childCategories : $sub->activeChildCategories;
                $childNodes = [];
                foreach ($children as $child) {
                    $childNodes[] = [
                        'id' => (int) $child->id,
                        'name' => (string) $child->name,
                        'slug' => (string) ($child->slug ?? ''),
                        'status' => (int) ($child->status ?? 1),
                    ];
                }
                $subNodes[] = [
                    'id' => (int) $sub->id,
                    'name' => (string) $sub->name,
                    'slug' => (string) ($sub->slug ?? ''),
                    'status' => (int) ($sub->status ?? 1),
                    'children' => $childNodes,
                ];
            }
            $tree[] = [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'slug' => (string) ($category->slug ?? ''),
                'status' => (int) ($category->status ?? 1),
                'sub_categories' => $subNodes,
            ];
        }

        $payload = $format === 'json'
            ? json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
            : $this->toMarkdown($tree, $withIds);

        $output = (string) $this->option('output');
        if ($output !== '') {
            $path = str_starts_with($output, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $output)
                ? $output
                : base_path($output);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $payload);
            $this->info('Yazıldı: '.$path);
        } else {
            $this->line($payload);
        }

        $catCount = count($tree);
        $subCount = 0;
        $childCount = 0;
        foreach ($tree as $cat) {
            $subCount += count($cat['sub_categories']);
            foreach ($cat['sub_categories'] as $sub) {
                $childCount += count($sub['children']);
            }
        }
        $this->comment("Toplam: {$catCount} kategori, {$subCount} alt kategori, {$childCount} child");

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $tree
     */
    private function toMarkdown(array $tree, bool $withIds): string
    {
        $lines = [];
        $scope = $this->option('all') ? 'tüm' : 'aktif';
        $lines[] = '# Kategori ağacı ('.$scope.')';
        $lines[] = '';
        $lines[] = 'Kaynak: canlı veritabanı · '.now()->toDateTimeString();
        $lines[] = '';

        foreach ($tree as $catIndex => $category) {
            if ($catIndex > 0) {
                $lines[] = '';
            }
            $lines[] = $this->label($category, $withIds);
            $subs = $category['sub_categories'];
            if ($subs === []) {
                $lines[] = '│';
                $lines[] = '└── (alt kategori yok)';
                continue;
            }
            $lines[] = '│';
            $lastSub = count($subs) - 1;
            foreach ($subs as $subIndex => $sub) {
                $isLastSub = $subIndex === $lastSub;
                $subPrefix = $isLastSub ? '└── ' : '├── ';
                $childPad = $isLastSub ? '    ' : '│   ';
                $lines[] = $subPrefix.$this->label($sub, $withIds);
                $children = $sub['children'];
                if ($children === []) {
                    if (! $isLastSub) {
                        $lines[] = '│';
                    }
                    continue;
                }
                $lastChild = count($children) - 1;
                foreach ($children as $childIndex => $child) {
                    $childBranch = $childIndex === $lastChild ? '└── ' : '├── ';
                    $lines[] = $childPad.$childBranch.$this->label($child, $withIds);
                }
                if (! $isLastSub) {
                    $lines[] = '│';
                }
            }
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param  array{id:int,name:string}  $node
     */
    private function label(array $node, bool $withIds): string
    {
        $name = trim((string) $node['name']);
        if (! $withIds) {
            return $name;
        }

        return $name.'  [id:'.$node['id'].']';
    }
}
