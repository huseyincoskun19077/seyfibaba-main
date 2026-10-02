<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'seller_shipping_breakdown')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->json('seller_shipping_breakdown')->nullable()->after('shipping_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'seller_shipping_breakdown')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('seller_shipping_breakdown');
            });
        }
    }
};
