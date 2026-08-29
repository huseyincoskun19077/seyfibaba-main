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
        Schema::table('order_products', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->change();
            $table->unsignedBigInteger('product_id')->change();
            // In DB seller_id is integer, let's keep it consistent if needed butFK to vendors needs unsignedBigInteger
        });
        
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'cash_on_delivery')) {
                $table->integer('cash_on_delivery')->default(0);
            }
            if (!Schema::hasColumn('orders', 'additional_info')) {
                $table->text('additional_info')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not easily reversible for type changes in a safe way without doctrine/dbal
    }
};
