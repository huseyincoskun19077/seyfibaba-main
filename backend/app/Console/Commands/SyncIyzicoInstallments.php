<?php

namespace App\Console\Commands;

use Database\Seeders\IyzicoInstallmentSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncIyzicoInstallments extends Command
{
    protected $signature = 'iyzico:sync-installments';

    protected $description = 'Iyzico onay tablosuna göre kategori max_installment değerlerini günceller';

    public function handle(): int
    {
        if (! Schema::hasColumn('categories', 'max_installment')) {
            $this->error('categories.max_installment kolonu yok. Önce php artisan migrate çalıştırın.');

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => IyzicoInstallmentSeeder::class, '--force' => true]);
        $this->info('Tamamlandı. Admin panelden kontrol: /admin/installment-categories');

        return self::SUCCESS;
    }
}
