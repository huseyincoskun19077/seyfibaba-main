<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_callbacks', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name');
            $table->string('phone', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_callbacks');
    }
};
