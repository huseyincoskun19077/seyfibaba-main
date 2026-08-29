<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iyzico_payments', function (Blueprint $table) {
            $table->id();
            $table->boolean('status')->default(0);
            $table->string('api_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->boolean('is_test_mode')->default(1);
            $table->boolean('marketplace_mode')->default(1);
            $table->string('sub_merchant_key')->nullable();
            $table->text('store_sub_merchant_keys')->nullable()->comment('JSON: {"vendor_id":"sub_merchant_key"}');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iyzico_payments');
    }
};
