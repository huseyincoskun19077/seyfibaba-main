<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate seller 15 login
$user = App\Models\User::where('email', 'devhcsoftware@gmail.com')->first();
$seller = $user->seller;
$sellerId = $seller->id;

echo "Seller: " . $seller->shop_name . " (ID: $sellerId)\n\n";

echo "=== All Orders for this seller ===\n";
$orders = App\Models\Order::with(['user', 'orderProducts'])
    ->whereHas('orderProducts', function($query) use ($sellerId) {
        $query->where('seller_id', $sellerId);
    })
    ->where('payment_status', 1)
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo "Found: " . $orders->count() . " orders\n\n";

foreach ($orders as $o) {
    $myProducts = $o->orderProducts->where('seller_id', $sellerId);
    $qty = $myProducts->sum('qty');
    echo "- Order: " . $o->order_id . ", Qty: " . $qty . ", Total: " . $o->orderProducts->sum(function($op) { return ($op->qty ?? 1) * ($op->unit_price ?? 0); }) . " TL\n";
}