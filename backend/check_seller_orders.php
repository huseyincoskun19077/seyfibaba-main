<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== For Seller ID 15 (hc yazilim) ===\n";
$orders15 = App\Models\Order::with(['user', 'orderProducts'])
    ->whereHas('orderProducts', function ($q) {
        $q->where('seller_id', 15);
    })
    ->where('payment_status', 1)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($orders15 as $o) {
    $ops = $o->orderProducts->where('seller_id', 15);
    echo "- Order " . $o->order_id . ": " . $ops->count() . " products\n";
}

echo "\n=== For Seller ID 17 (Craft Berber) ===\n";
$orders17 = App\Models\Order::with(['user', 'orderProducts'])
    ->whereHas('orderProducts', function ($q) {
        $q->where('seller_id', 17);
    })
    ->where('payment_status', 1)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($orders17 as $o) {
    $ops = $o->orderProducts->where('seller_id', 17);
    echo "- Order " . $o->order_id . ": " . $ops->count() . " products\n";
}