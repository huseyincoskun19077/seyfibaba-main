<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personalization_showcases')) {
            return;
        }

        Schema::create('personalization_showcases', function (Blueprint $table) {
            $table->id();
            $table->string('business_type', 40)->unique();
            $table->string('title', 120)->default('Sana Özel');
            $table->text('category_ids')->nullable(); // json: [1,2,3]
            $table->text('product_ids')->nullable();
            $table->text('vendor_ids')->nullable();
            // Yeni dükkan açanlar için ekstra / alternatif
            $table->text('opening_category_ids')->nullable();
            $table->text('opening_product_ids')->nullable();
            $table->text('opening_vendor_ids')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personalization_showcases');
    }
};
