#!/bin/bash
# Sunucuda PHP 8.2 + composer platform_check 8.3 hatasini duzeltir.
# Kullanim: bash backend/scripts/fix-php82-composer.sh
set -e

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
BACKEND="$ROOT/backend"

echo "==> Proje: $ROOT"
cd "$ROOT"

echo "==> composer.json / composer.lock guncelleniyor..."
if git rev-parse --git-dir >/dev/null 2>&1; then
  git fetch origin main 2>/dev/null || true
  git checkout origin/main -- backend/composer.json backend/composer.lock
else
  echo "Git yok, GitHub'dan indiriliyor..."
  curl -fsSL -o "$BACKEND/composer.json" \
    "https://raw.githubusercontent.com/huseyincoskun19077/seyfibaba/main/backend/composer.json"
  curl -fsSL -o "$BACKEND/composer.lock" \
    "https://raw.githubusercontent.com/huseyincoskun19077/seyfibaba/main/backend/composer.lock"
fi

cd "$BACKEND"

echo "==> Lock dosyasi zipstream surumu:"
grep -A1 '"name": "maennchen/zipstream-php"' composer.lock | head -3

# platform_check.php siteyi kilitliyorsa gecici olarak devre disi birak
if [ -f vendor/composer/platform_check.php ]; then
  echo '<?php // gecici: composer fix sirasinda devre disi' > vendor/composer/platform_check.php
fi

echo "==> Composer install..."
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

echo "==> Cache temizleniyor..."
php artisan config:clear || true
php artisan view:clear || true

echo "==> Tamam."
grep -A1 '"name": "maennchen/zipstream-php"' composer.lock | head -3
php -v
