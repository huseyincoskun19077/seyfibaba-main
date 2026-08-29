<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salon_crm_salons')) {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                if (!Schema::hasColumn('salon_crm_salons', 'logo_image')) {
                    $table->string('logo_image', 255)->nullable()->after('phone');
                }
                if (!Schema::hasColumn('salon_crm_salons', 'cover_image')) {
                    $table->string('cover_image', 255)->nullable()->after('logo_image');
                }
                if (!Schema::hasColumn('salon_crm_salons', 'profile_text')) {
                    $table->text('profile_text')->nullable()->after('cover_image');
                }
                if (!Schema::hasColumn('salon_crm_salons', 'show_profile_to_customers')) {
                    $table->boolean('show_profile_to_customers')->default(false)->after('profile_text');
                }
            });
        }

        if (Schema::hasTable('salon_crm_staff')) {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                if (!Schema::hasColumn('salon_crm_staff', 'photo')) {
                    $table->string('photo', 255)->nullable()->after('name');
                }
                if (!Schema::hasColumn('salon_crm_staff', 'show_photo_to_customers')) {
                    $table->boolean('show_photo_to_customers')->default(true)->after('photo');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('salon_crm_salons')) {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                foreach (['logo_image', 'cover_image', 'profile_text', 'show_profile_to_customers'] as $col) {
                    if (Schema::hasColumn('salon_crm_salons', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('salon_crm_staff')) {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                foreach (['photo', 'show_photo_to_customers'] as $col) {
                    if (Schema::hasColumn('salon_crm_staff', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
