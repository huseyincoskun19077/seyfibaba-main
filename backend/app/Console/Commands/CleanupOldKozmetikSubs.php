<?php

namespace App\Console\Commands;

use App\Models\ChildCategory;
use App\Models\MegaMenuSubCategory;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kozmetik: yeni ağaç vs eski sub’ları raporlar; boş eskileri pasifleştirir/siler.
 */
class CleanupOldKozmetikSubs extends Command
{
    protected $signature = 'categories:cleanup-old-kozmetik-subs
                            {--category-id=3 : Kozmetik category id}
                            {--deactivate : Ürünü 0 olan eski sub’ları status=0 yap}
                            {--delete : Ürünü 0 olan eski sub’ları sil (önce --deactivate önerilir)}
                            {--force-move-leftovers : Eski sub’ta kalan ürünleri sadece Kozmetik’e al (sub/child=0)}';

    protected $description = 'Kozmetik eski alt kategorileri raporlar; boş olanları temizler (ürün silmez)';

    private array $keepSubNames = [
        'Saç Bakımı', 'Saç Boyama', 'Saç Şekillendirme', 'Profesyonel Saç İşlemleri',
        'Erkek Bakım / Berber', 'Cilt Bakımı', 'Kirpik & Kaş', 'Tırnak',
        'Ağda & Epilasyon', 'Makyaj', 'Vücut Bakımı', 'Hijyen & Sarf',
        'Salon Sarf Malzemeleri', 'Parfüm & Koku',
    ];

    public function handle(): int
    {
        $categoryId = (int) $this->option('category-id');
        $keepNorms = [];
        foreach ($this->keepSubNames as $name) {
            $keepNorms[$this->normalize($name)] = $name;
        }

        $subs = SubCategory::query()
            ->where('category_id', $categoryId)
            ->orderBy('id')
            ->get();

        $newRows = [];
        $oldRows = [];
        $oldWithProducts = 0;
        $oldEmpty = 0;
        $newProductTotal = 0;
        $oldProductTotal = 0;

        foreach ($subs as $sub) {
            $cnt = (int) Product::query()
                ->where('category_id', $categoryId)
                ->where('sub_category_id', $sub->id)
                ->count();
            $isNew = isset($keepNorms[$this->normalize((string) $sub->name)]);
            $row = [
                $sub->id,
                $sub->name,
                $sub->status,
                $cnt,
            ];
            if ($isNew) {
                $newRows[] = $row;
                $newProductTotal += $cnt;
            } else {
                $oldRows[] = $row;
                $oldProductTotal += $cnt;
                if ($cnt > 0) {
                    $oldWithProducts++;
                } else {
                    $oldEmpty++;
                }
            }
        }

        $orphan = (int) Product::query()
            ->where('category_id', $categoryId)
            ->where(function ($q) {
                $q->whereNull('sub_category_id')->orWhere('sub_category_id', 0);
            })
            ->count();

        $this->info('YENİ ağaç (tutulacak)');
        $this->table(['id', 'name', 'status', 'ürün'], $newRows);
        $this->line('Yeni sub ürün toplamı: '.$newProductTotal);

        $this->newLine();
        $this->warn('ESKİ sub’lar (temizlenebilir)');
        $this->table(['id', 'name', 'status', 'ürün'], $oldRows);
        $this->line("Eski sub ürün toplamı: {$oldProductTotal} | dolu: {$oldWithProducts} | boş: {$oldEmpty}");
        $this->line("Sadece Kozmetik (sub yok): {$orphan}");

        if ($this->option('force-move-leftovers') && $oldProductTotal > 0) {
            $moved = 0;
            foreach ($oldRows as [$id, $name, $status, $cnt]) {
                if ($cnt <= 0) {
                    continue;
                }
                $n = Product::query()
                    ->where('category_id', $categoryId)
                    ->where('sub_category_id', $id)
                    ->update([
                        'sub_category_id' => 0,
                        'child_category_id' => 0,
                    ]);
                $moved += $n;
                $this->line("  → leftover #{$id} {$name}: {$n} ürün Kozmetik’e alındı");
            }
            $this->info("Toplam leftover taşınan: {$moved}");
        }

        $deactivate = $this->option('deactivate');
        $delete = $this->option('delete');

        if (! $deactivate && ! $delete) {
            $this->newLine();
            $this->comment('Şimdilik sadece rapor. Sonraki adımlar:');
            $this->line('  1) Eski dolu varsa: --force-move-leftovers');
            $this->line('  2) Boş eskileri kapat: --deactivate');
            $this->line('  3) Boş eskileri sil: --delete');

            return self::SUCCESS;
        }

        $targets = SubCategory::query()
            ->where('category_id', $categoryId)
            ->get()
            ->filter(fn ($s) => ! isset($keepNorms[$this->normalize((string) $s->name)]));

        $deactivated = 0;
        $deleted = 0;
        $skipped = 0;

        foreach ($targets as $sub) {
            $cnt = (int) Product::query()
                ->where('category_id', $categoryId)
                ->where('sub_category_id', $sub->id)
                ->count();

            if ($cnt > 0) {
                $skipped++;
                $this->warn("Atlandı (ürün var): #{$sub->id} {$sub->name} ({$cnt})");
                continue;
            }

            if ($deactivate && (int) $sub->status !== 0) {
                $sub->status = 0;
                $sub->save();
                $deactivated++;
                $this->line("Pasif: #{$sub->id} {$sub->name}");
            }

            if ($delete) {
                DB::transaction(function () use ($sub, &$deleted) {
                    ChildCategory::query()->where('sub_category_id', $sub->id)->delete();
                    MegaMenuSubCategory::query()->where('sub_category_id', $sub->id)->delete();
                    $sub->delete();
                    $deleted++;
                });
                $this->line("Silindi: #{$sub->id} {$sub->name}");
            }
        }

        $this->newLine();
        $this->table(['Metrik', 'Adet'], [
            ['Pasifleştirilen', $deactivated],
            ['Silinen', $deleted],
            ['Atlanan (ürünlü)', $skipped],
        ]);

        return self::SUCCESS;
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
