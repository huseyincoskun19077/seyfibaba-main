<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // OpenAI / Groq settings
            $table->string('openai_api_key', 500)->nullable();
            $table->string('openai_model', 100)->default('gpt-4o-mini');
            $table->unsignedInteger('openai_timeout')->default(60);
            $table->boolean('openai_enabled')->default(false);

            // Anthropic Claude settings
            $table->string('claude_api_key', 500)->nullable();
            $table->string('claude_model', 100)->default('claude-sonnet-4-5-20250929');
            $table->unsignedInteger('claude_timeout')->default(60);
            $table->boolean('claude_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'openai_api_key', 'openai_model', 'openai_timeout', 'openai_enabled',
                'claude_api_key', 'claude_model', 'claude_timeout', 'claude_enabled',
            ]);
        });
    }
};
