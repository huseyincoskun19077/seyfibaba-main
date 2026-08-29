<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_document_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('guest_identifier', 64)->nullable()->index();
            $table->unsignedBigInteger('legal_document_id')->nullable();
            $table->string('document_slug', 64);
            $table->string('document_title');
            $table->string('document_version', 32);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('platform', 20)->default('web');
            $table->boolean('consent_status')->default(true);
            $table->string('context', 64)->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->timestamp('consented_at');
            $table->timestamps();

            $table->index(['user_id', 'document_slug']);
            $table->index(['document_slug', 'consented_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_consents');
    }
};
