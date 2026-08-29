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
        Schema::create('commission_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('order_product_id')->constrained('order_products');
            $table->foreignId('seller_id')->constrained('vendors');
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('seller_net_amount', 10, 2);
            $table->enum('status', ['pending', 'settled', 'refunded'])->default('pending');
            $table->timestamp('settled_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status'], 'idx_seller_status');
            $table->index('order_id', 'idx_order');
            $table->index('order_product_id', 'idx_order_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_ledger');
    }
};
