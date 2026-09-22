<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('settings', 'topbar_announcement')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->text('topbar_announcement')->nullable();
            });
        }

        if (Schema::hasColumn('settings', 'topbar_announcement')) {
            \DB::table('settings')
                ->where(function ($q) {
                    $q->whereNull('topbar_announcement')
                        ->orWhere('topbar_announcement', '');
                })
                ->update([
                    'topbar_announcement' => json_encode(
                        ['Her Satıcıda 1000 TL Üzeri KARGO ÜCRETSİZ'],
                        JSON_UNESCAPED_UNICODE
                    ),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'topbar_announcement')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('topbar_announcement');
            });
        }
    }
};
