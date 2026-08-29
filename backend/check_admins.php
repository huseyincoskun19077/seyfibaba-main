<?php
use App\Models\Admin;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admins = Admin::all(['id', 'name', 'email', 'status', 'admin_type']);
echo "Admins in DB:\n";
foreach($admins as $a) {
    echo "ID: {$a->id}, Name: {$a->name}, Email: {$a->email}, Status: {$a->status}, Type: {$a->admin_type}\n";
}
