<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_hand_conversations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('listing_id')->index();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('buyer_id')->index();

            $table->timestamp('last_message_at')->nullable()->index();

            $table->timestamps();

            // 1 ilan için 1 alıcıyla tek konuşma
            $table->unique(['listing_id', 'buyer_id']);

            $table->foreign('listing_id')->references('id')->on('second_hand_listings')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_conversations');
    }
};

