<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Vendor;
use App\Notifications\SellerNewOrderNotification;
use Illuminate\Support\Facades\Cache;

class SellerPushNotifier
{
    public function notifyNewOrderForSeller(Order $order, int $sellerId): void
    {
        if ($sellerId <= 0) {
            return;
        }

        if (($order->is_draft ?? 'no') === 'yes') {
            return;
        }

        $cacheKey = "seller_new_order_push:{$order->id}:{$sellerId}";
        if (! Cache::add($cacheKey, 1, now()->addDays(2))) {
            return;
        }

        $vendor = Vendor::with('user')->find($sellerId);
        if (! $vendor || ! $vendor->user) {
            return;
        }

        $vendor->user->notify(new SellerNewOrderNotification($order, $vendor));
    }

    public function notifySellersForOrder(Order $order): void
    {
        if (($order->is_draft ?? 'no') === 'yes') {
            return;
        }

        $sellerIds = $order->orderProducts()
            ->whereNotNull('seller_id')
            ->pluck('seller_id')
            ->unique()
            ->filter(fn ($id) => (int) $id > 0);

        foreach ($sellerIds as $sellerId) {
            $this->notifyNewOrderForSeller($order, (int) $sellerId);
        }
    }
}
