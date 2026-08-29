<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Setting;
use App\Services\PayoutSettingsService;
use App\Services\SellerPayoutService;
use Illuminate\Console\Command;

class TestSellerPayoutFlow extends Command
{
    protected $signature = 'payout:test-flow
                            {--order-id= : Veritabanı order.id}
                            {--dry-run : İyzico API çağrısı yapmadan simüle et}
                            {--force : Bekleme süresini atla}';

    protected $description = 'Satıcı hakediş akışını uçtan uca test eder (simülasyon + durum raporu)';

    public function handle(PayoutSettingsService $settings, SellerPayoutService $payoutService): int
    {
        $this->info('=== Hakediş Ayarları ===');
        foreach ($settings->all() as $key => $value) {
            $this->line("  {$key}: ".(is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }

        $orderId = $this->option('order-id');
        if (! $orderId) {
            $this->warn('Sipariş ID verilmedi. Örnek: php artisan payout:test-flow --order-id=123 --dry-run');

            return self::SUCCESS;
        }

        $order = Order::with('orderProducts')->find($orderId);
        if (! $order) {
            $this->error("Sipariş bulunamadı: {$orderId}");

            return self::FAILURE;
        }

        $this->info('');
        $this->info("=== Sipariş #{$order->order_id} (id={$order->id}) ===");
        $this->line('  payment_method: '.$order->payment_method);
        $this->line('  order_status: '.$order->order_status);
        $this->line('  payout_status: '.($order->payout_status ?? 'null'));
        $this->line('  payout_eligible_at: '.($order->payout_eligible_at ?? 'null'));
        $this->line('  payout_blocked_at: '.($order->payout_blocked_at ?? 'null'));
        $this->line('  customer_confirmed_at: '.($order->customer_confirmed_at ?? 'null'));
        $this->line('  auto_complete_date: '.($order->auto_complete_date ?? 'null'));

        $payoutService->syncPaymentTransactionIds($order);
        $order->refresh()->load('orderProducts');

        $this->info('');
        $this->info('=== Sipariş Satırları ===');
        foreach ($order->orderProducts as $op) {
            $this->line("  op#{$op->id} product={$op->product_id} txn=".($op->iyzico_payment_transaction_id ?? 'yok').' approved='.($op->iyzico_approved_at ?? 'hayır'));
        }

        if ($this->option('dry-run')) {
            Setting::query()->first()?->update(['iyzico_payout_dry_run' => true]);
            $this->warn('Dry-run modu: İyzico API çağrısı simüle edilecek.');
        }

        $force = (bool) $this->option('force');
        $result = $payoutService->processOrderPayout($order, $force);

        $this->info('');
        $this->info('=== Sonuç ===');
        $this->line('  success: '.($result['success'] ? 'evet' : 'hayır'));
        $this->line('  message: '.$result['message']);

        foreach ($result['results'] ?? [] as $row) {
            $this->line('  - '.json_encode($row, JSON_UNESCAPED_UNICODE));
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
