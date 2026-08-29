<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\DB;

class CleanDemoProductsSeeder extends Seeder
{
    /**
     * Alakasız demo ürünleri (Xbox, PlayStation, MacBook vb.) temizler.
     * Sipariş bağlantısı olan ürünler korunur.
     *
     * Kullanım: php artisan db:seed --class=CleanDemoProductsSeeder
     */
    public function run(): void
    {
        // Kuaför/güzellik ile alakası olmayan demo ürün anahtar kelimeleri
        $demoKeywords = [
            'xbox', 'playstation', 'play station', 'macbook', 'airpods', 'iphone',
            'samsung galaxy', 'jbl', 'sony', 'realme', 'xiaomi', 'xioami', 'gopro',
            'gopor', 'fantech', 'headset', 'headphone', 'earbuds', 'speaker',
            'sound box', 'joystick', 'gamepad', 'controller', 'laptop', 'rice cooker',
            'ceiling fan', 'smartwatch', 'kospet', 'bluetooth speaker', 'hp playstation',
            'wireless game', 'music box', 'camera', 'apple', 'asus', 'zenbook',
            'rolex', 'watch', 'symphony', 'phone', 'pineapple', 'desktop',
            'mini sound', 'mabppappaa', 'vision',
        ];

        // Siparişi olan ürün ID'lerini bul (bunlara dokunma)
        $protectedIds = OrderProduct::pluck('product_id')->unique()->toArray();

        $query = Product::query();

        // Demo keyword'lerle eşleşenleri bul
        $query->where(function ($q) use ($demoKeywords) {
            foreach ($demoKeywords as $keyword) {
                $q->orWhere('name', 'LIKE', '%' . $keyword . '%');
            }
        });

        // Siparişi olanları koru
        if (!empty($protectedIds)) {
            $query->whereNotIn('id', $protectedIds);
        }

        $demoProducts = $query->get();

        if ($demoProducts->isEmpty()) {
            $this->command->info('Temizlenecek demo ürün bulunamadı.');
            return;
        }

        $this->command->info("Temizlenecek demo ürün sayısı: {$demoProducts->count()}");

        foreach ($demoProducts as $product) {
            $this->command->line("  Siliniyor: [{$product->id}] {$product->name}");
        }

        $ids = $demoProducts->pluck('id')->toArray();

        DB::transaction(function () use ($ids) {
            // İlişkili kayıtları temizle
            DB::table('product_galleries')->whereIn('product_id', $ids)->delete();
            DB::table('product_specifications')->whereIn('product_id', $ids)->delete();
            DB::table('product_reviews')->whereIn('product_id', $ids)->delete();
            DB::table('product_reports')->whereIn('product_id', $ids)->delete();
            DB::table('wishlists')->whereIn('product_id', $ids)->delete();
            DB::table('compare_products')->whereIn('product_id', $ids)->delete();
            DB::table('flash_sale_products')->whereIn('product_id', $ids)->delete();

            // Varyantları temizle
            $variantIds = DB::table('product_variants')->whereIn('product_id', $ids)->pluck('id');
            if ($variantIds->isNotEmpty()) {
                DB::table('product_variant_items')->whereIn('product_variant_id', $variantIds)->delete();
                DB::table('product_variants')->whereIn('product_id', $ids)->delete();
            }

            // Ürünleri sil
            Product::whereIn('id', $ids)->delete();
        });

        $this->command->info("✓ {$demoProducts->count()} demo ürün başarıyla silindi.");

        // Demo kategorileri temizle
        $demoCategories = DB::table('categories')->where('name', 'John Doe')->count();
        if ($demoCategories > 0) {
            DB::table('categories')->where('name', 'John Doe')->delete();
            $this->command->info("✓ 'John Doe' demo kategorisi silindi.");
        }
    }
}
