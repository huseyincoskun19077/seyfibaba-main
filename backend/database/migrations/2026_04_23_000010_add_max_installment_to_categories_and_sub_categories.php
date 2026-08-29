<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'max_installment')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_installment')->nullable()->after('slug');
            });
        }

        if (Schema::hasTable('sub_categories') && !Schema::hasColumn('sub_categories', 'max_installment')) {
            Schema::table('sub_categories', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_installment')->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'max_installment')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('max_installment');
            });
        }

        if (Schema::hasTable('sub_categories') && Schema::hasColumn('sub_categories', 'max_installment')) {
            Schema::table('sub_categories', function (Blueprint $table) {
                $table->dropColumn('max_installment');
            });
        }
    }
};

