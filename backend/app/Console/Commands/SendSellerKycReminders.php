<?php

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Notifications\KycReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendSellerKycReminders extends Command
{
    protected $signature = 'sellers:kyc-remind {--days=3 : Aynı satıcıya tekrar hatırlatma aralığı (gün)}';

    protected $description = 'KYC yapmamış aktif satıcılara push + uygulama içi hatırlatma gönderir';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $sent = 0;
        $skipped = 0;

        Vendor::query()
            ->with('user')
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('kyc_status')
                    ->orWhere('kyc_status', 'not_submitted');
            })
            ->orderBy('id')
            ->chunkById(100, function ($vendors) use ($days, &$sent, &$skipped) {
                foreach ($vendors as $vendor) {
                    $user = $vendor->user;
                    if (! $user) {
                        $skipped++;
                        continue;
                    }

                    $cacheKey = "seller_kyc_reminder:{$user->id}";
                    if (! Cache::add($cacheKey, 1, now()->addDays($days))) {
                        $skipped++;
                        continue;
                    }

                    $user->notify(new KycReminderNotification($vendor));
                    $sent++;
                }
            });

        $this->info("KYC hatırlatma: gönderilen={$sent}, atlanan={$skipped}");

        return self::SUCCESS;
    }
}
