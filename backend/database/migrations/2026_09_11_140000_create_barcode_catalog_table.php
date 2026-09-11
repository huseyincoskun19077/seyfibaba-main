<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('barcode_catalog')) {
            return;
        }

        Schema::create('barcode_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 64)->unique();
            $table->string('name')->nullable();
            $table->string('short_name', 120)->nullable();
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->unsignedBigInteger('category_id')->default(0);
            $table->unsignedBigInteger('sub_category_id')->default(0);
            $table->unsignedBigInteger('child_category_id')->default(0);
            $table->unsignedBigInteger('brand_id')->default(0);
            $table->string('thumb_image')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('tags')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->decimal('weight', 12, 2)->default(0);
            $table->unsignedBigInteger('source_product_id')->nullable()->index();
            $table->unsignedBigInteger('source_vendor_id')->nullable()->index();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_catalog');
    }
};
