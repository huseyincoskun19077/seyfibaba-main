<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_views', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_count')->default(0)->after('add_to_cart_count');
            $table->timestamp('last_purchase_at')->nullable()->after('last_cart_at');
        });
    }

    public function down()
    {
        Schema::table('product_views', function (Blueprint $table) {
            $table->dropColumn(['purchase_count', 'last_purchase_at']);
        });
    }
};