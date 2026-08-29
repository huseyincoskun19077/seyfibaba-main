<?php

namespace App\Support;

use App\Models\Product;

class ProductSellerPublishStatus
{
    /**
     * @return list<string>
     */
    public function issues(Product $product): array
    {
        $issues = [];

        if (! $this->hasThumbImage($product)) {
            $issues[] = 'Görsel eksik';
        }

        if (! (int) $product->category_id) {
            $issues[] = 'Kategori eksik';
        }

        if (trim((string) $product->name) === '') {
            $issues[] = 'Ürün adı eksik';
        }

        if ((float) $product->price <= 0) {
            $issues[] = 'Fiyat eksik';
        }

        if ((int) $product->qty < 1) {
            $issues[] = 'Stok eksik';
        }

        return $issues;
    }

    public function isBlockedByAdmin(Product $product): bool
    {
        if ((int) $product->approve_by_admin !== 0) {
            return false;
        }

        return $this->hasThumbImage($product) && $this->issues($product) === [];
    }

    public function isIncompleteDraft(Product $product): bool
    {
        return $this->issues($product) !== [];
    }

    public function canSellerPublish(Product $product): bool
    {
        return ! $this->isBlockedByAdmin($product) && $this->issues($product) === [];
    }

    public function primaryIssueLabel(Product $product): ?string
    {
        $issues = $this->issues($product);

        return $issues[0] ?? null;
    }

    private function hasThumbImage(Product $product): bool
    {
        $image = trim((string) $product->thumb_image);

        return $image !== '';
    }
}
