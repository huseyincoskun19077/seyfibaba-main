<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('footer_links')) {
            return;
        }

        Schema::table('footer_links', function (Blueprint $table) {
            if (! Schema::hasColumn('footer_links', 'status')) {
                $table->boolean('status')->default(true)->after('title');
            }
            if (! Schema::hasColumn('footer_links', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
            }
            if (! Schema::hasColumn('footer_links', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('footer_links')) {
            return;
        }

        Schema::table('footer_links', function (Blueprint $table) {
            foreach (['brand_id', 'sort_order', 'status'] as $col) {
                if (Schema::hasColumn('footer_links', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
