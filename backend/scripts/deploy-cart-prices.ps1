# Sepet canli fiyat guncellemesi
$ErrorActionPreference = "Stop"
$SERVER = "root@45.138.183.101"
$REMOTE = "/opt/seyfibaba-main"
$BACKEND = "C:\Users\monster\Desktop\seyfibaba-main\backend"
$FRONTEND = "C:\Users\monster\Desktop\seyfibaba-main\frontend"

$backendFiles = @(
    "app\Services\CartPriceService.php",
    "app\Http\Controllers\CartController.php",
    "routes\api.php",
    "tests\Unit\Services\CartPriceServiceTest.php"
)

$frontendFiles = @(
    "src\utils\cartPriceRefresh.js",
    "src\hooks\useRefreshCartPrices.js",
    "src\appConfig\apiRoutes.js",
    "src\components\CartPage\index.jsx",
    "src\components\CartPage\ProductsTable.jsx",
    "src\components\Cart\index.jsx",
    "src\components\CheckoutPage\index.jsx"
)

Write-Host "=== Backend ===" -ForegroundColor Cyan
foreach ($rel in $backendFiles) {
    $src = Join-Path $BACKEND $rel
    $remotePath = ($rel -replace '\\', '/')
    $remoteDir = Split-Path $remotePath -Parent
    ssh $SERVER "mkdir -p $REMOTE/backend/$remoteDir"
    scp $src "${SERVER}:${REMOTE}/backend/${remotePath}"
    Write-Host "OK  $rel" -ForegroundColor Green
}

Write-Host "=== Frontend ===" -ForegroundColor Cyan
foreach ($rel in $frontendFiles) {
    $src = Join-Path $FRONTEND $rel
    $remotePath = ($rel -replace '\\', '/')
    $remoteDir = Split-Path $remotePath -Parent
    ssh $SERVER "mkdir -p $REMOTE/frontend/$remoteDir"
    scp $src "${SERVER}:${REMOTE}/frontend/${remotePath}"
    Write-Host "OK  $rel" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== Sunucuda ===" -ForegroundColor Yellow
Write-Host @"
ssh $SERVER
cd $REMOTE/backend && php artisan route:cache && php artisan config:cache
cd $REMOTE/frontend && npm run build && pm2 restart sey-frontend
"@
