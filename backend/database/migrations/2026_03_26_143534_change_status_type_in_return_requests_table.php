<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->integer('status')->default(0)->change();
            if (!Schema::hasColumn('return_requests', 'qty')) {
                $table->integer('qty')->default(1)->after('order_product_id');
            }
            if (!Schema::hasColumn('return_requests', 'refund_method')) {
                $table->string('refund_method')->nullable()->after('refund_amount');
            }
            if (Schema::hasColumn('return_requests', 'vendor_id')) {
                $table->renameColumn('vendor_id', 'seller_id');
            } else if (!Schema::hasColumn('return_requests', 'seller_id')) {
                $table->integer('seller_id')->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->dropColumn(['qty', 'refund_method']);
            $table->renameColumn('seller_id', 'vendor_id');
        });
    }
};
