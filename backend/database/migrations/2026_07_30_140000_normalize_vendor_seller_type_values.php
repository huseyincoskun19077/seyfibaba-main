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

        DB::table('vendors')
            ->where('seller_type', 'corporate')
            ->update(['seller_type' => 'limited_company']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vendors', 'seller_type')) {
            return;
        }

        DB::table('vendors')
            ->where('seller_type', 'limited_company')
            ->update(['seller_type' => 'corporate']);
    }
};
