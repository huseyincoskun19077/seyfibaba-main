<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'mobile_hub_shop_image')) {
                $table->string('mobile_hub_shop_image')->nullable()->after('mobile_hub_feature_end');
            }
            if (!Schema::hasColumn('settings', 'mobile_hub_crm_image')) {
                $table->string('mobile_hub_crm_image')->nullable()->after('mobile_hub_shop_image');
            }
            if (!Schema::hasColumn('settings', 'mobile_hub_secondhand_image')) {
                $table->string('mobile_hub_secondhand_image')->nullable()->after('mobile_hub_crm_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach ([
                'mobile_hub_shop_image',
                'mobile_hub_crm_image',
                'mobile_hub_secondhand_image',
            ] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
