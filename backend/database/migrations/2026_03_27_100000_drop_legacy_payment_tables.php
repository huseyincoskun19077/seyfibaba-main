<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('paystack_and_mollies');
        Schema::dropIfExists('flutterwaves');
        Schema::dropIfExists('instamojo_payments');
        Schema::dropIfExists('myfatoorah_payments');
        Schema::dropIfExists('sslcommerz_payments');
        Schema::dropIfExists('baksh_payments');
    }

    public function down(): void
    {
        // These tables belonged to removed payment gateways.
        // Re-creating them is not supported.
    }
};
