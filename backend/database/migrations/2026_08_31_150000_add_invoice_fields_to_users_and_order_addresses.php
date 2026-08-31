<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'invoice_type')) {
                $table->string('invoice_type', 20)->default('individual')->after('tax_number');
            }
            if (! Schema::hasColumn('users', 'tax_office')) {
                $table->string('tax_office', 120)->nullable()->after('invoice_type');
            }
        });

        Schema::table('order_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('order_addresses', 'invoice_type')) {
                $table->string('invoice_type', 20)->nullable()->after('billing_address_type');
            }
            if (! Schema::hasColumn('order_addresses', 'tc_identity')) {
                $table->string('tc_identity', 11)->nullable()->after('invoice_type');
            }
            if (! Schema::hasColumn('order_addresses', 'tax_number')) {
                $table->string('tax_number', 20)->nullable()->after('tc_identity');
            }
            if (! Schema::hasColumn('order_addresses', 'tax_office')) {
                $table->string('tax_office', 120)->nullable()->after('tax_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            foreach (['invoice_type', 'tc_identity', 'tax_number', 'tax_office'] as $column) {
                if (Schema::hasColumn('order_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['invoice_type', 'tax_office'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
