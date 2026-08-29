<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('product_view_sessions')) {
            return;
        }

        Schema::create('product_view_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('duration')->default(0); // seconds spent on product
            $table->boolean('engaged')->default(false); // user's level of interaction
            $table->text('referrer')->nullable();
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // Note: user_id foreign key intentionally removed for compatibility
            $table->index('product_id');
            $table->index('user_id');
            $table->index('session_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_view_sessions');
    }
};