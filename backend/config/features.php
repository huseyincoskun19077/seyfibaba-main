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

    /*
    |--------------------------------------------------------------------------
    | Softtr satıcı entegrasyonu (opt-in per vendor)
    |--------------------------------------------------------------------------
    | Pull-only product catalog from seller Softtr shop API (Basic Auth).
    | Mutually exclusive with Sentos via VendorCatalogIntegration.
    */
    'softtr_enabled' => env('FEATURE_SOFTTR', true),

    /*
    | Softtr /products/list often omits categories — fallback into Seyfibaba tree.
    | Default: Kozmetik (3) → sub "Tırnak Malzemeleri" (matched by name under that category).
    */
    'softtr_default_category_id' => (int) env('SOFTTR_DEFAULT_CATEGORY_ID', 3),
    'softtr_default_sub_category_name' => env('SOFTTR_DEFAULT_SUB_CATEGORY_NAME', 'Tırnak Malzemeleri'),

];
