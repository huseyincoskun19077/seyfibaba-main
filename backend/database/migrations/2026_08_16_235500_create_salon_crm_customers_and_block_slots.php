<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_crm_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->string('name', 120);
            $table->string('phone', 32);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
            $table->unique(['salon_id', 'phone']);
            $table->index(['salon_id', 'name']);
        });

        Schema::table('salon_crm_appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('salon_id');
            $table->boolean('is_block')->default(false)->after('notes');

            $table->foreign('customer_id')->references('id')->on('salon_crm_customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salon_crm_appointments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'is_block']);
        });

        Schema::dropIfExists('salon_crm_customers');
    }
};
