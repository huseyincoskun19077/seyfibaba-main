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
            if (!Schema::hasColumn('salon_crm_salons', 'admin_free_until')) {
                $table->timestamp('admin_free_until')->nullable()->after('threshold_amount');
            }
            if (!Schema::hasColumn('salon_crm_salons', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('admin_free_until');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('salon_crm_salons')) {
            return;
        }

        Schema::table('salon_crm_salons', function (Blueprint $table) {
            foreach (['admin_free_until', 'admin_notes'] as $col) {
                if (Schema::hasColumn('salon_crm_salons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
