<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOrderProductQty extends Command
{
    protected $signature = 'orders:sync-product-qty';

    protected $description = 'orders.product_qty alanini order_products toplami ile esitler';

    public function handle(): int
    {
        $updated = DB::update('
            UPDATE orders o
            SET product_qty = (
                SELECT COALESCE(SUM(op.qty), 0)
                FROM order_products op
                WHERE op.order_id = o.id
            )
            WHERE EXISTS (
                SELECT 1 FROM order_products op2 WHERE op2.order_id = o.id
            )
        ');

        $this->info("Guncellenen siparis: {$updated}");

        return self::SUCCESS;
    }
}
