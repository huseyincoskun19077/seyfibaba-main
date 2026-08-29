<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('otp_code', 6);
            $table->enum('purpose', ['register', 'password_reset', 'phone_verify', 'seller_first_login'])->default('register');
            $table->tinyInteger('attempts')->unsigned()->default(0);
            $table->tinyInteger('max_attempts')->unsigned()->default(3);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['phone', 'purpose'], 'idx_phone_purpose');
            $table->index('expires_at', 'idx_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
