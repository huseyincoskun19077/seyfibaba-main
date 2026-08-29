<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVendorIdAndCouponIdToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'vendor_id')) {
                $table->integer('vendor_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('orders', 'coupon_id')) {
                $table->integer('coupon_id')->nullable()->after('coupon_coast');
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vendor_id', 'coupon_id']);
        });
    }
}