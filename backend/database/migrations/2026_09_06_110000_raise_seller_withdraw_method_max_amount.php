<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_methods')) {
            return;
        }

        // Eski Shopo limiti (ör. 2000) çekilebilir bakiyeyi engelliyordu
        DB::table('withdraw_methods')->update([
            'max_amount' => 10000000,
            'withdraw_charge' => 0,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        //
    }
};
