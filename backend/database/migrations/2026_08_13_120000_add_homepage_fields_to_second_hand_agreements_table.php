<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('second_hand_agreements')) {
            return;
        }

        Schema::table('second_hand_agreements', function (Blueprint $table) {
            if (!Schema::hasColumn('second_hand_agreements', 'homepage_title')) {
                $table->string('homepage_title')->nullable()->after('privacy_content');
            }
            if (!Schema::hasColumn('second_hand_agreements', 'homepage_subtitle')) {
                $table->text('homepage_subtitle')->nullable()->after('homepage_title');
            }
            if (!Schema::hasColumn('second_hand_agreements', 'homepage_cta_primary')) {
                $table->string('homepage_cta_primary', 80)->nullable()->after('homepage_subtitle');
            }
            if (!Schema::hasColumn('second_hand_agreements', 'homepage_cta_secondary')) {
                $table->string('homepage_cta_secondary', 80)->nullable()->after('homepage_cta_primary');
            }
            if (!Schema::hasColumn('second_hand_agreements', 'homepage_image')) {
                $table->string('homepage_image')->nullable()->after('homepage_cta_secondary');
            }
            if (!Schema::hasColumn('second_hand_agreements', 'homepage_show_categories')) {
                $table->boolean('homepage_show_categories')->default(true)->after('homepage_image');
            }
            if (!Schema::hasColumn('second_hand_agreements', 'homepage_show_featured')) {
                $table->boolean('homepage_show_featured')->default(true)->after('homepage_show_categories');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('second_hand_agreements')) {
            return;
        }

        Schema::table('second_hand_agreements', function (Blueprint $table) {
            foreach ([
                'homepage_title',
                'homepage_subtitle',
                'homepage_cta_primary',
                'homepage_cta_secondary',
                'homepage_image',
                'homepage_show_categories',
                'homepage_show_featured',
            ] as $col) {
                if (Schema::hasColumn('second_hand_agreements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
