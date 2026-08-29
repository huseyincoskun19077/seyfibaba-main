<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('second_hand_listings', function (Blueprint $table) {
            $table->string('province', 190)->nullable()->after('city_id')->index();
            $table->string('locality', 190)->nullable()->after('district')->index();
            $table->string('neighborhood', 190)->nullable()->after('locality')->index();
        });
    }

    public function down(): void
    {
        Schema::table('second_hand_listings', function (Blueprint $table) {
            $table->dropColumn(['province', 'locality', 'neighborhood']);
        });
    }
};
