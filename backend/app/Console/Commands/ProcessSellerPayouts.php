<?php

namespace App\Console\Commands;

use App\Services\SellerPayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSellerPayouts extends Command
{
    protected $signature = 'orders:process-payouts {--force : Bekleme süresini atla}';

    protected $description = 'Tamamlanan İyzico siparişleri için satıcı ödemesini (Approval API) işler';

    public function handle(SellerPayoutService $payoutService): int
    {
        $force = (bool) $this->option('force');

        $orders = $payoutService->eligibleOrdersQuery($force)
            ->where(function ($query) {
                $query->whereRaw('LOWER(payment_method) = ?', ['iyzico'])
                    ->where('payment_status', 1);
            })
            ->limit(200)
            ->get();

        $this->info('İşlenecek İyzico sipariş sayısı: '.$orders->count());

        $successCount = 0;
        $failCount = 0;

        foreach ($orders as $order) {
            $result = $payoutService->processOrderPayout($order, $force);

            if ($result['success']) {
                $successCount++;
                $this->info("Order #{$order->order_id}: {$result['message']}");
            } else {
                $failCount++;
                $this->warn("Order #{$order->order_id}: {$result['message']}");
                Log::warning('Payout cron failed', [
                    'order_id' => $order->id,
                    'message' => $result['message'],
                    'results' => $result['results'] ?? [],
                ]);
            }
        }

        $this->info("Başarılı: {$successCount}, Başarısız: {$failCount}");

        return self::SUCCESS;
    }
}
