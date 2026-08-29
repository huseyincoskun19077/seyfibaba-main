<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_hand_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('terms_title')->default('İkinci El Kullanım Koşulları');
            $table->longText('terms_content')->nullable();
            $table->string('privacy_title')->default('İkinci El KVKK / Gizlilik Metni');
            $table->longText('privacy_content')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_agreements');
    }
};

