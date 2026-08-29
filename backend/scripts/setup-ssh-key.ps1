# Bir kez calistir — sonra deploy'da sifre sormaz
# Kullanim: .\setup-ssh-key.ps1

$ErrorActionPreference = "Stop"
$SERVER = "root@45.138.183.101"
$KEY = "$env:USERPROFILE\.ssh\id_ed25519_seyfibaba"
$PUB = "$KEY.pub"

if (-not (Test-Path "$env:USERPROFILE\.ssh")) {
    New-Item -ItemType Directory -Path "$env:USERPROFILE\.ssh" | Out-Null
}

if (-not (Test-Path $KEY)) {
    Write-Host "SSH anahtari olusturuluyor..." -ForegroundColor Cyan
    ssh-keygen -t ed25519 -f $KEY -N '""' -C "seyfibaba-deploy"
}

Write-Host ""
Write-Host "Sunucuya anahtar ekleniyor (SON KEZ sifre istenecek)..." -ForegroundColor Yellow
Get-Content $PUB | ssh $SERVER "mkdir -p ~/.ssh && chmod 700 ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"

Write-Host ""
Write-Host "Test (sifre sormamali):" -ForegroundColor Cyan
ssh -i $KEY -o IdentitiesOnly=yes $SERVER "echo SSH_KEY_OK && hostname"

Write-Host ""
Write-Host "Tamam. Deploy script artik bu anahtari kullanir." -ForegroundColor Green
Write-Host "Anahtar: $KEY"
