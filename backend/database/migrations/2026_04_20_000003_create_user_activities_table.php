<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_activities')) {
            return;
        }

        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('activity_type', 50); // view, cart, wishlist, purchase, login, register
            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('referrer')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('session_id');
            $table->index('activity_type');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_activities');
    }
};