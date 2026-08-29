<?php

namespace App\Console\Commands;

use App\Models\CargoShipment;
use App\Models\CommissionLedger;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderProduct;
use App\Models\OrderProductVariant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupDraftOrders extends Command
{
    protected $signature = 'orders:cleanup-drafts {--minutes=180 : Draft siparişlerin saklama süresi (dakika)} {--dry-run : Silmeden sadece kaç kayıt etkilenecek göster}';
    protected $description = 'Ödenmemiş taslak (draft) siparişleri belirli süre sonra temizler';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        if ($minutes < 10) {
            $minutes = 10;
        }

        $cutoff = Carbon::now()->subMinutes($minutes);
        $dryRun = (bool) $this->option('dry-run');

        $query = Order::query()
            ->where('is_draft', 'yes')
            ->where('payment_method', 'draft')
            ->where('order_status', 0)
            ->where('created_at', '<=', $cutoff)
            // Kargo/teslimat akışına bulaşmışsa asla silme
            ->whereDoesntHave('orderProducts', function ($q) {
                $q->whereNotNull('shipped_at')
                    ->orWhereNotNull('delivered_at')
                    ->orWhereNotNull('customer_confirmed_at')
                    ->orWhereNotNull('auto_confirmed_at');
            });

        $count = (clone $query)->count();
        $this->info("Cutoff: {$cutoff->toDateTimeString()} | Aday draft sipariş: {$count}");

        if ($dryRun || $count === 0) {
            return 0;
        }

        $deletedOrders = 0;
        $deletedProducts = 0;
        $deletedVariants = 0;
        $deletedAddresses = 0;
        $deletedLedgers = 0;
        $skippedDueToCargo = 0;

        $query->orderBy('id')->chunkById(200, function ($orders) use (
            &$deletedOrders,
            &$deletedProducts,
            &$deletedVariants,
            &$deletedAddresses,
            &$deletedLedgers,
            &$skippedDueToCargo
        ) {
            foreach ($orders as $order) {
                try {
                    DB::transaction(function () use (
                        $order,
                        &$deletedOrders,
                        &$deletedProducts,
                        &$deletedVariants,
                        &$deletedAddresses,
                        &$deletedLedgers,
                        &$skippedDueToCargo
                    ) {
                        // Eğer bir şekilde kargo kaydı oluştuysa silme (güvenlik)
                        $hasCargo = CargoShipment::query()
                            ->where('order_id', (int) $order->id)
                            ->exists();
                        if ($hasCargo) {
                            $skippedDueToCargo++;
                            return;
                        }

                        $orderProductIds = OrderProduct::query()
                            ->where('order_id', (int) $order->id)
                            ->pluck('id')
                            ->all();

                        if (!empty($orderProductIds)) {
                            $deletedVariants += OrderProductVariant::query()
                                ->whereIn('order_product_id', $orderProductIds)
                                ->delete();

                            $deletedLedgers += CommissionLedger::query()
                                ->whereIn('order_product_id', $orderProductIds)
                                ->delete();
                        }

                        $deletedAddresses += OrderAddress::query()
                            ->where('order_id', (int) $order->id)
                            ->delete();

                        $deletedProducts += OrderProduct::query()
                            ->where('order_id', (int) $order->id)
                            ->delete();

                        // Ledger’da order_id üzerinden kalmış kayıt varsa temizle (garanti)
                        $deletedLedgers += CommissionLedger::query()
                            ->where('order_id', (int) $order->id)
                            ->delete();

                        $order->delete();
                        $deletedOrders++;
                    });
                } catch (\Throwable $e) {
                    Log::error('Draft order cleanup failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Silinen sipariş: {$deletedOrders}");
        $this->info("Silinen order_products: {$deletedProducts}");
        $this->info("Silinen order_product_variants: {$deletedVariants}");
        $this->info("Silinen order_addresses: {$deletedAddresses}");
        $this->info("Silinen commission_ledger: {$deletedLedgers}");
        if ($skippedDueToCargo > 0) {
            $this->info("Kargo kaydı olduğu için atlanan: {$skippedDueToCargo}");
        }

        return 0;
    }
}

