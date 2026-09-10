<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_softtr_settings')) {
            Schema::create('vendor_softtr_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id')->unique();
                $table->string('api_base_url', 255);
                $table->text('api_user');
                $table->text('api_password');
                $table->boolean('is_enabled')->default(false);
                $table->timestamp('last_tested_at')->nullable();
                $table->string('last_test_status', 32)->nullable();
                $table->string('last_test_message', 500)->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->string('last_sync_status', 32)->nullable();
                $table->string('last_sync_message', 500)->nullable();
                $table->json('last_sync_stats')->nullable();
                $table->timestamps();

                $table->foreign('vendor_id')
                    ->references('id')
                    ->on('vendors')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('vendor_softtr_product_maps')) {
            Schema::create('vendor_softtr_product_maps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->string('softtr_product_id', 64);
                $table->unsignedBigInteger('product_id');
                $table->string('softtr_sku', 191)->nullable();
                $table->string('softtr_barcode', 191)->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['vendor_id', 'softtr_product_id'], 'vendor_softtr_product_unique');
                $table->index(['vendor_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_softtr_product_maps');
        Schema::dropIfExists('vendor_softtr_settings');
    }
};
