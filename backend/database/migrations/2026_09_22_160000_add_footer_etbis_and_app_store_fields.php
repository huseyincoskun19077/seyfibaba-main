<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('footers')) {
            return;
        }

        Schema::table('footers', function (Blueprint $table) {
            if (! Schema::hasColumn('footers', 'etbis_image')) {
                $table->string('etbis_image')->nullable();
            }
            if (! Schema::hasColumn('footers', 'etbis_url')) {
                $table->string('etbis_url', 500)->nullable();
            }
            if (! Schema::hasColumn('footers', 'app_store_image')) {
                $table->string('app_store_image')->nullable();
            }
            if (! Schema::hasColumn('footers', 'app_store_url')) {
                $table->string('app_store_url', 500)->nullable();
            }
            if (! Schema::hasColumn('footers', 'play_store_image')) {
                $table->string('play_store_image')->nullable();
            }
            if (! Schema::hasColumn('footers', 'play_store_url')) {
                $table->string('play_store_url', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('footers')) {
            return;
        }

        Schema::table('footers', function (Blueprint $table) {
            foreach ([
                'etbis_image', 'etbis_url',
                'app_store_image', 'app_store_url',
                'play_store_image', 'play_store_url',
            ] as $col) {
                if (Schema::hasColumn('footers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
