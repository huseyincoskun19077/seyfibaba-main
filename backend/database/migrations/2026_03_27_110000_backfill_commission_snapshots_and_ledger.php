<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('order_products') || !DB::getSchemaBuilder()->hasTable('orders') || !DB::getSchemaBuilder()->hasTable('commission_ledger')) {
            return;
        }

        DB::table('order_products')
            ->where(function ($query) {
                $query->whereNull('seller_net_amount')
                    ->orWhere('seller_net_amount', 0);
            })
            ->update([
                'commission_rate' => DB::raw('COALESCE(commission_rate, 0)'),
                'commission_amount' => DB::raw('COALESCE(commission_amount, 0)'),
                'seller_net_amount' => DB::raw('(unit_price * qty) - COALESCE(commission_amount, 0)'),
            ]);

        $rows = DB::table('order_products as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->leftJoin('commission_ledger as cl', 'cl.order_product_id', '=', 'op.id')
            ->join('vendors as v', 'v.id', '=', 'op.seller_id')
            ->whereNull('cl.id')
            ->where('op.seller_id', '>', 0)
            ->selectRaw('
                op.order_id as order_id,
                op.id as order_product_id,
                op.seller_id as seller_id,
                (op.unit_price * op.qty) as gross_amount,
                COALESCE(op.commission_rate, 0) as commission_rate,
                COALESCE(op.commission_amount, 0) as commission_amount,
                CASE
                    WHEN COALESCE(op.seller_net_amount, 0) = 0 THEN (op.unit_price * op.qty) - COALESCE(op.commission_amount, 0)
                    ELSE op.seller_net_amount
                END as seller_net_amount,
                CASE
                    WHEN o.order_status = 3 THEN "settled"
                    ELSE "pending"
                END as status,
                CASE
                    WHEN o.order_status = 3 THEN COALESCE(o.order_completed_date, o.updated_at, NOW())
                    ELSE NULL
                END as settled_at,
                "Legacy backfill" as notes,
                NOW() as created_at,
                NOW() as updated_at
            ')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        if (!empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('commission_ledger')->insert($chunk);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('commission_ledger')) {
            return;
        }

        DB::table('commission_ledger')
            ->where('notes', 'Legacy backfill')
            ->delete();
    }
};
