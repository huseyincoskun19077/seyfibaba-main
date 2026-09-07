<?php

namespace App\Console\Commands;

use App\Models\ChildCategory;
use App\Models\MegaMenuSubCategory;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldMalzemeleriSubs extends Command
{
    protected $signature = 'categories:cleanup-old-malzemeleri-subs
                            {--category-id=2}
                            {--deactivate}
                            {--delete}
                            {--force-move-leftovers}';

    protected $description = 'Kuaför Malzemeleri eski boş sub’ları raporlar / temizler (ürün silmez)';

    private array $keepSubNames = [
        'Makaslar', 'Taraklar', 'Saç Fırçaları', 'Saç Kesim Malzemeleri',
        'Saç Tutucular & Şekillendirme Aksesuarları', 'Elektrikli Kuaför Aletleri',
        'Tıraş & Berber Malzemeleri', 'Saç Boyama & Röfle Malzemeleri',
        'Perma & Saç İşlem Malzemeleri', 'Manikür & Pedikür Malzemeleri',
        'Ağda & Epilasyon Malzemeleri', 'Cilt Bakım Malzemeleri',
        'Kirpik & Kaş Malzemeleri', 'Makyaj Malzemeleri',
        'Salon Hijyen & Koruyucu Malzemeleri', 'Salon Yardımcı Malzemeleri',
        'Profesyonel Setler',
    ];

    public function handle(): int
    {
        $categoryId = (int) $this->option('category-id');
        $keepNorms = [];
        foreach ($this->keepSubNames as $name) {
            $keepNorms[$this->normalize($name)] = $name;
        }

        $subs = SubCategory::query()->where('category_id', $categoryId)->orderBy('id')->get();
        $newRows = [];
        $oldRows = [];
        $newTotal = 0;
        $oldTotal = 0;
        $oldEmpty = 0;
        $oldFull = 0;

        foreach ($subs as $sub) {
            $cnt = (int) Product::query()->where('category_id', $categoryId)->where('sub_category_id', $sub->id)->count();
            $isNew = isset($keepNorms[$this->normalize((string) $sub->name)]);
            $row = [$sub->id, $sub->name, $sub->status, $cnt];
            if ($isNew) {
                $newRows[] = $row;
                $newTotal += $cnt;
            } else {
                $oldRows[] = $row;
                $oldTotal += $cnt;
                $cnt > 0 ? $oldFull++ : $oldEmpty++;
            }
        }

        $orphan = (int) Product::query()
            ->where('category_id', $categoryId)
            ->where(function ($q) {
                $q->whereNull('sub_category_id')->orWhere('sub_category_id', 0);
            })->count();

        $this->info('YENİ ağaç');
        $this->table(['id', 'name', 'status', 'ürün'], $newRows);
        $this->line("Yeni ürün toplamı: {$newTotal}");
        $this->warn('ESKİ sub’lar');
        $this->table(['id', 'name', 'status', 'ürün'], $oldRows);
        $this->line("Eski ürün: {$oldTotal} | dolu: {$oldFull} | boş: {$oldEmpty} | sadece ana: {$orphan}");

        if ($this->option('force-move-leftovers') && $oldTotal > 0) {
            foreach ($oldRows as [$id, $name, , $cnt]) {
                if ($cnt <= 0) {
                    continue;
                }
                $n = Product::query()->where('category_id', $categoryId)->where('sub_category_id', $id)
                    ->update(['sub_category_id' => 0, 'child_category_id' => 0]);
                $this->line("leftover #{$id} {$name}: {$n}");
            }
        }

        if (! $this->option('deactivate') && ! $this->option('delete')) {
            $this->comment('Rapor. Sonra: --deactivate / --delete (sadece ürün=0)');

            return self::SUCCESS;
        }

        $deactivated = 0;
        $deleted = 0;
        $skipped = 0;
        foreach ($subs->filter(fn ($s) => ! isset($keepNorms[$this->normalize((string) $s->name)])) as $sub) {
            $cnt = (int) Product::query()->where('category_id', $categoryId)->where('sub_category_id', $sub->id)->count();
            if ($cnt > 0) {
                $skipped++;
                $this->warn("Atlandı #{$sub->id} {$sub->name} ({$cnt})");
                continue;
            }
            if ($this->option('deactivate') && (int) $sub->status !== 0) {
                $sub->status = 0;
                $sub->save();
                $deactivated++;
            }
            if ($this->option('delete')) {
                DB::transaction(function () use ($sub, &$deleted) {
                    ChildCategory::query()->where('sub_category_id', $sub->id)->delete();
                    MegaMenuSubCategory::query()->where('sub_category_id', $sub->id)->delete();
                    $sub->delete();
                    $deleted++;
                });
                $this->line("Silindi #{$sub->id} {$sub->name}");
            }
        }

        $this->table(['Metrik', 'Adet'], [
            ['Pasif', $deactivated],
            ['Silinen', $deleted],
            ['Atlanan', $skipped],
        ]);

        return self::SUCCESS;
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
