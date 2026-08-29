<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'auto_complete_days')) {
                $table->unsignedSmallInteger('auto_complete_days')->default(15)->after('return_window_days');
            }
            if (! Schema::hasColumn('settings', 'payout_hold_days')) {
                $table->unsignedSmallInteger('payout_hold_days')->default(3)->after('auto_complete_days');
            }
            if (! Schema::hasColumn('settings', 'iyzico_payout_dry_run')) {
                $table->boolean('iyzico_payout_dry_run')->default(false)->after('payout_hold_days');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payout_eligible_at')) {
                $table->timestamp('payout_eligible_at')->nullable()->after('auto_complete_date');
            }
        });

        Schema::table('order_products', function (Blueprint $table) {
            if (! Schema::hasColumn('order_products', 'iyzico_payment_transaction_id')) {
                $table->string('iyzico_payment_transaction_id', 64)->nullable()->after('payout_block_reason');
            }
            if (! Schema::hasColumn('order_products', 'iyzico_approved_at')) {
                $table->timestamp('iyzico_approved_at')->nullable()->after('iyzico_payment_transaction_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $drops = [];
            foreach (['iyzico_payment_transaction_id', 'iyzico_approved_at'] as $col) {
                if (Schema::hasColumn('order_products', $col)) {
                    $drops[] = $col;
                }
            }
            if ($drops) {
                $table->dropColumn($drops);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payout_eligible_at')) {
                $table->dropColumn('payout_eligible_at');
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            $drops = [];
            foreach (['auto_complete_days', 'payout_hold_days', 'iyzico_payout_dry_run'] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $drops[] = $col;
                }
            }
            if ($drops) {
                $table->dropColumn($drops);
            }
        });
    }
};
