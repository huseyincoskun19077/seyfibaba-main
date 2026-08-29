<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\CommissionService;
use App\Services\PayoutSettingsService;
use App\Services\SellerPayoutService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCompleteOrders extends Command
{
    protected $signature = 'orders:auto-complete';

    protected $description = 'Teslimden sonra müşteri onayı gelmezse siparişi otomatik tamamlar';

    public function handle(
        PayoutSettingsService $payoutSettings,
        CommissionService $commissionService,
        SellerPayoutService $payoutService,
    ): int {
        $days = $payoutSettings->autoCompleteDays();
        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();

        $orders = Order::query()
            ->where('order_status', 2)
            ->where('order_delivered_date', '<=', $cutoffDate->toDateString())
            ->whereNull('customer_confirmed_at')
            ->whereNull('auto_complete_date')
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            try {
                $order->order_status = 3;
                $order->order_completed_date = date('Y-m-d');
                $order->auto_complete_date = now();
                $order->save();

                $commissionService->settleCommissions($order);
                $payoutService->schedulePayoutEligibility($order);

                $count++;
                Log::info('Otomatik tamamlandı', ['order_id' => $order->id, 'days' => $days]);
            } catch (\Throwable $e) {
                Log::error('Otomatik tamamlama hatası', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("{$count} sipariş otomatik tamamlandı ({$days} gün).");

        return self::SUCCESS;
    }
}
