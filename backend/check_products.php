<?php
use App\Models\Product;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$keyword = 'koltuk';
$products = Product::where('name', 'LIKE', "%$keyword%")
    ->orWhere('long_description', 'LIKE', "%$keyword%")
    ->get(['id', 'name', 'status', 'approve_by_admin']);

echo "Products matching '$keyword':\n";
foreach($products as $p) {
    echo "ID: {$p->id}, Name: {$p->name}, Status: {$p->status}, Approved: {$p->approve_by_admin}\n";
}

if($products->isEmpty()) {
    echo "No products found for '$keyword' searching directly in DB.\n";
}
