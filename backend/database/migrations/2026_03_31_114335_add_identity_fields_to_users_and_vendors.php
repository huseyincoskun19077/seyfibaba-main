<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alıcı: TC Kimlik veya Vergi No
        Schema::table('users', function (Blueprint $table) {
            $table->string('tc_identity', 11)->nullable()->after('phone');
            $table->string('tax_number', 20)->nullable()->after('tc_identity');
        });

        // Satıcı: TC Kimlik (iban, tax_number, tax_office zaten var)
        if (!Schema::hasColumn('vendors', 'tc_identity')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('tc_identity', 11)->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tc_identity', 'tax_number']);
        });
        if (Schema::hasColumn('vendors', 'tc_identity')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('tc_identity');
            });
        }
    }
};
