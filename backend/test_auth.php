<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate exactly what the controller does
// Get the user that logged in
$user = App\Models\User::where('email', 'devhcsoftware@gmail.com')->first();
echo "User: " . $user->email . "\n";

// Get seller from user (like Auth::guard('web')->user()->seller)
$seller = $user->seller;
if ($seller) {
    echo "Seller ID: " . $seller->id . "\n";
    echo "Shop Name: " . $seller->shop_name . "\n";
    
    // Now run the exact query
    $sellerId = $seller->id;
    echo "\nRunning query for seller_id = $sellerId:\n";
    
    $orders = App\Models\Order::with(['user', 'orderProducts'])
        ->whereHas('orderProducts', function($query) use ($sellerId) {
            $query->where(['seller_id' => $sellerId, 'seller_status' => 0]);
        })
        ->where('payment_status', 1)
        ->limit(5)
        ->get();
    
    echo "Found " . $orders->count() . " orders\n";
    foreach ($orders as $o) {
        echo "- " . $o->order_id . "\n";
    }
} else {
    echo "No seller relation found!\n";
}