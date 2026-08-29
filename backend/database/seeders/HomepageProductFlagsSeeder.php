<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomepageProductFlagsSeeder extends Seeder
{
    /**
     * Tüm aktif ürünlerde ana sayfa flag'lerini açar
     * ve featured_categories'i ürün bulunan kategorilere atar.
     */
    public function run(): void
    {
        // Tüm aktif ürünlerde homepage flag'lerini aç
        $updated = DB::table('products')
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->update([
                'is_top' => 1,
                'is_best' => 1,
                'new_product' => 1,
                'is_featured' => 1,
            ]);

        $this->command->info("$updated ürünün ana sayfa flag'leri açıldı.");

        // Featured categories: ürün bulunan kategorileri ata
        $categoryIdsWithProducts = DB::table('products')
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->distinct()
            ->pluck('category_id');

        DB::table('featured_categories')->truncate();

        foreach ($categoryIdsWithProducts as $catId) {
            DB::table('featured_categories')->insert([
                'category_id' => $catId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info(count($categoryIdsWithProducts) . " kategori featured olarak atandı.");
    }
}
