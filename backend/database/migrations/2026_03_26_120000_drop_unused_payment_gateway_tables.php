<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('paypal_payments');
        Schema::dropIfExists('stripe_payments');
        Schema::dropIfExists('razorpay_payments');
    }

    public function down(): void
    {
        Schema::create('paypal_payments', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('client_id')->nullable();
            $table->string('secret_id')->nullable();
            $table->string('account_mode')->nullable();
            $table->string('country_code')->nullable();
            $table->string('currency_code')->nullable();
            $table->double('currency_rate')->default(1);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        Schema::create('stripe_payments', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('stripe_key')->nullable();
            $table->string('stripe_secret')->nullable();
            $table->string('country_code')->nullable();
            $table->string('currency_code')->nullable();
            $table->double('currency_rate')->default(1);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        Schema::create('razorpay_payments', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('key')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->string('country_code')->nullable();
            $table->string('currency_code')->nullable();
            $table->double('currency_rate')->default(1);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }
};
