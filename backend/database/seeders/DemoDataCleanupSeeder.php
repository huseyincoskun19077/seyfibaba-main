<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Support\CommissionLedgerCleanup;

class DemoDataCleanupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $testReturnIds = DB::table('return_requests')
                ->where('details', 'E2E validation flow')
                ->pluck('id');

            if ($testReturnIds->isNotEmpty()) {
                DB::table('return_request_images')
                    ->whereIn('return_request_id', $testReturnIds)
                    ->delete();

                DB::table('commission_ledger')
                    ->whereIn('notes', $testReturnIds->map(fn ($id) => "Return Refund for Request #{$id}")->all())
                    ->delete();

                DB::table('return_requests')
                    ->whereIn('id', $testReturnIds)
                    ->delete();
            }

            $demoVendorIds = DB::table('vendors')
                ->where(function ($query) {
                    $query->whereIn('shop_name', ['api test shop name', 'check'])
                        ->orWhereIn('email', ['shop@gmail.com', 'check@chec.com']);
                })
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('products')
                        ->whereColumn('products.vendor_id', 'vendors.id');
                })
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('order_products')
                        ->whereColumn('order_products.seller_id', 'vendors.id');
                })
                ->pluck('id');

            if ($demoVendorIds->isNotEmpty()) {
                DB::table('vendor_social_links')->whereIn('vendor_id', $demoVendorIds)->delete();
                DB::table('vendors')->whereIn('id', $demoVendorIds)->delete();
            }

            $removedLedgerRows = CommissionLedgerCleanup::purgeInvalidRows();
            if ($removedLedgerRows > 0 && $this->command) {
                $this->command->info("Temizlenen geçersiz komisyon kaydı: {$removedLedgerRows}");
            }
        });
    }
}
