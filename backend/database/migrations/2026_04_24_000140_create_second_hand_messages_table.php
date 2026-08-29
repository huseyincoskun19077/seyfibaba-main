<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_hand_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('sender_id')->index();

            $table->text('body');
            $table->timestamp('read_at')->nullable()->index();

            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('second_hand_conversations')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_messages');
    }
};

