<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Kozmetik ürün kategori atamalarını yedekler / geri yükler (ürün silmez).
 */
class BackupKozmetikCategories extends Command
{
    protected $signature = 'categories:backup-kozmetik
                            {action=backup : backup|restore}
                            {--file= : Restore için JSON dosya yolu}
                            {--category-id=3 : Kozmetik category id}
                            {--list : Mevcut yedekleri listele}';

    protected $description = 'Kozmetik ürün category/sub/child atamalarını JSON yedekle veya geri yükle';

    public function handle(): int
    {
        $dir = storage_path('app/backups/kozmetik');
        File::ensureDirectoryExists($dir);

        if ($this->option('list')) {
            $files = collect(File::files($dir))
                ->filter(fn ($f) => str_ends_with($f->getFilename(), '.json'))
                ->sortByDesc(fn ($f) => $f->getMTime())
                ->values();
            if ($files->isEmpty()) {
                $this->warn('Yedek yok: '.$dir);

                return self::SUCCESS;
            }
            foreach ($files as $f) {
                $this->line($f->getPathname().' ('.number_format($f->getSize() / 1024, 1).' KB)');
            }

            return self::SUCCESS;
        }

        $action = strtolower((string) $this->argument('action'));
        if ($action === 'backup') {
            return $this->backup($dir);
        }
        if ($action === 'restore') {
            return $this->restore($dir);
        }

        $this->error('action: backup | restore');

        return self::FAILURE;
    }

    private function backup(string $dir): int
    {
        $categoryId = (int) $this->option('category-id');
        $category = Category::query()->find($categoryId);
        if (! $category) {
            $this->error("Kategori #{$categoryId} yok.");

            return self::FAILURE;
        }

        $products = Product::query()
            ->where('category_id', $categoryId)
            ->orderBy('id')
            ->get(['id', 'category_id', 'sub_category_id', 'child_category_id', 'name']);

        $subs = SubCategory::query()
            ->where('category_id', $categoryId)
            ->orderBy('id')
            ->get(['id', 'category_id', 'name', 'slug', 'status']);

        $children = ChildCategory::query()
            ->where('category_id', $categoryId)
            ->orderBy('id')
            ->get(['id', 'category_id', 'sub_category_id', 'name', 'slug', 'status']);

        $payload = [
            'created_at' => now()->toIso8601String(),
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'products' => $products->map(fn ($p) => [
                'id' => (int) $p->id,
                'category_id' => (int) $p->category_id,
                'sub_category_id' => (int) ($p->sub_category_id ?? 0),
                'child_category_id' => (int) ($p->child_category_id ?? 0),
                'name' => (string) $p->name,
            ])->all(),
            'sub_categories' => $subs->toArray(),
            'child_categories' => $children->toArray(),
        ];

        $file = $dir.'/kozmetik-cats-'.date('Ymd-His').'.json';
        File::put($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->info('Yedek alındı (ürün silinmedi, sadece kategori alanları).');
        $this->line("Dosya: {$file}");
        $this->line('Ürün: '.$products->count().' | Sub: '.$subs->count().' | Child: '.$children->count());
        $this->newLine();
        $this->line('Geri almak için:');
        $this->line("php artisan categories:backup-kozmetik restore --file=\"{$file}\"");

        return self::SUCCESS;
    }

    private function restore(string $dir): int
    {
        $file = (string) ($this->option('file') ?: '');
        if ($file === '') {
            $latest = collect(File::files($dir))
                ->filter(fn ($f) => str_ends_with($f->getFilename(), '.json'))
                ->sortByDesc(fn ($f) => $f->getMTime())
                ->first();
            if (! $latest) {
                $this->error('Yedek dosyası yok. --file= verin veya önce backup alın.');

                return self::FAILURE;
            }
            $file = $latest->getPathname();
            $this->warn("En son yedek kullanılıyor: {$file}");
        }

        if (! is_readable($file)) {
            $this->error("Okunamıyor: {$file}");

            return self::FAILURE;
        }

        $payload = json_decode(File::get($file), true);
        if (! is_array($payload) || empty($payload['products'])) {
            $this->error('Geçersiz yedek JSON.');

            return self::FAILURE;
        }

        if (! $this->confirm('Ürün category/sub/child alanları bu yedeğe dönecek. Devam?', true)) {
            $this->warn('İptal.');

            return self::SUCCESS;
        }

        $updated = 0;
        $missing = 0;

        DB::transaction(function () use ($payload, &$updated, &$missing) {
            foreach ($payload['products'] as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $affected = Product::query()->where('id', $id)->update([
                    'category_id' => (int) ($row['category_id'] ?? 0),
                    'sub_category_id' => (int) ($row['sub_category_id'] ?? 0),
                    'child_category_id' => (int) ($row['child_category_id'] ?? 0),
                ]);
                if ($affected) {
                    $updated++;
                } else {
                    $missing++;
                }
            }
        });

        $this->info("Geri yüklendi: {$updated} ürün güncellendi.");
        if ($missing > 0) {
            $this->warn("Bulunamayan ürün id: {$missing} (silinmiş olabilir; diğerleri restore edildi).");
        }
        $this->line('Not: Yedekten sonra eklenen yeni sub/child kayıtları silinmez; sadece ürün atamaları eski haline döner.');

        return self::SUCCESS;
    }
}
