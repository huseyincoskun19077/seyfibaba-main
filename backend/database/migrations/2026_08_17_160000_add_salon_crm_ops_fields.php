<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salon_crm_staff') && !Schema::hasColumn('salon_crm_staff', 'commission_percent')) {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                $table->decimal('commission_percent', 5, 2)->default(0)->after('is_active');
            });
        }

        if (Schema::hasTable('salon_crm_appointments')) {
            Schema::table('salon_crm_appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('salon_crm_appointments', 'block_type')) {
                    $table->string('block_type', 20)->nullable()->after('is_block');
                }
                if (!Schema::hasColumn('salon_crm_appointments', 'payment_method')) {
                    $table->string('payment_method', 20)->nullable()->after('price');
                }
                if (!Schema::hasColumn('salon_crm_appointments', 'payment_status')) {
                    $table->string('payment_status', 20)->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('salon_crm_appointments', 'commission_percent')) {
                    $table->decimal('commission_percent', 5, 2)->nullable()->after('payment_status');
                }
                if (!Schema::hasColumn('salon_crm_appointments', 'staff_share')) {
                    $table->decimal('staff_share', 12, 2)->nullable()->after('commission_percent');
                }
                if (!Schema::hasColumn('salon_crm_appointments', 'owner_share')) {
                    $table->decimal('owner_share', 12, 2)->nullable()->after('staff_share');
                }
            });
        }

        if (Schema::hasTable('salon_crm_ledger_entries')) {
            Schema::table('salon_crm_ledger_entries', function (Blueprint $table) {
                if (!Schema::hasColumn('salon_crm_ledger_entries', 'payment_method')) {
                    $table->string('payment_method', 20)->nullable()->after('category');
                }
                if (!Schema::hasColumn('salon_crm_ledger_entries', 'appointment_id')) {
                    $table->unsignedBigInteger('appointment_id')->nullable()->after('staff_id');
                }
                if (!Schema::hasColumn('salon_crm_ledger_entries', 'staff_share')) {
                    $table->decimal('staff_share', 12, 2)->nullable()->after('amount');
                }
                if (!Schema::hasColumn('salon_crm_ledger_entries', 'owner_share')) {
                    $table->decimal('owner_share', 12, 2)->nullable()->after('staff_share');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('salon_crm_staff') && Schema::hasColumn('salon_crm_staff', 'commission_percent')) {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                $table->dropColumn('commission_percent');
            });
        }

        if (Schema::hasTable('salon_crm_appointments')) {
            Schema::table('salon_crm_appointments', function (Blueprint $table) {
                foreach (['block_type', 'payment_method', 'payment_status', 'commission_percent', 'staff_share', 'owner_share'] as $col) {
                    if (Schema::hasColumn('salon_crm_appointments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('salon_crm_ledger_entries')) {
            Schema::table('salon_crm_ledger_entries', function (Blueprint $table) {
                foreach (['payment_method', 'appointment_id', 'staff_share', 'owner_share'] as $col) {
                    if (Schema::hasColumn('salon_crm_ledger_entries', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
