<?php

namespace App\Support;

class SellerLoginUrl
{
    public static function public(): string
    {
        $frontend = rtrim((string) config('app.frontend_url', 'https://kuafortedarik.com'), '/');

        return $frontend.'/satici-giris';
    }

    /** SMS metinleri için: kuafortedarik.com/satici-giris */
    public static function publicDisplay(): string
    {
        return preg_replace('#^https?://#', '', self::public()) ?? self::public();
    }
}
