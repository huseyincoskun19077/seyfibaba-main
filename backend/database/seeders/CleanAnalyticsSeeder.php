<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanAnalyticsSeeder extends Seeder
{
    /**
     * Sahte Google Analytics ID'yi temizler.
     * Admin panelden gerçek ID girilene kadar boş kalır.
     */
    public function run(): void
    {
        DB::table('google_analytics')->where('id', 1)->update([
            'analytic_id' => '',
            'status' => 0,
        ]);

        $this->command->info('Google Analytics ID temizlendi — admin panelden gerçek ID girilmeli.');

        // Banka havalesi bilgilerini Türkçeleştir
        DB::table('bank_payments')->where('id', 1)->update([
            'account_info' => "Hesap Sahibi: Seyfibaba Tic. Ltd. Şti.\nBanka: ................\nIBAN: TR00 0000 0000 0000 0000 0000 00\nHesap No: ................\nŞube: ................\n\nHavale/EFT yaparken sipariş numaranızı açıklama kısmına yazınız.",
        ]);
        $this->command->info('Banka havalesi bilgileri Türkçeleştirildi.');

        // Sahte Facebook Pixel ID'yi temizle
        DB::table('facebook_pixels')->where('id', 1)->update([
            'app_id' => '',
            'status' => 0,
        ]);
        $this->command->info('Facebook Pixel ID temizlendi.');
    }
}
