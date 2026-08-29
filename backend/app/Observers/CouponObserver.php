<?php

namespace App\Observers;

use App\Models\Coupon;
use App\Notifications\DiscountNotification;
use App\Services\PushBroadcastService;

class CouponObserver
{
    public function created(Coupon $coupon): void
    {
        if ((int) $coupon->status !== 1) {
            return;
        }

        $this->broadcast($coupon);
    }

    public function updated(Coupon $coupon): void
    {
        if (! $coupon->wasChanged('status') || (int) $coupon->status !== 1) {
            return;
        }

        $this->broadcast($coupon);
    }

    private function broadcast(Coupon $coupon): void
    {
        app(PushBroadcastService::class)->sendToAllBuyers(new DiscountNotification($coupon));
    }
}
