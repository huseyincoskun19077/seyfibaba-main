<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_sentos_settings')) {
            Schema::table('vendor_sentos_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('vendor_sentos_settings', 'last_sync_at')) {
                    $table->timestamp('last_sync_at')->nullable()->after('last_test_message');
                }
                if (! Schema::hasColumn('vendor_sentos_settings', 'last_sync_status')) {
                    $table->string('last_sync_status', 32)->nullable()->after('last_sync_at');
                }
                if (! Schema::hasColumn('vendor_sentos_settings', 'last_sync_message')) {
                    $table->string('last_sync_message', 1000)->nullable()->after('last_sync_status');
                }
                if (! Schema::hasColumn('vendor_sentos_settings', 'last_sync_stats')) {
                    $table->json('last_sync_stats')->nullable()->after('last_sync_message');
                }
            });
        }

        if (! Schema::hasTable('vendor_sentos_category_maps')) {
            Schema::create('vendor_sentos_category_maps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->string('sentos_category_key', 191);
                $table->string('sentos_category_name', 255)->nullable();
                $table->unsignedBigInteger('category_id');
                $table->unsignedBigInteger('sub_category_id')->default(0);
                $table->unsignedBigInteger('child_category_id')->default(0);
                $table->timestamps();

                $table->unique(['vendor_id', 'sentos_category_key'], 'vendor_sentos_cat_unique');
                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('vendor_sentos_product_maps')) {
            Schema::create('vendor_sentos_product_maps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->string('sentos_product_id', 64);
                $table->unsignedBigInteger('product_id');
                $table->string('sentos_sku', 191)->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['vendor_id', 'sentos_product_id'], 'vendor_sentos_product_unique');
                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_sentos_product_maps');
        Schema::dropIfExists('vendor_sentos_category_maps');

        if (Schema::hasTable('vendor_sentos_settings')) {
            Schema::table('vendor_sentos_settings', function (Blueprint $table) {
                foreach (['last_sync_stats', 'last_sync_message', 'last_sync_status', 'last_sync_at'] as $column) {
                    if (Schema::hasColumn('vendor_sentos_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
