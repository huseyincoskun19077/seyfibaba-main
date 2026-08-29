<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('netgsm_usercode')->nullable()->after('geliver_webhook_header_secret');
            $table->string('netgsm_password')->nullable()->after('netgsm_usercode');
            $table->string('netgsm_msgheader')->nullable()->after('netgsm_password');
            $table->boolean('netgsm_enabled')->default(false)->after('netgsm_msgheader');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['netgsm_usercode', 'netgsm_password', 'netgsm_msgheader', 'netgsm_enabled']);
        });
    }
};
