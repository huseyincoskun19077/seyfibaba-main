<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('settings', 'topbar_announcement')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->text('topbar_announcement')->nullable();
            });
        } else {
            // string(255) -> text without doctrine/dbal
            try {
                DB::statement('ALTER TABLE settings MODIFY topbar_announcement TEXT NULL');
            } catch (\Throwable $e) {
                // ignore if already text / unsupported
            }
        }

        $default = json_encode([
            'Her Satıcıda 1000 TL Üzeri KARGO ÜCRETSİZ',
        ], JSON_UNESCAPED_UNICODE);

        $rows = DB::table('settings')->select('id', 'topbar_announcement')->get();
        foreach ($rows as $row) {
            $raw = $row->topbar_announcement;
            if ($raw === null || $raw === '') {
                DB::table('settings')->where('id', $row->id)->update([
                    'topbar_announcement' => $default,
                ]);
                continue;
            }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                continue;
            }
            DB::table('settings')->where('id', $row->id)->update([
                'topbar_announcement' => json_encode([trim($raw)], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        // keep column
    }
};
