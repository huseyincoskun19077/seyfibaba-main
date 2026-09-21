<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'netsantral_pbxnum')) {
                $table->string('netsantral_pbxnum', 32)->nullable()->after('netsantral_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'netsantral_pbxnum')) {
                $table->dropColumn('netsantral_pbxnum');
            }
        });
    }
};
