<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Vendor 15:\n";
$v15 = App\Models\Vendor::find(15);
echo "- Email: " . $v15->email . "\n";
echo "- Shop: " . $v15->shop_name . "\n";
echo "- User ID: " . $v15->user_id . "\n";

$user15 = App\Models\User::find($v15->user_id);
echo "- Login email: " . $user15->email . "\n\n";

echo "Vendor 17:\n";
$v17 = App\Models\Vendor::find(17);
echo "- Email: " . $v17->email . "\n";
echo "- Shop: " . $v17->shop_name . "\n";
echo "- User ID: " . $v17->user_id . "\n";

$user17 = App\Models\User::find($v17->user_id);
echo "- Login email: " . $user17->email . "\n";