<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::orderBy('id', 'desc')->first();
echo "Order ID: " . $order->order_id . "\n";
$order->load('orderProducts');
echo "Order Products Count: " . $order->orderProducts->count() . "\n";
foreach ($order->orderProducts as $op) {
    echo "- Product ID: " . $op->product_id . ", Seller: " . $op->seller_id . ", Qty: " . ($op->qty ?? 1) . ", Status: " . ($op->seller_status ?? 0) . "\n";
}