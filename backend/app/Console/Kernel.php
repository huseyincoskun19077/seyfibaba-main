<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Her saat çalışarak 3 günlük payout'ları kontrol et
        $schedule->command('orders:process-payouts')->hourly();
        
        // 15 günlük otomatik tamamlama (mevcut)
        $schedule->command('orders:auto-complete')->daily();

        // Satır bazlı otomatik onay (gelecek uyumluluk — delivered_at doluysa çalışır)
        $schedule->command('order-items:auto-confirm')->daily();

        // Ödenmemiş taslak siparişleri periyodik temizle
        $schedule->command('orders:cleanup-drafts --minutes=180')->hourly();

        // KYC yapmayan satıcılara 3 günde bir hatırlatma (push + uygulama içi)
        $schedule->command('sellers:kyc-remind --days=3')->dailyAt('10:00');

        // Kampanya başlangıç bildirimleri
        $schedule->command('campaigns:notify')->dailyAt('09:00');

        // Ürün bakış hatırlatmaları
        $schedule->command('products:view-remind')->hourly();

        // Salon CRM — randevuya ~30 dk kala push
        $schedule->command('salon-crm:appointment-remind')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
