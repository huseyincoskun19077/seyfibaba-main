<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('seller_withdraws', 'approved_date')) {
            Schema::table('seller_withdraws', function (Blueprint $table) {
                $table->date('approved_date')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('seller_withdraws', 'approved_date')) {
            Schema::table('seller_withdraws', function (Blueprint $table) {
                $table->dropColumn('approved_date');
            });
        }
    }
};
