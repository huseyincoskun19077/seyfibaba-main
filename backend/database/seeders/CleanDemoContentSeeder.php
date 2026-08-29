<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanDemoContentSeeder extends Seeder
{
    /**
     * Shopo temasından kalan tüm demo içerikleri temizler.
     * Slider, banner, marka, flash sale, mega menu vb.
     *
     * Kullanım: php artisan db:seed --class=CleanDemoContentSeeder
     */
    public function run(): void
    {
        // 1. Demo slider'ları temizle (2022 tarihli dosyalar mevcut değil)
        $sliderCount = DB::table('sliders')->where('image', 'LIKE', '%2022%')->count();
        DB::table('sliders')->where('image', 'LIKE', '%2022%')->delete();
        $this->command->info("✓ {$sliderCount} demo slider silindi.");

        // 2. Demo markaları temizle (Oneplus, Tesla, Firefox vb.)
        $demoBrands = [
            'Oneplus', 'Tencent', 'Apple', 'Mircrosoft', 'lenovo',
            'Huawei', 'Nexus', 'Google', 'Firefox', 'Tesla', 'Brave', 'Facebook',
        ];
        $brandCount = DB::table('brands')->whereIn('name', $demoBrands)->count();
        DB::table('brands')->whereIn('name', $demoBrands)->delete();
        $this->command->info("✓ {$brandCount} demo marka silindi.");

        // 3. Flash sale'i devre dışı bırak (demo ürünler silindiği için)
        DB::table('flash_sales')->update(['status' => 0]);
        DB::table('flash_sale_products')->truncate();
        $this->command->info("✓ Flash sale devre dışı bırakıldı.");

        // 4. Demo mega menu kategorilerini temizle (2022 tarihli resimler)
        $megaMenuCount = DB::table('mega_menu_categories')->count();
        if ($megaMenuCount > 0) {
            DB::table('mega_menu_sub_categories')->truncate();
            DB::table('mega_menu_categories')->truncate();
            $this->command->info("✓ {$megaMenuCount} demo mega menu kategorisi temizlendi.");
        }

        // 5. Demo banner resimlerini null yap (dosyalar mevcut değil)
        $bannerIds = [16, 17, 18, 19, 20, 21, 22, 23]; // Mega menu banner'ları
        DB::table('banner_images')
            ->whereIn('id', $bannerIds)
            ->where('image', 'LIKE', '%2022%')
            ->update(['image' => '']);
        $this->command->info("✓ Demo mega menu banner görselleri temizlendi.");

        // 6. Flash sale görselleri temizle
        try {
            DB::table('flash_sales')->update(['homepage_image' => '', 'flashsale_page_image' => '']);
            $this->command->info("✓ Flash sale görselleri temizlendi.");
        } catch (\Throwable $e) {}

        // 7. Kategori demo görselleri temizle
        $catCount = DB::table('categories')->where('image', 'LIKE', '%2022%')->count();
        DB::table('categories')->where('image', 'LIKE', '%2022%')->update(['image' => '']);
        if ($catCount > 0) {
            $this->command->info("✓ {$catCount} kategori demo görseli temizlendi.");
        }

        // 8. Kalan banner ve breadcrumb görselleri temizle
        DB::table('banner_images')->where('image', 'LIKE', '%2022%')->update(['image' => '']);
        DB::table('breadcrumb_images')->where('image', 'LIKE', '%2022%')->update(['image' => '']);
        $this->command->info("✓ Demo banner ve breadcrumb görselleri temizlendi.");

        $this->command->info('');
        $this->command->info('Demo içerik temizliği tamamlandı.');
        $this->command->info('Yeni slider, marka ve banner görselleri admin panelden yüklenebilir.');
    }
}
