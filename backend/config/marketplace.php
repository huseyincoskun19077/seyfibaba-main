<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Misafir (guest) checkout
    |--------------------------------------------------------------------------
    |
    | Güvenlik: misafir sipariş ve sipariş sorgulama kapalı. Yalnızca giriş
    | yapmış kullanıcılar sipariş verebilir ve siparişlerini görebilir.
    |
    */
    'guest_checkout_enabled' => env('GUEST_CHECKOUT_ENABLED', false),

];
