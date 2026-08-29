<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate pendingOrder for seller_id = 15
$sellerId = 15;
echo "=== pendingOrder for Seller $sellerId ===\n";
$orders = App\Models\Order::with(['user', 'orderProducts'])
    ->whereHas('orderProducts', function ($query) use ($sellerId) {
        $query->where(['seller_id' => $sellerId, 'seller_status' => 0]);
    })
    ->where('payment_status', 1)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

echo "Found: " . $orders->count() . " orders\n";
foreach ($orders as $o) {
    echo "- Order " . $o->order_id . "\n";
}

// Simulate pendingOrder for seller_id = 17
$sellerId = 17;
echo "\n=== pendingOrder for Seller $sellerId ===\n";
$orders = App\Models\Order::with(['user', 'orderProducts'])
    ->whereHas('orderProducts', function ($query) use ($sellerId) {
        $query->where(['seller_id' => $sellerId, 'seller_status' => 0]);
    })
    ->where('payment_status', 1)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

echo "Found: " . $orders->count() . " orders\n";
foreach ($orders as $o) {
    echo "- Order " . $o->order_id . "\n";
}