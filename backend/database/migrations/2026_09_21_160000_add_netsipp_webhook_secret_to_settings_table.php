<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'netsipp_webhook_secret')) {
                $table->string('netsipp_webhook_secret', 64)->nullable()->after('netsipp_api_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'netsipp_webhook_secret')) {
                $table->dropColumn('netsipp_webhook_secret');
            }
        });
    }
};
