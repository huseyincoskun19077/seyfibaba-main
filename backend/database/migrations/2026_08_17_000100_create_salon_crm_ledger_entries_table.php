<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_crm_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('type', 20); // income | expense
            $table->string('category', 80)->nullable();
            $table->string('title', 160);
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('salon_id')->references('id')->on('salon_crm_salons')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('salon_crm_staff')->nullOnDelete();
            $table->index(['salon_id', 'entry_date']);
            $table->index(['salon_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_crm_ledger_entries');
    }
};
