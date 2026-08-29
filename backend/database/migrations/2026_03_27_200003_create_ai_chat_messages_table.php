<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->unsignedInteger('tokens_used')->nullable();
            $table->string('provider', 20)->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')
                  ->references('id')->on('ai_chat_conversations')
                  ->cascadeOnDelete();
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
