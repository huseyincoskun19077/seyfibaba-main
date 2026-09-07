<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MegaMenuCategory;
use App\Models\MegaMenuSubCategory;
use App\Models\SubCategory;
use Illuminate\Console\Command;

/**
 * Kozmetik aktif sub’larını mega menüye ekler (canlı menü bunlardan okur).
 */
class SyncKozmetikMegaMenu extends Command
{
    protected $signature = 'categories:sync-kozmetik-mega-menu
                            {--category-id=3 : Ana kategori id}
                            {--replace-old : Eski (md dışı) mega sub kayıtlarını kaldır}
                            {--dry-run : Yazmadan raporla}';

    protected $description = 'Yeni Kozmetik sub kategorilerini mega_menu_sub_categories tablosuna ekler';

    /** Yeni ağaçtaki sub isimleri (normalize ile eşlenir) */
    private array $newSubNames = [
        'Saç Bakımı', 'Saç Boyama', 'Saç Şekillendirme', 'Profesyonel Saç İşlemleri',
        'Erkek Bakım / Berber', 'Cilt Bakımı', 'Kirpik & Kaş', 'Tırnak',
        'Ağda & Epilasyon', 'Makyaj', 'Vücut Bakımı', 'Hijyen & Sarf',
        'Salon Sarf Malzemeleri', 'Parfüm & Koku',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $categoryId = (int) $this->option('category-id');

        $mega = MegaMenuCategory::query()
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->first();

        if (! $mega) {
            $this->error("Mega menüde category_id={$categoryId} kaydı yok. Admin → Mega Menu’den Kozmetik ekleyin.");

            return self::FAILURE;
        }

        $this->info("MegaMenuCategory #{$mega->id} (category_id={$categoryId})");

        $wantedNorms = [];
        foreach ($this->newSubNames as $name) {
            $wantedNorms[$this->normalize($name)] = $name;
        }

        $subs = SubCategory::query()
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        $serial = (int) MegaMenuSubCategory::query()
            ->where('mega_menu_category_id', $mega->id)
            ->max('serial');

        $added = 0;
        $skipped = 0;

        foreach ($subs as $sub) {
            $norm = $this->normalize((string) $sub->name);
            if (! isset($wantedNorms[$norm])) {
                continue;
            }

            $exists = MegaMenuSubCategory::query()
                ->where('mega_menu_category_id', $mega->id)
                ->where('sub_category_id', $sub->id)
                ->exists();

            if ($exists) {
                $skipped++;
                $this->line("  = zaten var: {$sub->name}");
                continue;
            }

            $serial++;
            if ($dry) {
                $this->line("  [dry] eklenecek: {$sub->name} serial={$serial}");
            } else {
                MegaMenuSubCategory::query()->create([
                    'mega_menu_category_id' => $mega->id,
                    'sub_category_id' => $sub->id,
                    'status' => 1,
                    'serial' => $serial,
                ]);
                $this->line("  + eklendi: {$sub->name}");
            }
            $added++;
        }

        $removed = 0;
        if ($this->option('replace-old')) {
            $keepIds = $subs
                ->filter(fn ($s) => isset($wantedNorms[$this->normalize((string) $s->name)]))
                ->pluck('id')
                ->all();

            $oldRows = MegaMenuSubCategory::query()
                ->where('mega_menu_category_id', $mega->id)
                ->when(count($keepIds) > 0, fn ($q) => $q->whereNotIn('sub_category_id', $keepIds))
                ->get();

            foreach ($oldRows as $row) {
                $name = optional($row->subCategory)->name ?? ('sub#'.$row->sub_category_id);
                if ($dry) {
                    $this->line("  [dry] kaldırılacak eski: {$name}");
                } else {
                    $row->delete();
                    $this->line("  - kaldırıldı eski: {$name}");
                }
                $removed++;
            }
        }

        $this->newLine();
        $this->table(['Metrik', 'Adet'], [
            ['Eklenecek/eklenen yeni', $added],
            ['Zaten vardı', $skipped],
            ['Eski kaldırılan', $removed],
        ]);

        if ($dry) {
            $this->warn('Dry-run. Uygula: php artisan categories:sync-kozmetik-mega-menu');
            $this->warn('Eski menü satırlarını da temizlemek için: --replace-old');
        } else {
            $this->info('Tamam. Tarayıcıda hard refresh (Ctrl+F5) veya gizli pencere deneyin.');
            $this->line('İsteğe bağlı: cd /opt/seyfibaba-main/frontend && pm2 restart sey-frontend');
        }

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
