<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test exact query from pendingOrder with seller_id = 15
$sellerId = 15;
$query = App\Models\Order::with(['user', 'orderProducts'])
    ->whereHas('orderProducts', function($query) use ($sellerId) {
        $query->where(['seller_id' => $sellerId, 'seller_status' => 0]);
    })
    ->where('payment_status', 1);

$count = $query->count();
echo "Total orders for seller 15: $count\n";

// Get SQL to debug
echo "\nSQL: " . $query->toSql();
echo "\n\nFirst 3 orders:\n";
foreach ($query->limit(3)->get() as $o) {
    echo "- " . $o->order_id . "\n";
}