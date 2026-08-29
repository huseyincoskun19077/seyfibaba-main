<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeyfibabaDumpSeeder extends Seeder
{
    /**
     * Seyfibaba temel veritabanı dump'ını import eder.
     * Kullanım: php artisan db:seed --class=SeyfibabaDumpSeeder
     */
    public function run(): void
    {
        $dumpPath = database_path('seyfibaba_canli_dump.sql');

        if (!file_exists($dumpPath)) {
            $this->command->error("SQL dump bulunamadı: {$dumpPath}");
            return;
        }

        $this->command->info('Seyfibaba DB dump import ediliyor...');

        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');

        $command = sprintf(
            'mysql -h %s -P %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($dumpPath)
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            $this->command->info('Seyfibaba DB dump başarıyla import edildi.');
        } else {
            $this->command->error('Import hatası: ' . implode("\n", $output));
        }
    }
}
