<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sunucuda dosyası olmayan blog kapak görsellerini mevcut dosyalarla değiştirir.
 */
class FixMissingBlogImagesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('blogs')) {
            $this->command?->warn('blogs tablosu yok, atlandı.');

            return;
        }

        $replacements = [
            'uploads/custom-images/kuafor-ekipmanlari-2025-12-18-04-41-03-2600.png'
                => 'uploads/custom-images/profesyonel-sac-makasi-2026-03-26-09-21-54-9130.jpg',
            'uploads/custom-images/kuafor-malzemeleri-2025-12-18-04-43-36-7379.png'
                => 'uploads/custom-images/ikili-erkek-kuafor-tezgahi-profesyonel-berber-calisma-tezgahi-1773652911-1645.jpg',
        ];

        $total = 0;
        foreach ($replacements as $missing => $fallback) {
            $count = DB::table('blogs')->where('image', $missing)->update([
                'image' => $fallback,
                'updated_at' => now(),
            ]);
            $total += $count;
            $this->command?->info("{$count} blog: {$missing} → {$fallback}");
        }

        $this->command?->info("Toplam {$total} blog görseli düzeltildi.");
    }
}
