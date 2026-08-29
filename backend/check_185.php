<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::find(185);
if (!$order) {
    echo "Order not found\n";
    exit;
}

$order->load('orderProducts');
echo "Order ID: " . $order->order_id . "\n";
echo "order_status: " . $order->order_status . "\n\n";

echo "orderProducts:\n";
foreach ($order->orderProducts as $op) {
    echo "- Product: " . $op->product_name . "\n";
    echo "  Seller ID: " . $op->seller_id . "\n";
    echo "  seller_status: " . ($op->seller_status ?? 0) . "\n";
}