<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('customer_confirmed_at')->nullable()->after('order_declined_date');
            $table->date('order_confirmed_date')->nullable()->after('customer_confirmed_at');
            $table->timestamp('auto_complete_date')->nullable()->after('order_confirmed_date');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_confirmed_at', 'order_confirmed_date', 'auto_complete_date']);
        });
    }
};
