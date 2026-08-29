<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_hand_listings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            // Mevcut kategori ağacıyla uyumlu (nullable: esnek)
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('sub_category_id')->nullable()->index();
            $table->unsignedBigInteger('child_category_id')->nullable()->index();

            $table->string('title');
            $table->longText('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);

            // Şehir/ilçe
            $table->unsignedBigInteger('city_id')->nullable()->index();
            $table->string('district')->nullable()->index();

            // Condition: new | lightly_used | used | defective
            $table->string('condition')->default('used')->index();

            // Status: draft | active | inactive | sold
            $table->string('status')->default('draft')->index();
            $table->string('inactive_reason')->nullable();

            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('sold_at')->nullable();

            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Diğer tabloların isimleri projeye göre değişebildiği için FK koymuyoruz (risk azaltma).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_listings');
    }
};

