<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Vendor 13 (hc yazilim / huseyon coskun / devhcsoftware1@gmail.com) silinir.
 * - Ürün (id:81) vendor 15'e (hc yazilim) taşınır
 * - Vendor 15 shop_name "hc yazilim1" → "hc yazilim", slug düzeltilir
 * - Vendor 13 ve user 128'e ait tüm izler temizlenir
 */
class CleanVendor13Seeder extends Seeder
{
    public function run(): void
    {
        // 1. Ürün 81'i vendor 15'e taşı
        DB::table('products')->where('id', 81)->update(['vendor_id' => 15]);
        $this->command->info('Ürün 81 → vendor 15 taşındı.');

        // 2. Vendor 15 shop_name ve slug düzelt
        DB::table('vendors')->where('id', 15)->update([
            'shop_name' => 'hc yazilim',
            'slug'      => 'hc-yazilim',
        ]);
        $this->command->info('Vendor 15 shop_name → "hc yazilim" düzeltildi.');

        // 3. Wishlists (user 128)
        DB::table('wishlists')->where('user_id', 128)->delete();

        // 4. Product reviews (user 128)
        DB::table('product_reviews')->where('user_id', 128)->delete();

        // 5. Compare products (user 128)
        DB::table('compare_products')->where('user_id', 128)->delete();

        // 6. Product galleries (ürün 81 - artık vendor 15'te, eski galeriler tutulacak)
        // Galeriler ürünle taşındı, silinmeyecek

        // 7. Addresses (user 128)
        DB::table('addresses')->where('user_id', 128)->delete();

        // 8. Notifications (user 128)
        DB::table('notifications')->where('notifiable_id', 128)->delete();

        // 9. KYC documents (seller_id 13)
        DB::table('seller_kyc_documents')->where('seller_id', 13)->delete();

        // 10. Vendor social links (vendor 13)
        DB::table('vendor_social_links')->where('vendor_id', 13)->delete();

        // 10b. Commission ledger (seller_id 13)
        DB::table('commission_ledger')->where('seller_id', 13)->delete();

        // 11. Vendor 13'ü sil
        DB::table('vendors')->where('id', 13)->delete();
        $this->command->info('Vendor 13 silindi.');

        // 12. User 128'i sil
        DB::table('users')->where('id', 128)->delete();
        $this->command->info('User 128 (devhcsoftware1@gmail.com) silindi.');

        $this->command->info('✓ Tüm izler temizlendi.');
    }
}
