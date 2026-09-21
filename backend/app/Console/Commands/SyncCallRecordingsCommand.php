<?php

namespace App\Console\Commands;

use App\Services\NetsantralCallRecordingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncCallRecordingsCommand extends Command
{
    protected $signature = 'calls:sync-recordings
        {--from= : Y-m-d başlangıç (varsayılan dün)}
        {--to= : Y-m-d bitiş (varsayılan bugün)}
        {--no-download : Sadece CDR meta, ses indirme}';

    protected $description = 'Netgsm Netsantral CDR + ses kayıtlarını Seyfibaba\'ya senkronlar';

    public function handle(NetsantralCallRecordingService $service): int
    {
        $from = Carbon::parse($this->option('from') ?: now()->subDay()->toDateString())->startOfDay();
        $to = Carbon::parse($this->option('to') ?: now()->toDateString())->endOfDay();
        $download = ! $this->option('no-download');

        $this->info("Senkron: {$from->toDateString()} → {$to->toDateString()} (audio: " . ($download ? 'evet' : 'hayır') . ')');

        $result = $service->syncDateRange($from, $to, $download);

        if (! empty($result['message']) && ($result['fetched'] ?? 0) === 0) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'fetched=%d upserted=%d downloaded=%d failed=%d',
            $result['fetched'],
            $result['upserted'],
            $result['downloaded'],
            $result['failed']
        ));

        return self::SUCCESS;
    }
}
