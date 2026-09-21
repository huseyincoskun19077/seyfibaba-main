<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'netsantral_usercode')) {
                $table->string('netsantral_usercode')->nullable()->after('netgsm_enabled');
            }
            if (! Schema::hasColumn('settings', 'netsantral_password')) {
                $table->string('netsantral_password')->nullable()->after('netsantral_usercode');
            }
            if (! Schema::hasColumn('settings', 'netsantral_enabled')) {
                $table->boolean('netsantral_enabled')->default(false)->after('netsantral_password');
            }
            if (! Schema::hasColumn('settings', 'netsipp_api_key')) {
                $table->text('netsipp_api_key')->nullable()->after('netsantral_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $cols = ['netsantral_usercode', 'netsantral_password', 'netsantral_enabled', 'netsipp_api_key'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
