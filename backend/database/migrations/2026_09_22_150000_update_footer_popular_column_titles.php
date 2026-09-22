<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('footers')) {
            return;
        }

        $footer = DB::table('footers')->orderBy('id')->first();
        if (! $footer) {
            return;
        }

        $updates = [];
        if (empty($footer->first_column) || $footer->first_column === 'First Column' || stripos((string) $footer->first_column, 'first') !== false) {
            $updates['first_column'] = 'Popüler Marka ve Mağazalar';
        }
        if (empty($footer->second_column) || $footer->second_column === 'Second Column' || stripos((string) $footer->second_column, 'second') !== false) {
            $updates['second_column'] = 'Popüler Sayfalar';
        }
        if (empty($footer->third_column) || $footer->third_column === 'Third Column' || stripos((string) $footer->third_column, 'third') !== false) {
            $updates['third_column'] = 'Yardım';
        }

        if ($updates) {
            DB::table('footers')->where('id', $footer->id)->update($updates);
        }
    }

    public function down(): void
    {
        // no-op
    }
};
