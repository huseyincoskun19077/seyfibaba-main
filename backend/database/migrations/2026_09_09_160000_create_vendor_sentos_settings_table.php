<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_sentos_settings')) {
            return;
        }

        Schema::create('vendor_sentos_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->unique();
            $table->string('api_base_url', 255);
            $table->text('api_key');
            $table->text('api_secret');
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 32)->nullable();
            $table->string('last_test_message', 500)->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_sentos_settings');
    }
};
