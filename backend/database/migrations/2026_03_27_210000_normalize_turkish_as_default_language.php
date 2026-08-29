<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('languages')) {
            return;
        }

        DB::table('languages')->whereNotNull('id')->update(['is_default' => 'No']);

        $turkish = DB::table('languages')->where('lang_code', 'tr')->first();

        if ($turkish) {
            DB::table('languages')
                ->where('id', $turkish->id)
                ->update([
                    'lang_name' => 'Türkçe',
                    'is_default' => 'Yes',
                    'status' => 1,
                    'lang_direction' => 'left_to_right',
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('languages')->insert([
                'lang_code' => 'tr',
                'lang_name' => 'Türkçe',
                'is_default' => 'Yes',
                'status' => 1,
                'lang_direction' => 'left_to_right',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('languages')) {
            return;
        }

        DB::table('languages')
            ->where('lang_code', 'tr')
            ->update([
                'is_default' => 'No',
                'updated_at' => now(),
            ]);
    }
};
