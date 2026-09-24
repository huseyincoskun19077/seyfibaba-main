<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stories')) {
            return;
        }
        Schema::table('stories', function (Blueprint $table) {
            if (! Schema::hasColumn('stories', 'show_on_web')) {
                $table->boolean('show_on_web')->default(true)->after('status');
            }
            if (! Schema::hasColumn('stories', 'show_on_mobile')) {
                $table->boolean('show_on_mobile')->default(true)->after('show_on_web');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stories')) {
            return;
        }
        Schema::table('stories', function (Blueprint $table) {
            if (Schema::hasColumn('stories', 'show_on_mobile')) {
                $table->dropColumn('show_on_mobile');
            }
            if (Schema::hasColumn('stories', 'show_on_web')) {
                $table->dropColumn('show_on_web');
            }
        });
    }
};
