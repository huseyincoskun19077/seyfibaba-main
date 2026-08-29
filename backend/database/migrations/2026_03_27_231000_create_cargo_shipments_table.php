<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargo_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('geliver_shipment_id')->nullable()->index();
            $table->string('geliver_transaction_id')->nullable()->index();
            $table->string('carrier_name')->nullable();
            $table->string('barcode')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->text('tracking_url')->nullable();
            $table->text('label_url')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('created_by_type')->default('admin');
            $table->unsignedBigInteger('created_by_id')->default(0);
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_shipments');
    }
};
