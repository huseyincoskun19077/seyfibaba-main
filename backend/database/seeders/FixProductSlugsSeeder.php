<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixProductSlugsSeeder extends Seeder
{
    /**
     * Türkçe karakter dönüşümü ile tüm ürün slug'larını düzeltir.
     */
    public function run(): void
    {
        $trMap = [
            'ç' => 'c', 'Ç' => 'c',
            'ğ' => 'g', 'Ğ' => 'g',
            'ı' => 'i', 'I' => 'i',
            'İ' => 'i',
            'ö' => 'o', 'Ö' => 'o',
            'ş' => 's', 'Ş' => 's',
            'ü' => 'u', 'Ü' => 'u',
            'â' => 'a', 'Â' => 'a',
            'î' => 'i', 'Î' => 'i',
            'û' => 'u', 'Û' => 'u',
        ];

        $products = DB::table('products')->select('id', 'name', 'slug')->get();
        $fixed = 0;

        foreach ($products as $product) {
            $name = mb_strtolower($product->name, 'UTF-8');
            $name = strtr($name, $trMap);
            $newSlug = preg_replace('/[^a-z0-9 -]/', '', $name);
            $newSlug = preg_replace('/\s+/', '-', $newSlug);
            $newSlug = preg_replace('/-+/', '-', $newSlug);
            $newSlug = trim($newSlug, '-');

            if ($newSlug !== $product->slug) {
                DB::table('products')->where('id', $product->id)->update(['slug' => $newSlug]);
                $this->command->info("  #{$product->id}: {$product->slug} → {$newSlug}");
                $fixed++;
            }
        }

        $this->command->info("$fixed ürün slug'ı düzeltildi.");
    }
}
