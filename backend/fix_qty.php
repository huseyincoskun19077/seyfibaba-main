<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::where('order_id', '1155232926')->first();
if (!$order) {
    echo "Order not found\n";
    exit;
}

echo "Fixing Order ID: " . $order->order_id . "\n";
$order->load('orderProducts');

$fixed = 0;
foreach ($order->orderProducts as $op) {
    $currentQty = $op->qty ?? 1;
    // Eğer qty ürün sayısından fazlaysa düzelt
    if ($currentQty > 2) {
        $op->qty = 1;
        $op->save();
        echo "Fixed Product ID " . $op->product_id . ": " . $currentQty . " -> 1\n";
        $fixed++;
    } else {
        echo "Product ID " . $op->product_id . ": qty=" . $currentQty . " (OK)\n";
    }
}

if ($fixed > 0) {
    echo "\nFixed $fixed items!\n";
} else {
    echo "\nNo fix needed - all quantities are 1 or 2\n";
}