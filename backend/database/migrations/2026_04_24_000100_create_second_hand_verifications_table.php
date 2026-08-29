<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_hand_verifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            $table->string('business_name');
            $table->string('tax_number')->index();

            // Vergi levhası (gerekirse)
            $table->string('tax_document_path')->nullable();
            $table->string('tax_document_original_name')->nullable();
            $table->unsignedBigInteger('tax_document_size')->nullable();

            // pending | approved | rejected
            $table->string('status')->default('pending')->index();
            $table->timestamp('submitted_at')->nullable()->index();

            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_verifications');
    }
};

