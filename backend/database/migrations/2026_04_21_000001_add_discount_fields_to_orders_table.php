<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 20, 2)->nullable()->after('coupon_discount');
            }
            if (!Schema::hasColumn('orders', 'discount_type')) {
                $table->string('discount_type', 50)->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'total_amount')) {
                $table->decimal('total_amount', 20, 2)->nullable()->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_type', 'total_amount']);
        });
    }
};