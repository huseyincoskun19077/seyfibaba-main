<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_hand_user_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blocker_id')->index();
            $table->unsignedBigInteger('blocked_id')->index();
            $table->string('reason', 100)->nullable();
            $table->timestamps();
            $table->unique(['blocker_id', 'blocked_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_user_blocks');
    }
};

