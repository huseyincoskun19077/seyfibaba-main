<?php

namespace App\Console\Commands;

use App\Support\CommissionLedgerCleanup;
use Illuminate\Console\Command;

class CleanupCommissionLedger extends Command
{
    protected $signature = 'commission:cleanup-ledger';

    protected $description = 'Ödenmemiş veya geçersiz siparişlere bağlı komisyon kayıtlarını temizler';

    public function handle(): int
    {
        $deleted = CommissionLedgerCleanup::purgeInvalidRows();

        $this->info("Temizlenen komisyon kaydı: {$deleted}");

        return self::SUCCESS;
    }
}
