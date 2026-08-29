<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class CommissionLedgerCleanup
{
    /**
     * Remove ledger rows tied to unpaid/missing orders or legacy backfill noise.
     */
    public static function purgeInvalidRows(): int
    {
        $invalidIds = DB::table('commission_ledger as cl')
            ->leftJoin('orders as o', 'o.id', '=', 'cl.order_id')
            ->where(function ($query) {
                $query->whereNull('o.id')
                    ->orWhere('o.payment_status', '!=', 1);
            })
            ->pluck('cl.id');

        if ($invalidIds->isEmpty()) {
            return 0;
        }

        return DB::table('commission_ledger')->whereIn('id', $invalidIds)->delete();
    }
}
