<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('iyzico_sub_merchant_key', 100)->nullable()->after('tax_number');
            $table->string('iyzico_sub_merchant_type', 30)->nullable()->after('iyzico_sub_merchant_key');
            $table->string('tax_office', 100)->nullable()->after('tax_number');
            $table->string('legal_company_title', 255)->nullable()->after('tax_office');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'iyzico_sub_merchant_key',
                'iyzico_sub_merchant_type',
                'tax_office',
                'legal_company_title',
            ]);
        });
    }
};
