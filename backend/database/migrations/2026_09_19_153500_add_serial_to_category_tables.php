<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'sub_categories', 'child_categories'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (! Schema::hasColumn($table, 'serial')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedInteger('serial')->default(0)->after('id');
                });
            }
        }

        foreach (['categories', 'sub_categories', 'child_categories'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'serial')) {
                continue;
            }
            // Mevcut sırayı id üzerinden koru
            DB::statement("UPDATE {$table} SET serial = id WHERE serial = 0 OR serial IS NULL");
        }
    }

    public function down(): void
    {
        foreach (['categories', 'sub_categories', 'child_categories'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'serial')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('serial');
                });
            }
        }
    }
};
