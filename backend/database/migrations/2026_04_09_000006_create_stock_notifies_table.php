<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockNotifiesTable extends Migration
{
    public function up()
    {
        Schema::create('stock_notifies', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->string('email');
            $table->boolean('notified')->default(false);
            $table->timestamps();
            
            $table->index(['product_id', 'email']);
            $table->unique(['product_id', 'email']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_notifies');
    }
}