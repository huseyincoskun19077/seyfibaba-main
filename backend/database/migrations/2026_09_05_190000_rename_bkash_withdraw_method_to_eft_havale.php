<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_methods')) {
            $hasStatus = Schema::hasColumn('withdraw_methods', 'status');

            // Satış komisyonu (%10) zaten bakiyeden düşülmüş; çekimde ek kesinti yok.
            DB::table('withdraw_methods')->update([
                'withdraw_charge' => 0,
                'updated_at' => now(),
            ]);

            DB::table('withdraw_methods')
                ->whereRaw('LOWER(name) LIKE ?', ['%bkash%'])
                ->update(array_filter([
                    'name' => 'EFT/Havale',
                    'description' => 'Satıcı IBAN hesabına banka havalesi / EFT ile ödeme. Lütfen IBAN, hesap sahibi ve banka adını girin. Platform komisyonu satışta kesilir; çekimde ek ücret alınmaz.',
                    'withdraw_charge' => 0,
                    'updated_at' => now(),
                ]));

            $existsEft = DB::table('withdraw_methods')->where('name', 'EFT/Havale')->exists();
            if (! $existsEft) {
                $row = [
                    'name' => 'EFT/Havale',
                    'min_amount' => 50,
                    'max_amount' => 100000,
                    'withdraw_charge' => 0,
                    'description' => 'Satıcı IBAN hesabına banka havalesi / EFT ile ödeme. Lütfen IBAN, hesap sahibi ve banka adını girin. Platform komisyonu satışta kesilir; çekimde ek ücret alınmaz.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($hasStatus) {
                    $row['status'] = 1;
                }
                DB::table('withdraw_methods')->insert($row);
            } else {
                $eftUpdate = [
                    'withdraw_charge' => 0,
                    'updated_at' => now(),
                ];
                if ($hasStatus) {
                    $eftUpdate['status'] = 1;
                }
                DB::table('withdraw_methods')->where('name', 'EFT/Havale')->update($eftUpdate);
            }
        }

        if (Schema::hasTable('seller_withdraws')) {
            DB::table('seller_withdraws')
                ->whereRaw('LOWER(method) LIKE ?', ['%bkash%'])
                ->update([
                    'method' => 'EFT/Havale',
                    'updated_at' => now(),
                ]);

            // Bekleyen taleplerde yanlış ek kesintiyi kaldır (talep = ödenecek tutar)
            DB::table('seller_withdraws')
                ->where('status', 0)
                ->where('withdraw_charge', '>', 0)
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('seller_withdraws')->where('id', $row->id)->update([
                            'withdraw_amount' => $row->total_amount,
                            'withdraw_charge' => 0,
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Geri alınamaz (veri düzeltmesi)
    }
};
