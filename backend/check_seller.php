<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "All Sellers/Vendors:\n";
$sellers = App\Models\Vendor::all(['id', 'name']);
foreach ($sellers as $s) {
    echo "- ID: " . $s->id . ", Name: " . $s->name . "\n";
}

echo "\n\nOrder 1126112246 has products with seller_id: 15 and 17\n";
echo "If seller is logged in, check their vendor_id:\n";

// Simulate checking
$v15 = App\Models\Vendor::find(15);
$v17 = App\Models\Vendor::find(17);
echo "Vendor 15: " . ($v15 ? $v15->name : 'NOT FOUND') . "\n";
echo "Vendor 17: " . ($v17 ? $v17->name : 'NOT FOUND') . "\n";