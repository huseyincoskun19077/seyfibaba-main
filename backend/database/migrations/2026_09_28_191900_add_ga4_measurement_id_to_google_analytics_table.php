<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('google_analytics')) {
            return;
        }

        if (! Schema::hasColumn('google_analytics', 'ga4_measurement_id')) {
            Schema::table('google_analytics', function (Blueprint $table) {
                $table->string('ga4_measurement_id', 32)->nullable()->after('analytic_id');
            });
        }

        DB::table('google_analytics')
            ->where(function ($query) {
                $query->whereNull('ga4_measurement_id')
                    ->orWhere('ga4_measurement_id', '');
            })
            ->update(['ga4_measurement_id' => 'G-2ZL87131XC']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('google_analytics')) {
            return;
        }

        if (Schema::hasColumn('google_analytics', 'ga4_measurement_id')) {
            Schema::table('google_analytics', function (Blueprint $table) {
                $table->dropColumn('ga4_measurement_id');
            });
        }
    }
};
