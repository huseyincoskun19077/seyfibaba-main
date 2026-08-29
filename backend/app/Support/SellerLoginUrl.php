<?php

namespace App\Support;

class SellerLoginUrl
{
    public static function public(): string
    {
        $frontend = rtrim((string) config('app.frontend_url', 'https://seyfibaba.com'), '/');

        return $frontend.'/satici-giris';
    }

    /** SMS metinleri için: seyfibaba.com/satici-giris */
    public static function publicDisplay(): string
    {
        return preg_replace('#^https?://#', '', self::public()) ?? self::public();
    }
}
