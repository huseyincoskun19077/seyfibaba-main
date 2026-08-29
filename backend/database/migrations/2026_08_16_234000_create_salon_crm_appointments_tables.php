<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_crm_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->string('name', 120);
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
            $table->index(['salon_id', 'is_active']);
        });

        Schema::create('salon_crm_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name', 120);
            $table->string('customer_name', 120);
            $table->string('customer_phone', 32);
            $table->dateTime('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status', 20)->default('scheduled'); // scheduled|completed|cancelled|no_show
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('salon_crm_staff')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('salon_crm_services')->nullOnDelete();
            $table->index(['salon_id', 'starts_at']);
            $table->index(['salon_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_crm_appointments');
        Schema::dropIfExists('salon_crm_services');
    }
};
