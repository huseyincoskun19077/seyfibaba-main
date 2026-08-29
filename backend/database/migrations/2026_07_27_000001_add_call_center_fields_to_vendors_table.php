<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'registration_source')) {
                $table->string('registration_source', 32)->default('self')->after('status');
            }

            if (! Schema::hasColumn('vendors', 'registered_by_admin_id')) {
                $table->unsignedBigInteger('registered_by_admin_id')->nullable()->after('registration_source');
            }

            if (! Schema::hasColumn('vendors', 'quick_registration_note')) {
                $table->text('quick_registration_note')->nullable()->after('registered_by_admin_id');
            }

            if (! Schema::hasColumn('vendors', 'primary_category_id')) {
                $table->unsignedBigInteger('primary_category_id')->nullable()->after('quick_registration_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            foreach (['primary_category_id', 'quick_registration_note', 'registered_by_admin_id', 'registration_source'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
