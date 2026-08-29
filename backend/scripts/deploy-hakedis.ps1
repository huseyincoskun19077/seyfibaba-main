# Hakediş (payout) + Netgsm fix — production deploy
# Kullanım: PowerShell'de bu dosyayı çalıştırın
#   cd C:\Users\monster\Desktop\seyfibaba-main\backend\scripts
#   .\deploy-hakedis.ps1

$ErrorActionPreference = "Stop"
$SERVER = "root@45.138.183.101"
$REMOTE = "/opt/seyfibaba-main/backend"
$LOCAL = "C:\Users\monster\Desktop\seyfibaba-main\backend"

$files = @(
    "database\migrations\2026_08_28_120000_add_payout_timing_settings_to_settings_table.php",
    "app\Services\CartPriceService.php",
    "app\Services\PayoutSettingsService.php",
    "app\Services\SellerPayoutService.php",
    "app\Services\IyzicoService.php",
    "app\Services\NetgsmService.php",
    "app\Models\Setting.php",
    "app\Console\Commands\ProcessSellerPayouts.php",
    "app\Console\Commands\AutoCompleteOrders.php",
    "app\Console\Commands\AutoConfirmOrderItems.php",
    "app\Console\Commands\TestSellerPayoutFlow.php",
    "app\Http\Controllers\Admin\OrderController.php",
    "app\Http\Controllers\WEB\Admin\OrderController.php",
    "app\Http\Controllers\WEB\Admin\CommissionController.php",
    "app\Http\Controllers\User\UserProfileController.php",
    "app\Http\Controllers\User\IyzicoController.php",
    "app\Http\Controllers\User\ReturnRequestController.php",
    "app\Http\Controllers\WEB\Seller\ReturnRequestController.php",
    "routes\web.php",
    "resources\views\admin\commission_settings.blade.php"
)

Write-Host "=== Hakedis deploy: $SERVER ===" -ForegroundColor Cyan

foreach ($rel in $files) {
    $src = Join-Path $LOCAL $rel
    if (-not (Test-Path $src)) {
        Write-Host "EKSIK: $rel" -ForegroundColor Red
        exit 1
    }
    $remotePath = ($rel -replace '\\', '/')
    $remoteDir = Split-Path $remotePath -Parent
    ssh $SERVER "mkdir -p $REMOTE/$remoteDir"
    scp $src "${SERVER}:${REMOTE}/${remotePath}"
    Write-Host "OK  $rel" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== Sunucuda calistirin ===" -ForegroundColor Yellow
Write-Host @"
ssh $SERVER
cd $REMOTE
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
php artisan payout:test-flow
"@
