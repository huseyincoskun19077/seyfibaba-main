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
            if (!Schema::hasColumn('salon_crm_salons', 'open_hour')) {
                $table->unsignedTinyInteger('open_hour')->default(9)->after('show_profile_to_customers');
            }
            if (!Schema::hasColumn('salon_crm_salons', 'close_hour')) {
                $table->unsignedTinyInteger('close_hour')->default(21)->after('open_hour');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('salon_crm_salons')) {
            return;
        }

        Schema::table('salon_crm_salons', function (Blueprint $table) {
            foreach (['close_hour', 'open_hour'] as $col) {
                if (Schema::hasColumn('salon_crm_salons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
