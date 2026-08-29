<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store Iyzico payment item details (paymentTransactionId per basket item) for refunds
        Schema::table('orders', function (Blueprint $table) {
            $table->json('iyzico_payment_data')->nullable()->after('transection_id');
        });

        // Track refund transaction from Iyzico
        Schema::table('return_requests', function (Blueprint $table) {
            $table->string('refund_transaction_id')->nullable()->after('refund_method');
            $table->string('refund_status')->nullable()->after('refund_transaction_id');
            $table->text('refund_error')->nullable()->after('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('iyzico_payment_data');
        });

        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropColumn(['refund_transaction_id', 'refund_status', 'refund_error']);
        });
    }
};
