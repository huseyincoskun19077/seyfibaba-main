<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\OrderPurgeService;
use Illuminate\Console\Command;

class PurgeOrdersCommand extends Command
{
    protected $signature = 'orders:purge-test
                            {--all : TÜM siparişleri sil (ürün/kullanıcı/satıcıya dokunmaz)}
                            {--id=* : Belirli sipariş id listesi}
                            {--dry-run : Silmeden sayıları göster}
                            {--force : Onay istemeden sil}';

    protected $description = 'Test siparişlerini ve bağlı iade/ledger/kargo kayıtlarını güvenli siler. Ürün, kullanıcı, satıcı silinmez.';

    public function handle(OrderPurgeService $purgeService): int
    {
        $ids = collect($this->option('id'))
            ->flatMap(fn ($v) => preg_split('/[,\s]+/', (string) $v) ?: [])
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique()
            ->values();

        $all = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        if (! $all && $ids->isEmpty()) {
            $this->error('Kullanım: --all  veya  --id=1 --id=2  (önce --dry-run önerilir)');
            $this->line('Örnek: php artisan orders:purge-test --all --dry-run');
            $this->line('Örnek: php artisan orders:purge-test --all --force');

            return 1;
        }

        $query = Order::query();
        if (! $all) {
            $query->whereIn('id', $ids->all());
        }

        $orderCount = (clone $query)->count();
        $orderPkIds = (clone $query)->pluck('id');
        $returnCount = ReturnRequest::query()
            ->when($orderPkIds->isNotEmpty(), fn ($q) => $q->whereIn('order_id', $orderPkIds))
            ->when($orderPkIds->isEmpty(), fn ($q) => $q->whereRaw('1=0'))
            ->count();

        $this->warn('SİLİNECEK (ürün / kullanıcı / satıcı SİLİNMEZ):');
        $this->line("  Sipariş: {$orderCount}");
        $this->line("  İade talebi: {$returnCount}");
        $this->line('  Ayrıca: order_products, adres, kargo, commission_ledger, iade görselleri, delivery mesajları');

        if ($orderCount === 0) {
            $this->info('Silinecek sipariş yok.');

            return 0;
        }

        if ($dryRun) {
            $this->info('Dry-run: hiçbir şey silinmedi.');

            return 0;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("{$orderCount} sipariş ve bağlı kayıtlar kalıcı silinsin mi?", false)) {
                $this->info('İptal edildi.');

                return 0;
            }
        }

        $result = $purgeService->purgeMany($all ? null : $ids->all());

        $this->info("Silinen sipariş: {$result['orders']}");
        foreach ($result['details'] as $key => $value) {
            $this->line("  {$key}: {$value}");
        }
        $this->info('Tamam. Admin/satıcı/alıcı sipariş listeleri boş veya güncel olmalı; 500 beklenmez.');

        return 0;
    }
}
