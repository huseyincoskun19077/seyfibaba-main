<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Iyzico ödeme altyapısı varsayılan ayarları.
     * Test modu aktif olarak başlatılır.
     * Canlı API anahtarları admin panelden girilmelidir.
     */
    public function up(): void
    {
        $exists = DB::table('iyzico_payments')->first();

        if (!$exists) {
            DB::table('iyzico_payments')->insert([
                'status'                 => 1,
                'api_key'                => '',
                'secret_key'             => '',
                'is_test_mode'           => 1,
                'marketplace_mode'       => 1,
                'sub_merchant_key'       => '',
                'store_sub_merchant_keys'=> 0,
                'currency_id'            => null,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('iyzico_payments')->truncate();
    }
};
