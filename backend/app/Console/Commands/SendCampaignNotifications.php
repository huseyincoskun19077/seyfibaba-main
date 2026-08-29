<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Notifications\CampaignNotification;
use App\Services\PushBroadcastService;
use Illuminate\Console\Command;

class SendCampaignNotifications extends Command
{
    protected $signature = 'campaigns:notify';

    protected $description = 'Bugün başlayan kampanyalar için alıcılara push + uygulama içi bildirim gönderir';

    public function handle(PushBroadcastService $broadcast): int
    {
        $today = now()->toDateString();
        $sent = 0;

        Campaign::query()
            ->where('status', 1)
            ->whereDate('start_date', $today)
            ->whereNull('notified_at')
            ->orderBy('id')
            ->chunkById(50, function ($campaigns) use ($broadcast, &$sent) {
                foreach ($campaigns as $campaign) {
                    $count = $broadcast->sendToAllBuyers(new CampaignNotification($campaign));
                    $campaign->forceFill(['notified_at' => now()])->saveQuietly();
                    $sent += $count;
                }
            });

        $this->info("Kampanya bildirimi gönderildi: {$sent} alıcı");

        return self::SUCCESS;
    }
}
