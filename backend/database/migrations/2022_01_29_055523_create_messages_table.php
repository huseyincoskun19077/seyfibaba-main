<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('seller_id');
            $table->text('message');
            $table->string('send_by')->default('customer');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->tinyInteger('customer_read_msg')->default(0);
            $table->tinyInteger('seller_read_msg')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'seller_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
