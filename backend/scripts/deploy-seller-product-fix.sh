#!/bin/bash
# Admin satıcı ürünleri + PHP 8.2 composer düzeltmesi
set -e

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
BACKEND="$ROOT/backend"
REF="${1:-origin/main}"

echo "==> Düzeltme dosyaları çekiliyor ($REF)..."
cd "$ROOT"
git fetch origin main 2>/dev/null || true
git checkout "$REF" -- \
  backend/composer.json \
  backend/composer.lock \
  backend/resources/views/admin/seller_product.blade.php \
  backend/resources/views/admin/product_by_seller.blade.php \
  backend/app/Http/Controllers/WEB/Admin/ProductController.php \
  backend/app/Http/Controllers/WEB/Admin/SellerController.php \
  backend/app/Helpers/storefront.php

cd "$BACKEND"

echo "==> zipstream sürümü:"
grep -A1 '"name": "maennchen/zipstream-php"' composer.lock | head -3

rm -f bootstrap/cache/config.php bootstrap/cache/packages.php bootstrap/cache/services.php 2>/dev/null || true

echo "==> Composer install..."
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

echo "==> Cache temizleniyor..."
php artisan view:clear
php artisan config:clear

echo "==> Tamam. Sayfayı test et: /admin/seller-product"

