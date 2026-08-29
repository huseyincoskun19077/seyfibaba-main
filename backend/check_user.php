<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate: user login with devhcsoftware@gmail.com
$user = App\Models\User::where('email', 'devhcsoftware@gmail.com')->first();
if ($user) {
    echo "User: " . $user->email . "\n";
    // Check if this user has seller relation
    $sellerRelation = $user->seller ?? null;
    echo "Seller relation: " . ($sellerRelation ? "Var - ID: " . $sellerRelation->id : "YOK") . "\n";
    
    // Or check via vendor table
    $vendor = App\Models\Vendor::where('user_id', $user->id)->first();
    echo "Vendor (via user_id): " . ($vendor ? "Var - ID: " . $vendor->id : "YOK") . "\n";
} else {
    echo "User not found\n";
}