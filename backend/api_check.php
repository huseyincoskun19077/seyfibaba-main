<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = App\Models\Order::with('orderProducts')->where('order_id', '1155232926')->first();
echo json_encode([
    'order_id' => $order->order_id,
    'order_products' => $order->orderProducts->map(function($op) {
        return [
            'product_id' => $op->product_id,
            'product_name' => $op->product_name,
            'qty' => $op->qty,
            'unit_price' => $op->unit_price,
            'seller_id' => $op->seller_id,
            'seller_status' => $op->seller_status,
        ];
    })
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);