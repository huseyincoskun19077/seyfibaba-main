<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_views', function (Blueprint $table) {
            $table->unsignedBigInteger('add_to_cart_count')->default(0)->after('view_count');
            $table->timestamp('last_cart_at')->nullable()->after('last_viewed_at');
        });
    }

    public function down()
    {
        Schema::table('product_views', function (Blueprint $table) {
            $table->dropColumn(['add_to_cart_count', 'last_cart_at']);
        });
    }
};