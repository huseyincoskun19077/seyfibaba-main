<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_hand_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id')->index();
            $table->string('kind', 20)->default('file')->index(); // image|file
            $table->string('path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->foreign('message_id')
                ->references('id')
                ->on('second_hand_messages')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_message_attachments');
    }
};

