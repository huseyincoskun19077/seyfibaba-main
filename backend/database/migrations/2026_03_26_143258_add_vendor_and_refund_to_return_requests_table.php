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
            if (!Schema::hasColumn('return_requests', 'vendor_id')) {
                $table->integer('vendor_id')->after('order_product_id');
            }
            if (!Schema::hasColumn('return_requests', 'refund_amount')) {
                $table->decimal('refund_amount', 15, 2)->default(0.00)->after('status');
            }
            if (!Schema::hasColumn('return_requests', 'vendor_response')) {
                $table->text('vendor_response')->nullable()->after('refund_amount');
            }
            if (!Schema::hasColumn('return_requests', 'admin_response')) {
                $table->text('admin_response')->nullable()->after('vendor_response');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropColumn(['vendor_id', 'refund_amount', 'vendor_response', 'admin_response']);
        });
    }
};
