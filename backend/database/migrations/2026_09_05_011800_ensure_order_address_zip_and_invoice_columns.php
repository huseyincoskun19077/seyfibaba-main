<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('order_addresses', 'billing_zip_code')) {
                $table->string('billing_zip_code', 20)->nullable()->after('billing_address_type');
            }
            if (! Schema::hasColumn('order_addresses', 'shipping_zip_code')) {
                $table->string('shipping_zip_code', 20)->nullable()->after('shipping_address_type');
            }
            if (! Schema::hasColumn('order_addresses', 'company_name')) {
                $table->string('company_name', 191)->nullable();
            }
            if (! Schema::hasColumn('order_addresses', 'is_e_invoice')) {
                $table->boolean('is_e_invoice')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            foreach (['billing_zip_code', 'shipping_zip_code', 'company_name', 'is_e_invoice'] as $column) {
                if (Schema::hasColumn('order_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
