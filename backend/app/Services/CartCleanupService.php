<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CartCleanupService
{
    /**
     * Başarılı siparişten sonra kullanıcının sepetinden ilgili ürünleri kaldır.
     */
    public function clearPurchasedItemsForUser(int $userId, Collection|array $productIds): void
    {
        if ($userId <= 0) {
            return;
        }

        $ids = collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        try {
            $cartIds = ShoppingCart::query()
                ->where('user_id', $userId)
                ->whereIn('product_id', $ids)
                ->pluck('id');

            if ($cartIds->isEmpty()) {
                return;
            }

            ShoppingCartVariant::query()
                ->whereIn('shopping_cart_id', $cartIds)
                ->delete();

            ShoppingCart::query()
                ->whereIn('id', $cartIds)
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('Cart cleanup failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clearCartForOrder(Order $order): void
    {
        $order->loadMissing('orderProducts');

        $productIds = $order->orderProducts
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $this->clearPurchasedItemsForUser((int) ($order->user_id ?? 0), $productIds);
    }
}
