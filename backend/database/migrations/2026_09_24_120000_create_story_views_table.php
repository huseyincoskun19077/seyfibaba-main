<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('story_views')) {
            return;
        }

        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('story_id')->index();
            $table->string('platform', 16)->default('web')->index(); // web|mobile
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('guest_key', 64)->nullable()->index();
            $table->unsignedSmallInteger('product_index')->default(0);
            $table->unsignedSmallInteger('products_total')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->index(['story_id', 'platform']);
            $table->index(['story_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_views');
    }
};
