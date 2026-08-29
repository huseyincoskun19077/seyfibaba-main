<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vendors', 'seller_type')) {
            return;
        }

        // Bireysel kaldırıldı → mevcut kayıtları şahıs şirketine taşı
        DB::table('vendors')
            ->whereIn('seller_type', ['individual', 'personal'])
            ->update(['seller_type' => 'sole_proprietorship']);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE vendors MODIFY seller_type VARCHAR(20) NOT NULL DEFAULT 'limited_company'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vendors', 'seller_type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE vendors MODIFY seller_type VARCHAR(20) NOT NULL DEFAULT 'individual'");
        }
    }
};
