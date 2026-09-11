<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode', 64)->nullable()->after('sku');
                $table->index('barcode');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'barcode')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropColumn('barcode');
        });
    }
};
