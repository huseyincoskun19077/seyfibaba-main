<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::with('orderProducts')->where('order_id', '760730105')->first();
if (!$order) {
    echo "Order not found\n";
    exit;
}

echo "Order: " . $order->order_id . "\n";
echo "Payment Status: " . $order->payment_status . "\n";
echo "Order Status: " . $order->order_status . "\n\n";

echo "Products:\n";
foreach ($order->orderProducts as $op) {
    echo "- Product ID: " . $op->product_id . ", Seller ID: " . $op->seller_id . ", Qty: " . ($op->qty ?? 1) . ", Status: " . ($op->seller_status ?? 0) . "\n";
}

// Check if seller matches
echo "\n\nSellers in order: " . implode(', ', $order->orderProducts->pluck('seller_id')->unique()->toArray()) . "\n";