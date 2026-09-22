<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('child_categories') && ! Schema::hasColumn('child_categories', 'max_installment')) {
            Schema::table('child_categories', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_installment')->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('child_categories') && Schema::hasColumn('child_categories', 'max_installment')) {
            Schema::table('child_categories', function (Blueprint $table) {
                $table->dropColumn('max_installment');
            });
        }
    }
};
