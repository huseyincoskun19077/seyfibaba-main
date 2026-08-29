<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'mobile_hub_bg_top')) {
                $table->string('mobile_hub_bg_top', 20)->nullable()->after('theme_two');
            }
            if (!Schema::hasColumn('settings', 'mobile_hub_bg_bottom')) {
                $table->string('mobile_hub_bg_bottom', 20)->nullable()->after('mobile_hub_bg_top');
            }
            if (!Schema::hasColumn('settings', 'mobile_hub_feature_start')) {
                $table->string('mobile_hub_feature_start', 20)->nullable()->after('mobile_hub_bg_bottom');
            }
            if (!Schema::hasColumn('settings', 'mobile_hub_feature_end')) {
                $table->string('mobile_hub_feature_end', 20)->nullable()->after('mobile_hub_feature_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach ([
                'mobile_hub_bg_top',
                'mobile_hub_bg_bottom',
                'mobile_hub_feature_start',
                'mobile_hub_feature_end',
            ] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
