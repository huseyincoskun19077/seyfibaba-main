<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test pendingOrder for seller_id = 15 ===\n";
$orders = App\Models\Order::with(['user', 'orderProducts'])
    ->whereHas('orderProducts', function($query) {
        $query->where(['seller_id' => 15, 'seller_status' => 0]);
    })
    ->where('payment_status', 1)
    ->limit(5)
    ->get();
echo "Found: " . $orders->count() . "\n";
foreach ($orders as $o) echo "- " . $o->order_id . "\n";

echo "\n=== Test index for seller_id = 15 ===\n";
$orders = App\Models\Order::with(['user'])
    ->whereHas('orderProducts', function($query) {
        $query->where('seller_id', 15);
    })
    ->where('payment_status', 1)
    ->orderBy('id','desc')
    ->limit(5)
    ->get();
echo "Found: " . $orders->count() . "\n";
foreach ($orders as $o) echo "- " . $o->order_id . "\n";