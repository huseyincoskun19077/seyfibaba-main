<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'welcome_sms_sent')) {
                $table->boolean('welcome_sms_sent')->nullable()->after('quick_registration_note');
            }
            if (! Schema::hasColumn('vendors', 'welcome_sms_sent_at')) {
                $table->timestamp('welcome_sms_sent_at')->nullable()->after('welcome_sms_sent');
            }
            if (! Schema::hasColumn('vendors', 'welcome_email_sent')) {
                $table->boolean('welcome_email_sent')->nullable()->after('welcome_sms_sent_at');
            }
            if (! Schema::hasColumn('vendors', 'welcome_email_sent_at')) {
                $table->timestamp('welcome_email_sent_at')->nullable()->after('welcome_email_sent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            foreach (['welcome_email_sent_at', 'welcome_email_sent', 'welcome_sms_sent_at', 'welcome_sms_sent'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
