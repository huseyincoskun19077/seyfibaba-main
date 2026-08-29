<?php

namespace Tests\Unit\Support;

use App\Models\Product;
use App\Support\ProductSellerPublishStatus;
use PHPUnit\Framework\TestCase;

class ProductSellerPublishStatusTest extends TestCase
{
    public function test_draft_without_image_shows_gorsel_eksik_not_admin_block(): void
    {
        $product = new Product([
            'thumb_image' => '',
            'category_id' => 1,
            'name' => 'Kuaför Tarama Koltuğu',
            'price' => 4500,
            'qty' => 5,
            'approve_by_admin' => 0,
            'status' => 0,
        ]);

        $status = new ProductSellerPublishStatus();

        $this->assertSame(['Görsel eksik'], $status->issues($product));
        $this->assertFalse($status->isBlockedByAdmin($product));
        $this->assertTrue($status->isIncompleteDraft($product));
    }

    public function test_complete_product_with_admin_flag_is_admin_blocked(): void
    {
        $product = new Product([
            'thumb_image' => 'uploads/custom-images/koltuk.jpg',
            'category_id' => 1,
            'name' => 'Berber Koltuğu',
            'price' => 12500,
            'qty' => 3,
            'approve_by_admin' => 0,
            'status' => 0,
        ]);

        $status = new ProductSellerPublishStatus();

        $this->assertSame([], $status->issues($product));
        $this->assertTrue($status->isBlockedByAdmin($product));
    }

    public function test_missing_category_is_reported(): void
    {
        $product = new Product([
            'thumb_image' => 'uploads/custom-images/koltuk.jpg',
            'category_id' => 0,
            'name' => 'Berber Makası',
            'price' => 890,
            'qty' => 10,
            'approve_by_admin' => 1,
            'status' => 0,
        ]);

        $status = new ProductSellerPublishStatus();

        $this->assertContains('Kategori eksik', $status->issues($product));
    }
}
