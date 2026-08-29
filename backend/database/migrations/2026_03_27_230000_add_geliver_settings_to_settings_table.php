<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('geliver_api_token', 500)->nullable()->after('claude_enabled');
            $table->string('geliver_test_mode', 20)->nullable()->after('geliver_api_token');
            $table->string('geliver_sender_address_id', 100)->nullable()->after('geliver_test_mode');
            $table->string('geliver_webhook_header_name', 100)->nullable()->after('geliver_sender_address_id');
            $table->string('geliver_webhook_header_secret', 255)->nullable()->after('geliver_webhook_header_name');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'geliver_api_token',
                'geliver_test_mode',
                'geliver_sender_address_id',
                'geliver_webhook_header_name',
                'geliver_webhook_header_secret',
            ]);
        });
    }
};
