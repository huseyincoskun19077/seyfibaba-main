<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::with('orderProducts')->where('order_id', '1126112246')->first();
if (!$order) {
    echo "Order not found\n";
    exit;
}

echo "Order ID: " . $order->order_id . "\n";
echo "Payment Status: " . $order->payment_status . "\n";
echo "Order Status: " . $order->order_status . "\n";
echo "Total Price: " . $order->total_price . "\n\n";

echo "Order Products:\n";
foreach ($order->orderProducts as $op) {
    echo "- Product ID: " . $op->product_id . "\n";
    echo "  Product Name: " . $op->product_name . "\n";
    echo "  Seller ID: " . $op->seller_id . "\n";
    echo "  Qty: " . ($op->qty ?? 1) . "\n";
    echo "  Unit Price: " . ($op->unit_price ?? 0) . "\n";
    echo "  Seller Status: " . ($op->seller_status ?? 0) . "\n\n";
}

// Check sellers
$sellerIds = $order->orderProducts->pluck('seller_id')->unique();
echo "Sellers in this order: " . implode(', ', $sellerIds->toArray()) . "\n";

foreach ($sellerIds as $sellerId) {
    $vendor = App\Models\Vendor::find($sellerId);
    echo "- Seller ID $sellerId: " . ($vendor ? $vendor->name : 'NOT FOUND') . "\n";
}