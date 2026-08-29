<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('salon_crm_customers')) {
            return;
        }

        if (!Schema::hasColumn('salon_crm_customers', 'staff_id')) {
            Schema::table('salon_crm_customers', function (Blueprint $table) {
                $table->unsignedBigInteger('staff_id')->default(0)->after('salon_id');
                $table->index(['salon_id', 'staff_id']);
            });
        }

        // Mevcut kayıtlar patron defteri (0)
        DB::table('salon_crm_customers')->whereNull('staff_id')->update(['staff_id' => 0]);

        $this->dropIndexIfExists('salon_crm_customers', 'salon_crm_customers_salon_id_phone_unique');
        $this->dropIndexIfExists('salon_crm_customers', 'salon_crm_customers_salon_staff_phone_unique');

        Schema::table('salon_crm_customers', function (Blueprint $table) {
            $table->unique(
                ['salon_id', 'staff_id', 'phone'],
                'salon_crm_customers_salon_staff_phone_unique'
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('salon_crm_customers')) {
            return;
        }

        $this->dropIndexIfExists('salon_crm_customers', 'salon_crm_customers_salon_staff_phone_unique');

        if (Schema::hasColumn('salon_crm_customers', 'staff_id')) {
            Schema::table('salon_crm_customers', function (Blueprint $table) {
                $table->dropIndex(['salon_id', 'staff_id']);
                $table->dropColumn('staff_id');
            });
        }

        try {
            Schema::table('salon_crm_customers', function (Blueprint $table) {
                $table->unique(['salon_id', 'phone']);
            });
        } catch (\Throwable $e) {
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            $dbName = DB::getDatabaseName();
            $exists = DB::selectOne(
                'SELECT COUNT(*) AS c FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$dbName, $table, $index]
            );
            if ((int) ($exists->c ?? 0) > 0) {
                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->dropUnique($index);
                });
            }
        } catch (\Throwable $e) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            } catch (\Throwable $e2) {
            }
        }
    }
};
