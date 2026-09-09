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
                if (! Schema::hasColumn('vendor_sentos_settings', 'channel_id')) {
                    $table->unsignedBigInteger('channel_id')->nullable()->after('is_enabled');
                }
                if (! Schema::hasColumn('vendor_sentos_settings', 'warehouse_id')) {
                    $table->unsignedBigInteger('warehouse_id')->nullable()->after('channel_id');
                }
                if (! Schema::hasColumn('vendor_sentos_settings', 'push_orders')) {
                    $table->boolean('push_orders')->default(true)->after('warehouse_id');
                }
            });
        }

        if (! Schema::hasTable('vendor_sentos_order_maps')) {
            Schema::create('vendor_sentos_order_maps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->unsignedBigInteger('order_id');
                $table->string('sentos_external_order_id', 150);
                $table->unsignedBigInteger('sentos_order_id')->nullable();
                $table->string('sentos_order_code', 255)->nullable();
                $table->unsignedTinyInteger('last_sentos_status')->nullable();
                $table->string('last_sync_status', 32)->nullable();
                $table->string('last_sync_message', 1000)->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['vendor_id', 'order_id'], 'vendor_sentos_order_unique');
                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_sentos_order_maps');

        if (Schema::hasTable('vendor_sentos_settings')) {
            Schema::table('vendor_sentos_settings', function (Blueprint $table) {
                foreach (['push_orders', 'warehouse_id', 'channel_id'] as $column) {
                    if (Schema::hasColumn('vendor_sentos_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
