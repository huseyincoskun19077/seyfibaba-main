<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$orders = DB::table('orders')->orderBy('id', 'desc')->limit(5)->get();
foreach ($orders as $o) {
    echo "ID: $o->id | OrderID: $o->order_id | Payment: $o->payment_method | Status: $o->payment_status | Total: $o->total_amount | Draft: $o->is_draft\n";
}