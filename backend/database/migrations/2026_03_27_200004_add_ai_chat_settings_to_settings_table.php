<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('ai_chat_enabled')->default(false)->after('map_key');
            $table->string('ai_chat_provider', 20)->default('groq')->after('ai_chat_enabled');
            $table->string('ai_chat_api_key', 255)->nullable()->after('ai_chat_provider');
            $table->string('ai_chat_model', 100)->default('llama-3.3-70b-versatile')->after('ai_chat_api_key');
            $table->unsignedSmallInteger('ai_chat_max_tokens')->default(1024)->after('ai_chat_model');
            $table->decimal('ai_chat_temperature', 3, 2)->default(0.70)->after('ai_chat_max_tokens');
            $table->text('ai_chat_system_prompt')->nullable()->after('ai_chat_temperature');
            $table->boolean('ai_chat_guest_enabled')->default(true)->after('ai_chat_system_prompt');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_chat_enabled', 'ai_chat_provider', 'ai_chat_api_key',
                'ai_chat_model', 'ai_chat_max_tokens', 'ai_chat_temperature',
                'ai_chat_system_prompt', 'ai_chat_guest_enabled',
            ]);
        });
    }
};
