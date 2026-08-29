<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'name'               => 'Hoş Geldin İndirimi',
                'code'               => 'HOSGELDIN10',
                'offer_type'         => 1, // yüzde
                'discount'           => 10,
                'max_quantity'       => 500,
                'min_purchase_price' => '100',
                'expired_date'       => '2026-12-31',
                'apply_qty'          => 0,
                'status'             => 1,
            ],
            [
                'name'               => 'Yaz Kampanyası',
                'code'               => 'YAZ2026',
                'offer_type'         => 1,
                'discount'           => 15,
                'max_quantity'       => 300,
                'min_purchase_price' => '200',
                'expired_date'       => '2026-09-01',
                'apply_qty'          => 0,
                'status'             => 1,
            ],
            [
                'name'               => '50 TL İndirim',
                'code'               => '50TLHEDIYE',
                'offer_type'         => 2, // sabit tutar
                'discount'           => 50,
                'max_quantity'       => 200,
                'min_purchase_price' => '300',
                'expired_date'       => '2026-06-30',
                'apply_qty'          => 0,
                'status'             => 1,
            ],
            [
                'name'               => 'İlk Sipariş',
                'code'               => 'ILKSIPARIS',
                'offer_type'         => 1,
                'discount'           => 20,
                'max_quantity'       => 1000,
                'min_purchase_price' => '150',
                'expired_date'       => '2026-12-31',
                'apply_qty'          => 0,
                'status'             => 1,
            ],
            [
                'name'               => 'Ramazan Bayramı',
                'code'               => 'BAYRAM25',
                'offer_type'         => 1,
                'discount'           => 25,
                'max_quantity'       => 100,
                'min_purchase_price' => '500',
                'expired_date'       => '2026-04-15',
                'apply_qty'          => 0,
                'status'             => 1,
            ],
            [
                'name'               => '100 TL Üzeri Kargo Bedava',
                'code'               => 'KARGOBEDAVA',
                'offer_type'         => 2,
                'discount'           => 30,
                'max_quantity'       => 500,
                'min_purchase_price' => '100',
                'expired_date'       => '2026-12-31',
                'apply_qty'          => 0,
                'status'             => 1,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}
