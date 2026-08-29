<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Türkiye geneli kargo kuralları seed migration.
     * city_id=0: tüm şehirler için geçerli varsayılan kurallar.
     *
     * Kural tipleri:
     *   base_on_price  → sipariş tutarına göre
     *   base_on_weight → toplam ağırlığa göre (gram)
     *   base_on_qty    → ürün adedine göre
     *
     * condition_to = -1 → üst limit yok (sınırsız)
     */
    public function up(): void
    {
        // Mevcut kuralları temizle
        DB::table('shippings')->truncate();

        $rules = [
            // Fiyata göre kargo kuralları
            [
                'city_id'        => 0,
                'shipping_rule'  => 'Standart Kargo',
                'type'           => 'base_on_price',
                'shipping_fee'   => 49.90,
                'condition_from' => '0',
                'condition_to'   => '499',
            ],
            [
                'city_id'        => 0,
                'shipping_rule'  => '500₺ Üzeri Ücretsiz Kargo',
                'type'           => 'base_on_price',
                'shipping_fee'   => 0,
                'condition_from' => '500',
                'condition_to'   => '-1',
            ],
        ];

        $now = now();

        foreach ($rules as $rule) {
            $rule['created_at'] = $now;
            $rule['updated_at'] = $now;
            DB::table('shippings')->insert($rule);
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::table('shippings')->whereIn('shipping_rule', [
            'Standart Kargo',
            '500₺ Üzeri Ücretsiz Kargo',
        ])->delete();
    }
};
