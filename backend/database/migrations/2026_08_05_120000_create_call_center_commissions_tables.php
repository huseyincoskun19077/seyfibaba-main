<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_center_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->unique();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedInteger('product_count')->default(0);
            $table->decimal('calculated_total', 12, 2)->default(0);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('paid_total', 12, 2)->default(0);
            $table->string('status', 32)->default('open'); // open | awaiting_payment
            $table->json('breakdown')->nullable();
            $table->timestamp('agent_approved_at')->nullable();
            $table->timestamps();

            $table->index('admin_id');
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
        });

        Schema::create('call_center_commission_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('admin_id');
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('paid_by_admin_id');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['admin_id', 'created_at']);
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
            $table->foreign('paid_by_admin_id')->references('id')->on('admins')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center_commission_payments');
        Schema::dropIfExists('call_center_commissions');
    }
};
