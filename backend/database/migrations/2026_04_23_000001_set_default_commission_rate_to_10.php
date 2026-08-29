<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hedef: varsayılan komisyon %10 (satıcı net %90).
        // Mevcut ayarı admin panelden değiştirebilirsiniz; bu migration sadece boş/0 olanları set eder.
        DB::table('settings')
            ->where(function ($q) {
                $q->whereNull('default_commission_rate')
                    ->orWhere('default_commission_rate', '=', 0);
            })
            ->update(['default_commission_rate' => 10.00]);
    }

    public function down(): void
    {
        // Geri dönüşte otomatik bir değer atamıyoruz; admin panelden yönetiliyor.
    }
};

