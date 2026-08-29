<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Silinen siparişler:\n";

$testOrders = ['1126112246', '1155232926', '408836751', '226583606', '900898747'];

foreach ($testOrders as $orderId) {
    $order = App\Models\Order::where('order_id', $orderId)->first();
    if ($order) {
        // Önce commission_ledger'ı sil
        $orderProducts = App\Models\OrderProduct::where('order_id', $order->id)->get();
        foreach ($orderProducts as $op) {
            App\Models\CommissionLedger::where('order_product_id', $op->id)->delete();
        }
        // Sonra order_products
        App\Models\OrderProduct::where('order_id', $order->id)->delete();
        // Order address
        App\Models\OrderAddress::where('order_id', $order->id)->delete();
        // Order
        $order->delete();
        echo "- $orderId (silindi)\n";
    }
}

echo "\nTüm test siparişleri silindi!\n";