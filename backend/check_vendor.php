<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo " vendors table columns:\n";
print_r(Schema::getColumnListing('vendors'));

echo "\n\nVendor 15:\n";
$v15 = App\Models\Vendor::find(15);
if ($v15) print_r($v15->toArray());
else echo "NOT FOUND\n";

echo "\n\nVendor 17:\n";
$v17 = App\Models\Vendor::find(17);
if ($v17) print_r($v17->toArray());
else echo "NOT FOUND\n";