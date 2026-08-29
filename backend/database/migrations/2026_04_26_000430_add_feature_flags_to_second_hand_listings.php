<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('second_hand_listings', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status')->index();
            $table->boolean('is_urgent')->default(false)->after('is_featured')->index();
            $table->timestamp('featured_at')->nullable()->after('is_urgent')->index();
        });
    }

    public function down(): void
    {
        Schema::table('second_hand_listings', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'is_urgent', 'featured_at']);
        });
    }
};

