<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salon_crm_staff_hours')) {
            return;
        }

        Schema::create('salon_crm_staff_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time')->default('09:00:00');
            $table->time('end_time')->default('21:00:00');
            $table->boolean('is_off')->default(false);
            $table->timestamps();

            $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('salon_crm_staff')->cascadeOnDelete();
            $table->unique(['staff_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_crm_staff_hours');
    }
};
