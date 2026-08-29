<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payout_blocked_at')) {
                $table->timestamp('payout_blocked_at')->nullable()->after('payout_status');
            }
            if (!Schema::hasColumn('orders', 'payout_block_reason')) {
                $table->text('payout_block_reason')->nullable()->after('payout_blocked_at');
            }
            if (!Schema::hasColumn('orders', 'payout_hold_until')) {
                $table->timestamp('payout_hold_until')->nullable()->after('payout_block_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('orders', 'payout_blocked_at')) $drops[] = 'payout_blocked_at';
            if (Schema::hasColumn('orders', 'payout_block_reason')) $drops[] = 'payout_block_reason';
            if (Schema::hasColumn('orders', 'payout_hold_until')) $drops[] = 'payout_hold_until';
            if ($drops) {
                $table->dropColumn($drops);
            }
        });
    }
};

