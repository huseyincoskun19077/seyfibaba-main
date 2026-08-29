<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salon_crm_salons') && !Schema::hasColumn('salon_crm_salons', 'fcm_token')) {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                $table->string('fcm_token', 512)->nullable()->after('api_token');
            });
        }

        if (Schema::hasTable('salon_crm_staff') && !Schema::hasColumn('salon_crm_staff', 'fcm_token')) {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                $table->string('fcm_token', 512)->nullable()->after('api_token');
            });
        }

        if (Schema::hasTable('salon_crm_customers') && !Schema::hasColumn('salon_crm_customers', 'fcm_token')) {
            Schema::table('salon_crm_customers', function (Blueprint $table) {
                $table->string('fcm_token', 512)->nullable()->after('api_token');
            });
        }

        if (Schema::hasTable('salon_crm_appointments') && !Schema::hasColumn('salon_crm_appointments', 'reminder_sent_at')) {
            Schema::table('salon_crm_appointments', function (Blueprint $table) {
                $table->timestamp('reminder_sent_at')->nullable()->after('is_block');
                $table->index(['status', 'starts_at', 'reminder_sent_at'], 'salon_crm_appt_reminder_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('salon_crm_appointments') && Schema::hasColumn('salon_crm_appointments', 'reminder_sent_at')) {
            Schema::table('salon_crm_appointments', function (Blueprint $table) {
                $table->dropIndex('salon_crm_appt_reminder_idx');
                $table->dropColumn('reminder_sent_at');
            });
        }

        if (Schema::hasTable('salon_crm_customers') && Schema::hasColumn('salon_crm_customers', 'fcm_token')) {
            Schema::table('salon_crm_customers', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        if (Schema::hasTable('salon_crm_staff') && Schema::hasColumn('salon_crm_staff', 'fcm_token')) {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        if (Schema::hasTable('salon_crm_salons') && Schema::hasColumn('salon_crm_salons', 'fcm_token')) {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }
    }
};
