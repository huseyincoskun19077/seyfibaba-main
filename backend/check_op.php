<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::where('order_id', '760730105')->first();
$order->load('orderProducts');

echo "Order: " . $order->order_id . "\n\n";
echo "order_products table:\n";
foreach ($order->orderProducts as $op) {
    echo "- Product ID: " . $op->product_id . ", Seller: " . $op->seller_id . ", Qty: " . ($op->qty ?? 'NULL') . "\n";
}

echo "\n\n--- Check view calculation ---\n";
echo "orderProducts->sum('qty'): " . $order->orderProducts->sum('qty') . "\n";

// This calculates correctly
$myQty = $order->orderProducts->where('seller_id', 15)->sum('qty');
echo "Seller 15 qty: " . $myQty . "\n";