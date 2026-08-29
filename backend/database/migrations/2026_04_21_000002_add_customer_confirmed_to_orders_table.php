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
            if (!Schema::hasColumn('orders', 'customer_confirmed_at')) {
                $table->timestamp('customer_confirmed_at')->nullable()->after('order_delivered_date');
            }
            if (!Schema::hasColumn('orders', 'payout_processed_at')) {
                $table->timestamp('payout_processed_at')->nullable()->after('customer_confirmed_at');
            }
            if (!Schema::hasColumn('orders', 'payout_status')) {
                $table->enum('payout_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('payout_processed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_confirmed_at', 'payout_processed_at', 'payout_status']);
        });
    }
};