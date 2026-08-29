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

];
