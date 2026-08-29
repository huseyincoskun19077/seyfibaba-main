<?php

namespace App\Console\Commands;

use Database\Seeders\LegalDocumentSeeder;
use Illuminate\Console\Command;

class ImportLegalDocumentsCommand extends Command
{
    protected $signature = 'legal:import-documents';

    protected $description = 'Yasal belge markdown kaynaklarını veritabanına aktarır';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => LegalDocumentSeeder::class,
            '--force' => true,
        ]);

        $this->info('Yasal belgeler güncellendi.');

        return self::SUCCESS;
    }
}
