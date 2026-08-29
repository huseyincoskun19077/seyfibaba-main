<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_crm_salons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('name', 160);
            $table->string('type', 40)->default('kuafor'); // kuafor | guzellik
            $table->string('phone', 32)->nullable();
            $table->timestamp('trial_ends_at');
            $table->unsignedInteger('threshold_amount')->default(10000);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('salon_crm_access_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->string('period', 7); // Y-m
            $table->string('type', 40); // immediate_unlock | next_month_credit
            $table->decimal('qualified_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
            $table->unique(['salon_id', 'period', 'type']);
        });

        Schema::create('salon_crm_staff', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->string('name', 120);
            $table->string('username', 80);
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
            $table->unique(['salon_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_crm_staff');
        Schema::dropIfExists('salon_crm_access_grants');
        Schema::dropIfExists('salon_crm_salons');
    }
};
