<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('addresses', 'zip_code')) {
                $table->string('zip_code', 10)->nullable()->after('address');
            }
            if (! Schema::hasColumn('addresses', 'invoice_type')) {
                $table->string('invoice_type', 20)->nullable()->after('type');
            }
            if (! Schema::hasColumn('addresses', 'tc_identity')) {
                $table->string('tc_identity', 11)->nullable()->after('invoice_type');
            }
            if (! Schema::hasColumn('addresses', 'tax_number')) {
                $table->string('tax_number', 20)->nullable()->after('tc_identity');
            }
            if (! Schema::hasColumn('addresses', 'tax_office')) {
                $table->string('tax_office', 120)->nullable()->after('tax_number');
            }
            if (! Schema::hasColumn('addresses', 'company_name')) {
                $table->string('company_name', 191)->nullable()->after('tax_office');
            }
            if (! Schema::hasColumn('addresses', 'is_e_invoice')) {
                $table->boolean('is_e_invoice')->default(false)->after('company_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            foreach ([
                'zip_code',
                'invoice_type',
                'tc_identity',
                'tax_number',
                'tax_office',
                'company_name',
                'is_e_invoice',
            ] as $column) {
                if (Schema::hasColumn('addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
