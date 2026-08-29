<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_hand_listing_images', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('listing_id')->index();
            $table->string('file_path');
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('second_hand_listings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_listing_images');
    }
};

