<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('salon_crm_salons')) {
            return;
        }

        Schema::table('salon_crm_salons', function (Blueprint $table) {
            if (!Schema::hasColumn('salon_crm_salons', 'join_code')) {
                $table->string('join_code', 8)->nullable()->unique()->after('owner_username');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('salon_crm_salons')) {
            return;
        }

        Schema::table('salon_crm_salons', function (Blueprint $table) {
            if (Schema::hasColumn('salon_crm_salons', 'join_code')) {
                $table->dropUnique(['join_code']);
                $table->dropColumn('join_code');
            }
        });
    }
};
