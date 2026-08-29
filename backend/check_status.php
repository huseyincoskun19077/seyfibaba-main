<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::where('order_id', '760730105')->first();
$order->load('orderProducts');

echo "Order: " . $order->order_id . "\n\n";
foreach ($order->orderProducts as $op) {
    echo "- Product ID: " . $op->product_id . ", Seller: " . $op->seller_id . ", seller_status: " . ($op->seller_status ?? 0) . "\n";
}