<?php

namespace Tests\Unit\Support;

use App\Support\ProductImageUrl;
use Tests\TestCase;

class ProductImageUrlTest extends TestCase
{
    public function test_normalize_for_storage_accepts_cdn_url(): void
    {
        $url = 'https://cdn.dsmcdn.com/ty1037/product/media/images/prod/test/1_org_zoom.jpg';

        $this->assertSame($url, ProductImageUrl::normalizeForStorage($url));
    }

    public function test_is_external_detects_https_urls(): void
    {
        $this->assertTrue(ProductImageUrl::isExternal('https://cdn.dsmcdn.com/image.jpg'));
        $this->assertFalse(ProductImageUrl::isExternal('uploads/custom-images/test.jpg'));
    }

    public function test_has_image_true_for_external_url(): void
    {
        $this->assertTrue(ProductImageUrl::hasImage('https://cdn.example.com/a.jpg'));
    }
}
