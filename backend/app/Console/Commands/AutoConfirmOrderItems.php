<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Services\PayoutSettingsService;
use App\Services\SellerPayoutService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Log;

class AutoConfirmOrderItems extends Command
{
    protected $signature = 'order-items:auto-confirm';
    protected $description = 'Teslimden 15 gün sonra satır bazlı otomatik müşteri onayı (hazırlık)';

    public function handle(PayoutSettingsService $payoutSettings, SellerPayoutService $payoutService)
    {
        $windowDays = $payoutSettings->autoCompleteDays();
        $cutoff = Carbon::now()->subDays($windowDays);

        $items = OrderProduct::query()
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $cutoff)
            ->whereNull('customer_confirmed_at')
            ->whereNull('auto_confirmed_at')
            ->limit(500)
            ->get();

        $count = 0;
        $touchedOrderIds = [];
        foreach ($items as $item) {
            try {
                $item->auto_confirmed_at = now();
                if (! $item->payout_eligible_at) {
                    $item->payout_eligible_at = now()->addDays($payoutSettings->payoutHoldDays());
                }
                $item->save();

                $count++;
                $touchedOrderIds[(int) $item->order_id] = true;
            } catch (\Throwable $e) {
                Log::error('AutoConfirmOrderItems failed', [
                    'order_product_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Sipariş seviyesinde tamamla: bu sayede payout cron (orders:process-payouts) çalışabilir.
        if (!empty($touchedOrderIds)) {
            $commissionService = app(\App\Services\CommissionService::class);

            foreach (array_keys($touchedOrderIds) as $oid) {
                try {
                    $order = Order::query()->find($oid);
                    if (! $order) continue;

                    $hasAnyDelivered = OrderProduct::query()
                        ->where('order_id', $oid)
                        ->whereNotNull('delivered_at')
                        ->exists();

                    if (! $hasAnyDelivered) continue;

                    // Teslim edilmiş satırlardan henüz onay/auto-confirm almayan var mı?
                    $hasUnconfirmedDelivered = OrderProduct::query()
                        ->where('order_id', $oid)
                        ->whereNotNull('delivered_at')
                        ->whereNull('customer_confirmed_at')
                        ->whereNull('auto_confirmed_at')
                        ->exists();

                    if ($hasUnconfirmedDelivered) continue;

                    if ((int) $order->order_status < 3) {
                        $order->order_status = 3;
                        $order->order_completed_date = date('Y-m-d');
                        $order->auto_complete_date = $order->auto_complete_date ?: now();
                        $order->save();

                        $commissionService->settleCommissions($order);
                        $payoutService->schedulePayoutEligibility($order);
                    }
                } catch (\Throwable $e) {
                    Log::error('AutoConfirmOrderItems order finalize failed', [
                        'order_id' => $oid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Auto-confirm edilen satır sayısı: {$count}");
        return 0;
    }
}

