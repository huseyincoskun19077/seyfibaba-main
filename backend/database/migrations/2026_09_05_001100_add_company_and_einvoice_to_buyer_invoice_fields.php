<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name', 191)->nullable()->after('tax_office');
            }
            if (! Schema::hasColumn('users', 'is_e_invoice')) {
                $table->boolean('is_e_invoice')->default(false)->after('company_name');
            }
        });

        Schema::table('order_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('order_addresses', 'company_name')) {
                $table->string('company_name', 191)->nullable()->after('tax_office');
            }
            if (! Schema::hasColumn('order_addresses', 'is_e_invoice')) {
                $table->boolean('is_e_invoice')->default(false)->after('company_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['company_name', 'is_e_invoice'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('order_addresses', function (Blueprint $table) {
            foreach (['company_name', 'is_e_invoice'] as $column) {
                if (Schema::hasColumn('order_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
