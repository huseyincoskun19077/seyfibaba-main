#!/bin/bash
# Laravel backend acil kurtarma — nginx'e dokunmaz
set -e

ROOT="${ROOT:-/opt/seyfibaba-main}"
OLD="${OLD:-/opt/seyfibaba-main-old}"

echo "=== 1) .env kontrol ==="
if [ ! -f "$ROOT/backend/.env" ]; then
  echo "HATA: $ROOT/backend/.env YOK"
  if [ -f "$OLD/backend/.env" ]; then
    cp "$OLD/backend/.env" "$ROOT/backend/.env"
    echo "Eski klasorden .env geri yuklendi"
  else
    echo "Yedek .env de yok. Once .env dosyasini geri yukleyin."
    exit 1
  fi
else
  echo "OK: .env mevcut"
  grep -E "^APP_KEY=|^DB_DATABASE=|^DB_HOST=" "$ROOT/backend/.env" | sed 's/=.*/=***/'
fi

echo ""
echo "=== 2) Maintenance modu kapat ==="
cd "$ROOT/backend"
php artisan up 2>/dev/null || true
rm -f storage/framework/maintenance.php

echo ""
echo "=== 3) Cache temizle ==="
php artisan optimize:clear
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php

echo ""
echo "=== 4) Composer + izinler ==="
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache

echo ""
echo "=== 5) DB baglantisi ==="
php artisan tinker --execute="try { echo 'DB: '.DB::connection()->getDatabaseName().PHP_EOL; } catch (Throwable \$e) { echo 'DB FAIL: '.\$e->getMessage().PHP_EOL; exit(1); }"

echo ""
echo "=== 6) Cache yeniden olustur ==="
php artisan config:cache
php artisan route:cache

echo ""
echo "=== 7) API test (nginx uzerinden — dogru yol) ==="
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Host: admin.kuafortedarik.com" "http://127.0.0.1/api/website-setup")
echo "website-setup HTTP: $CODE"

if [ "$CODE" != "200" ]; then
  echo ""
  echo "=== HATA: Son Laravel log ==="
  tail -40 storage/logs/laravel.log 2>/dev/null || echo "laravel.log okunamadi"
  echo ""
  echo "=== PHP-FPM log (son 10) ==="
  tail -10 /var/log/php8.3-fpm.log 2>/dev/null || tail -10 /var/log/php-fpm.log 2>/dev/null || true
  exit 1
fi

echo ""
echo "=== 8) Frontend build (opsiyonel, API 200 ise) ==="
if [ -f "$ROOT/frontend/package.json" ]; then
  if [ ! -f "$ROOT/frontend/.env.local" ] && [ -f "$OLD/frontend/.env.local" ]; then
    cp "$OLD/frontend/.env.local" "$ROOT/frontend/.env.local"
  fi
  cd "$ROOT/frontend"
  npm install --legacy-peer-deps
  rm -rf .next
  npm run build
  pm2 restart sey-frontend || pm2 start ecosystem.config.cjs
fi

echo ""
echo "RECOVER_OK"
