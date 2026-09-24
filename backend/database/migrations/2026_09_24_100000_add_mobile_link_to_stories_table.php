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
        if (! Schema::hasColumn('stories', 'mobile_link')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->string('mobile_link', 500)->nullable()->after('link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stories') && Schema::hasColumn('stories', 'mobile_link')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->dropColumn('mobile_link');
            });
        }
    }
};
