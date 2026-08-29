<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('second_hand_listings', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('inactive_reason')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('submitted_at')->index();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by')->index();
            $table->string('review_note', 500)->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('second_hand_listings', function (Blueprint $table) {
            $table->dropColumn(['submitted_at', 'reviewed_by', 'reviewed_at', 'review_note']);
        });
    }
};

