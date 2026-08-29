# Tek arsiv + tek SSH — sifre en fazla 1-2 kez (anahtar kuruluysa 0)
$ErrorActionPreference = "Stop"

$SERVER = "root@45.138.183.101"
$REMOTE = "/opt/seyfibaba-main"
$BACKEND = "C:\Users\monster\Desktop\seyfibaba-main\backend"
$FRONTEND = "C:\Users\monster\Desktop\seyfibaba-main\frontend"
$KEY = "$env:USERPROFILE\.ssh\id_ed25519_seyfibaba"
$SSH_OPTS = @()
if (Test-Path $KEY) {
    $SSH_OPTS = @("-i", $KEY, "-o", "IdentitiesOnly=yes")
}

function Copy-DeployFile {
    param(
        [string]$Base,
        [string]$Rel,
        [string]$StageRoot
    )
    $src = [System.IO.Path]::Combine($Base, $Rel)
    if (-not (Test-Path -LiteralPath $src)) { throw "Eksik: $src" }
    $dest = [System.IO.Path]::Combine($StageRoot, $Rel)
    $destDir = [System.IO.Path]::GetDirectoryName($dest)
    if ($destDir -and -not (Test-Path -LiteralPath $destDir)) {
        [void][System.IO.Directory]::CreateDirectory($destDir)
    }
    Copy-Item -LiteralPath $src -Destination $dest -Force
}

$backendFiles = @(
    "app\Exceptions\Handler.php",
    "app\Http\Middleware\JwtTokenFromQuery.php",
    "app\Services\CartCleanupService.php",
    "app\Services\CartPriceService.php",
    "app\Http\Controllers\CartController.php",
    "app\Http\Controllers\User\UserProfileController.php",
    "app\Http\Controllers\User\PaymentController.php",
    "app\Http\Controllers\User\IyzicoController.php",
    "routes\api.php"
)

$frontendFiles = @(
    'src\app\(website)\order\[id]\page.js',
    "src\components\OrderCom\index.js",
    "src\components\OrderCom\OrderPageClient.jsx",
    "src\components\OrderCom\PrintBtn.jsx",
    "src\redux\features\auth\apiSlice.js",
    "src\api\fetchOrderDetailsClient.js",
    "src\utils\orderStatus.js",
    "src\utils\clearCartAfterOrder.js",
    "src\utils\cartPriceRefresh.js",
    "src\hooks\useRefreshCartPrices.js",
    "src\appConfig\apiRoutes.js",
    "src\components\CartPage\index.jsx",
    "src\components\CartPage\ProductsTable.jsx",
    "src\components\Cart\index.jsx",
    "src\components\CheckoutPage\index.jsx",
    "src\components\CheckoutPage\hooks\usePlaceOrder.js"
)

$stage = Join-Path $env:TEMP "seyfibaba-deploy-$(Get-Date -Format 'yyyyMMddHHmmss')"
New-Item -ItemType Directory -Path "$stage\backend" -Force | Out-Null
New-Item -ItemType Directory -Path "$stage\frontend" -Force | Out-Null

foreach ($rel in $backendFiles) {
    Copy-DeployFile -Base $BACKEND -Rel $rel -StageRoot "$stage\backend"
}

foreach ($rel in $frontendFiles) {
    Copy-DeployFile -Base $FRONTEND -Rel $rel -StageRoot "$stage\frontend"
}

$zip = "$env:TEMP\seyfibaba-deploy.zip"
if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path "$stage\*" -DestinationPath $zip -Force
Remove-Item $stage -Recurse -Force

Write-Host "Arsiv gonderiliyor ($([math]::Round((Get-Item $zip).Length/1KB)) KB)..." -ForegroundColor Cyan
scp @SSH_OPTS $zip "${SERVER}:/tmp/seyfibaba-deploy.zip"

Write-Host "Sunucuda dosyalar aciliyor + build..." -ForegroundColor Cyan
$remoteScript = @'
set -e
cd /opt/seyfibaba-main
unzip -o /tmp/seyfibaba-deploy.zip -d /tmp/seyfibaba-deploy-extract
cp -r /tmp/seyfibaba-deploy-extract/backend/* backend/
cp -r /tmp/seyfibaba-deploy-extract/frontend/* frontend/
rm -rf /tmp/seyfibaba-deploy-extract /tmp/seyfibaba-deploy.zip
cd backend
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
cd ../frontend
rm -rf .next
npm run build
if ! grep -q "PrintBtn" src/components/OrderCom/index.js; then
  echo "HATA: PrintBtn import kaynak dosyada yok"
  exit 1
fi
if ! grep -rq "orderDetailApi" .next 2>/dev/null; then
  echo "HATA: orderDetailApi build ciktisinda yok — build basarisiz veya eski"
  exit 1
fi
pm2 restart sey-frontend --update-env
echo "--- Kaynak ---"
grep "PrintBtn" src/components/OrderCom/index.js | head -1
echo "--- Build ---"
cat .next/BUILD_ID
echo DEPLOY_OK
'@

$remoteScript | ssh @SSH_OPTS $SERVER "bash -s"
Remove-Item $zip -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "Deploy tamam. https://seyfibaba.com kontrol et (Ctrl+Shift+R)" -ForegroundColor Green
