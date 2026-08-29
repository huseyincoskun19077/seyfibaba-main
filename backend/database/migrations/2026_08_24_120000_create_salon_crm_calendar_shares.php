<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salon_crm_calendar_shares')) {
            return;
        }

        Schema::create('salon_crm_calendar_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('token', 40)->unique();
            $table->string('horizon', 24)->default('today_tomorrow');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['salon_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_crm_calendar_shares');
    }
};
