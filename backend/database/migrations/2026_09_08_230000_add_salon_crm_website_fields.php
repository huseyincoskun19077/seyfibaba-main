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
            if (!Schema::hasColumn('salon_crm_salons', 'website_enabled')) {
                $table->boolean('website_enabled')->default(false);
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_show_calendar')) {
                $table->boolean('website_show_calendar')->default(false);
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_show_prices')) {
                $table->boolean('website_show_prices')->default(false);
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_show_staff_appointments')) {
                $table->boolean('website_show_staff_appointments')->default(false);
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_province')) {
                $table->string('website_province', 80)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_district')) {
                $table->string('website_district', 80)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_province_slug')) {
                $table->string('website_province_slug', 100)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_district_slug')) {
                $table->string('website_district_slug', 100)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_name_slug')) {
                $table->string('website_name_slug', 140)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_slug_seq')) {
                $table->unsignedSmallInteger('website_slug_seq')->default(1);
            }
            if (!Schema::hasColumn('salon_crm_salons', 'whatsapp')) {
                $table->string('whatsapp', 32)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'instagram')) {
                $table->string('instagram', 120)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'address')) {
                $table->string('address', 255)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'address_lat')) {
                $table->decimal('address_lat', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'address_lng')) {
                $table->decimal('address_lng', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_seo_title')) {
                $table->string('website_seo_title', 180)->nullable();
            }
            if (!Schema::hasColumn('salon_crm_salons', 'website_seo_description')) {
                $table->string('website_seo_description', 320)->nullable();
            }
        });

        try {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                $table->unique(
                    ['website_province_slug', 'website_district_slug', 'website_name_slug'],
                    'salon_crm_website_path_unique'
                );
            });
        } catch (\Throwable $e) {
            // index may already exist
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('salon_crm_salons')) {
            return;
        }

        try {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                $table->dropUnique('salon_crm_website_path_unique');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('salon_crm_salons', function (Blueprint $table) {
            $cols = [
                'website_enabled',
                'website_show_calendar',
                'website_show_prices',
                'website_show_staff_appointments',
                'website_province',
                'website_district',
                'website_province_slug',
                'website_district_slug',
                'website_name_slug',
                'website_slug_seq',
                'whatsapp',
                'instagram',
                'address',
                'address_lat',
                'address_lng',
                'website_seo_title',
                'website_seo_description',
            ];
            $drop = [];
            foreach ($cols as $col) {
                if (Schema::hasColumn('salon_crm_salons', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
