<?php

namespace App\Support;

class GuestCheckout
{
    public static function enabled(): bool
    {
        return (bool) config('marketplace.guest_checkout_enabled', false);
    }

    public static function disabledResponse()
    {
        return response()->json([
            'status' => 0,
            'message' => 'Misafir sipariş geçici olarak devre dışıdır. Lütfen giriş yapın veya üye olun.',
        ], 403);
    }
}
