<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Yanlışlıkla approve_by_admin=0 yazılmış satıcı ürünlerini aç.
 * (Taslak/sync bayrağı admin kilidi sanılıyordu.)
 * Gerçek admin kilitleri de açılır; gerekirse admin yeniden pasife alabilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'approve_by_admin')) {
            return;
        }

        DB::table('products')
            ->where('vendor_id', '>', 0)
            ->where('approve_by_admin', 0)
            ->update(['approve_by_admin' => 1]);
    }

    public function down(): void
    {
        // Irreversible data repair
    }
};
