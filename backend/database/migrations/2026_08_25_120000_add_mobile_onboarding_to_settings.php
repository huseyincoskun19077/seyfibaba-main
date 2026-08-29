<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'mobile_onboarding_bg')) {
                $table->string('mobile_onboarding_bg', 20)->nullable();
            }
            if (!Schema::hasColumn('settings', 'mobile_onboarding_image_1')) {
                $table->string('mobile_onboarding_image_1')->nullable();
            }
            if (!Schema::hasColumn('settings', 'mobile_onboarding_image_2')) {
                $table->string('mobile_onboarding_image_2')->nullable();
            }
            if (!Schema::hasColumn('settings', 'mobile_onboarding_image_3')) {
                $table->string('mobile_onboarding_image_3')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach ([
                'mobile_onboarding_bg',
                'mobile_onboarding_image_1',
                'mobile_onboarding_image_2',
                'mobile_onboarding_image_3',
            ] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
