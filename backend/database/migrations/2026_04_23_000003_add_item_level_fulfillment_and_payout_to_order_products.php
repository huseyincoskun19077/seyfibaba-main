<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            // Fulfillment timeline (satır bazlı)
            if (!Schema::hasColumn('order_products', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('seller_status');
            }
            if (!Schema::hasColumn('order_products', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('order_products', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            }
            if (!Schema::hasColumn('order_products', 'customer_confirmed_at')) {
                $table->timestamp('customer_confirmed_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('order_products', 'auto_confirmed_at')) {
                $table->timestamp('auto_confirmed_at')->nullable()->after('customer_confirmed_at');
            }

            // Payout controls (satır bazlı) — order-level kill-switch ile birlikte çalışır
            if (!Schema::hasColumn('order_products', 'payout_eligible_at')) {
                $table->timestamp('payout_eligible_at')->nullable()->after('auto_confirmed_at');
            }
            if (!Schema::hasColumn('order_products', 'payout_status')) {
                $table->enum('payout_status', ['pending', 'processing', 'paid', 'blocked', 'failed'])
                    ->default('pending')
                    ->after('payout_eligible_at');
            }
            if (!Schema::hasColumn('order_products', 'payout_processed_at')) {
                $table->timestamp('payout_processed_at')->nullable()->after('payout_status');
            }
            if (!Schema::hasColumn('order_products', 'payout_hold_until')) {
                $table->timestamp('payout_hold_until')->nullable()->after('payout_processed_at');
            }
            if (!Schema::hasColumn('order_products', 'payout_block_reason')) {
                $table->text('payout_block_reason')->nullable()->after('payout_hold_until');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $drops = [];
            foreach ([
                'approved_at',
                'shipped_at',
                'delivered_at',
                'customer_confirmed_at',
                'auto_confirmed_at',
                'payout_eligible_at',
                'payout_status',
                'payout_processed_at',
                'payout_hold_until',
                'payout_block_reason',
            ] as $col) {
                if (Schema::hasColumn('order_products', $col)) $drops[] = $col;
            }
            if ($drops) {
                $table->dropColumn($drops);
            }
        });
    }
};

