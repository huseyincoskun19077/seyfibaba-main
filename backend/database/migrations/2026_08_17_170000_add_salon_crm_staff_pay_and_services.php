<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salon_crm_staff')) {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                if (!Schema::hasColumn('salon_crm_staff', 'pay_type')) {
                    $table->string('pay_type', 20)->default('percent')->after('commission_percent');
                }
                if (!Schema::hasColumn('salon_crm_staff', 'pay_period')) {
                    $table->string('pay_period', 20)->default('monthly')->after('pay_type');
                }
                if (!Schema::hasColumn('salon_crm_staff', 'salary_amount')) {
                    $table->decimal('salary_amount', 12, 2)->default(0)->after('pay_period');
                }
            });
        }

        if (!Schema::hasTable('salon_crm_staff_services')) {
            Schema::create('salon_crm_staff_services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('salon_id');
                $table->unsignedBigInteger('staff_id');
                $table->unsignedBigInteger('service_id');
                $table->decimal('price', 12, 2)->default(0);
                $table->unsignedSmallInteger('duration_minutes')->nullable();
                $table->timestamps();

                $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
                $table->foreign('staff_id')->references('id')->on('salon_crm_staff')->cascadeOnDelete();
                $table->foreign('service_id')->references('id')->on('salon_crm_services')->cascadeOnDelete();
                $table->unique(['staff_id', 'service_id']);
            });
        }

        if (!Schema::hasTable('salon_crm_salary_payments')) {
            Schema::create('salon_crm_salary_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('salon_id');
                $table->unsignedBigInteger('staff_id');
                $table->string('pay_type', 20);
                $table->string('pay_period', 20);
                $table->string('period_key', 16);
                $table->decimal('suggested_amount', 12, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('status', 20)->default('pending');
                $table->timestamp('owner_confirmed_at')->nullable();
                $table->timestamp('staff_confirmed_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->unsignedBigInteger('ledger_entry_id')->nullable();
                $table->string('notes', 500)->nullable();
                $table->timestamps();

                $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
                $table->foreign('staff_id')->references('id')->on('salon_crm_staff')->cascadeOnDelete();
                $table->index(['salon_id', 'staff_id', 'period_key']);
                $table->index(['salon_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_crm_salary_payments');
        Schema::dropIfExists('salon_crm_staff_services');

        if (Schema::hasTable('salon_crm_staff')) {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                foreach (['salary_amount', 'pay_period', 'pay_type'] as $col) {
                    if (Schema::hasColumn('salon_crm_staff', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
