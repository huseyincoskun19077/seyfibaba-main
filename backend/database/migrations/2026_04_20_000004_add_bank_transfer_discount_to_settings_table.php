<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'bank_transfer_discount_percent')) {
                $table->decimal('bank_transfer_discount_percent', 5, 2)->default(3)->after('default_commission_rate');
            }
            if (!Schema::hasColumn('settings', 'bank_transfer_info')) {
                $table->text('bank_transfer_info')->nullable()->after('bank_transfer_discount_percent');
            }
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('settings', 'bank_transfer_discount_percent')) $drops[] = 'bank_transfer_discount_percent';
            if (Schema::hasColumn('settings', 'bank_transfer_info')) $drops[] = 'bank_transfer_info';
            if ($drops) {
                $table->dropColumn($drops);
            }
        });
    }
};