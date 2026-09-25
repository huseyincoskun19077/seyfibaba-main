<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcement_modals', function (Blueprint $table) {
            if (!Schema::hasColumn('announcement_modals', 'status')) {
                $table->tinyInteger('status')->default(0)->after('image');
            }
            if (!Schema::hasColumn('announcement_modals', 'expired_date')) {
                $table->integer('expired_date')->default(7)->after('status');
            }
            if (!Schema::hasColumn('announcement_modals', 'link')) {
                $table->string('link')->nullable()->after('expired_date');
            }
            if (!Schema::hasColumn('announcement_modals', 'mobile_link')) {
                $table->string('mobile_link')->nullable()->after('link');
            }
            if (!Schema::hasColumn('announcement_modals', 'cta_text')) {
                $table->string('cta_text')->nullable()->after('mobile_link');
            }
            if (!Schema::hasColumn('announcement_modals', 'show_on_web')) {
                $table->tinyInteger('show_on_web')->default(1)->after('cta_text');
            }
            if (!Schema::hasColumn('announcement_modals', 'show_on_mobile')) {
                $table->tinyInteger('show_on_mobile')->default(1)->after('show_on_web');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcement_modals', function (Blueprint $table) {
            foreach (['show_on_mobile', 'show_on_web', 'cta_text', 'mobile_link', 'link'] as $col) {
                if (Schema::hasColumn('announcement_modals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
