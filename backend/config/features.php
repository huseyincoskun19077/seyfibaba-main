<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kurye (deliveryman) modülü — şu an kapalı
    |--------------------------------------------------------------------------
    */
    'deliveryman_enabled' => env('FEATURE_DELIVERYMAN', false),

    /*
    |--------------------------------------------------------------------------
    | Geliver kargo entegrasyonu — şu an kapalı (satıcılar manuel kargo kullanır)
    |--------------------------------------------------------------------------
    */
    'geliver_enabled' => env('FEATURE_GELIVER', false),

    /*
    |--------------------------------------------------------------------------
    | Sentos satıcı entegrasyonu (opt-in per vendor)
    |--------------------------------------------------------------------------
    | Global kill-switch. Vendor credentials live in vendor_sentos_settings.
    | Default true so sellers can configure; set FEATURE_SENTOS=false to hide.
    */
    'sentos_enabled' => env('FEATURE_SENTOS', true),

];
