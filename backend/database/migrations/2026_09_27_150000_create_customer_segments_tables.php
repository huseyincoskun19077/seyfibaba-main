<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADDITIVE ONLY — mevcut categories/sub/child tablolarına dokunmaz, slug silmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_segments')) {
            Schema::create('customer_segments', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('short_name')->nullable();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->string('image')->nullable();
                $table->unsignedInteger('serial')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('show_on_guest_home')->default(true);
                $table->boolean('is_primary_home')->default(false);
                $table->unsignedTinyInteger('vendor_diversity')->default(3);
                $table->unsignedTinyInteger('home_product_limit')->default(12);
                $table->string('business_type_key', 40)->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_segment_taxonomies')) {
            Schema::create('customer_segment_taxonomies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_segment_id')->constrained('customer_segments')->cascadeOnDelete();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->unsignedBigInteger('sub_category_id')->nullable()->index();
                $table->unsignedBigInteger('child_category_id')->nullable()->index();
                $table->unsignedInteger('serial')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(
                    ['customer_segment_id', 'category_id', 'sub_category_id', 'child_category_id'],
                    'cst_segment_taxonomy_unique'
                );
            });
        }

        if (! Schema::hasTable('customer_segment_products')) {
            Schema::create('customer_segment_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_segment_id')->constrained('customer_segments')->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedInteger('serial')->default(0);
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_forced')->default(false);
                $table->timestamps();
                $table->unique(['customer_segment_id', 'product_id'], 'csp_segment_product_unique');
            });
        }

        if (! Schema::hasTable('segment_assignment_queue')) {
            Schema::create('segment_assignment_queue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->json('suggested_segment_ids')->nullable();
                $table->string('reason', 255)->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('segment_assignment_queue');
        Schema::dropIfExists('customer_segment_products');
        Schema::dropIfExists('customer_segment_taxonomies');
        Schema::dropIfExists('customer_segments');
    }
};
