<?php

namespace App\Console\Commands;

use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShoppingCart;
use App\Models\UserProductView;
use App\Notifications\ProductViewReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendProductViewReminders extends Command
{
    protected $signature = 'products:view-remind';

    protected $description = 'Ürünü birkaç kez inceleyen alıcılara hatırlatma bildirimi gönderir';

    public function handle(): int
    {
        $setting = Setting::query()->first();
        $minViews = max(1, (int) ($setting->product_view_reminder_count ?? 3));
        $cooldownDays = max(1, (int) ($setting->product_view_reminder_cooldown_days ?? 7));
        $sent = 0;

        UserProductView::query()
            ->with(['user', 'product'])
            ->where('view_count', '>=', $minViews)
            ->where(function ($query) use ($cooldownDays) {
                $query->whereNull('reminded_at')
                    ->orWhere('reminded_at', '<', now()->subDays($cooldownDays));
            })
            ->orderBy('id')
            ->chunkById(100, function ($views) use (&$sent) {
                foreach ($views as $view) {
                    $user = $view->user;
                    $product = $view->product;

                    if (! $user || ! $product || (int) $product->status !== 1 || (int) $product->qty <= 0) {
                        continue;
                    }

                    if ($this->userPurchasedProduct($user->id, $product->id)) {
                        continue;
                    }

                    if ($this->productInCart($user->id, $product->id)) {
                        continue;
                    }

                    $user->notify(new ProductViewReminderNotification($product));
                    $view->forceFill(['reminded_at' => now()])->saveQuietly();
                    $sent++;
                }
            });

        $this->info("Ürün bakış hatırlatması gönderildi: {$sent}");

        return self::SUCCESS;
    }

    private function userPurchasedProduct(int $userId, int $productId): bool
    {
        return OrderProduct::query()
            ->where('product_id', $productId)
            ->whereIn('order_id', function ($query) use ($userId) {
                $query->select('id')
                    ->from('orders')
                    ->where('user_id', $userId);
            })
            ->exists();
    }

    private function productInCart(int $userId, int $productId): bool
    {
        return ShoppingCart::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }
}
