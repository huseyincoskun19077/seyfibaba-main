# Seyfibaba Backend (Laravel 10)

Seyfibaba pazaryeri platformunun backend API'si. CodeCanyon Shopo temasina dayali, Turkiye odakli e-ticaret pazaryeri.

## Gereksinimler

- PHP >= 8.1
- Composer
- MySQL 8.x
- Node.js (webpack mix icin, opsiyonel)

## Kurulum

```bash
# 1. Bagimliliklari yukle
composer install

# 2. Ortam dosyasini olustur
cp .env.example .env

# 3. Uygulama anahtari olustur
php artisan key:generate

# 4. JWT secret olustur
php artisan jwt:secret

# 5. .env dosyasinda veritabani bilgilerini ayarla
#    DB_DATABASE=seyfibaba
#    DB_USERNAME=root
#    DB_PASSWORD=sifren

# 6. Veritabanini olustur
mysql -u root -p -e "CREATE DATABASE seyfibaba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Migration ve seed calistir
php artisan migrate --seed

# 8. Storage linkini olustur
php artisan storage:link

# 9. Sunucuyu baslat
php artisan serve
```

Backend `http://localhost:8000` adresinde calisir.
Admin paneli: `http://localhost:8000/admin`

## Ortam Degiskenleri (.env)

| Degisken | Aciklama |
|----------|----------|
| `DB_DATABASE` | MySQL veritabani adi |
| `DB_USERNAME` / `DB_PASSWORD` | MySQL kullanici bilgileri |
| `JWT_SECRET` | `php artisan jwt:secret` ile olusturulur |
| `SMS_PROVIDER` | SMS saglayici: `netgsm` |
| `NETGSM_USERCODE` / `NETGSM_PASSWORD` / `NETGSM_MSGHEADER` | Netgsm API bilgileri |
| `FRONTEND_URL` | Frontend URL (CORS ve callback icin) |
| `MAIL_*` | SMTP mail ayarlari |
| `PUSHER_*` | Real-time bildirimler icin Pusher ayarlari |

AI, Iyzico odeme ve Google Maps ayarlari admin panelinden yapilandirilir.

## Sik Kullanilan Komutlar

```bash
# Sunucuyu baslat
php artisan serve

# Migration calistir
php artisan migrate

# Seed calistir
php artisan db:seed

# Cache temizle
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Hepsini bir seferde temizle
php artisan optimize:clear

# Route listesi
php artisan route:list

# Tinker (REPL)
php artisan tinker

# Kuyruk calistir (bildirimler, mail vb.)
php artisan queue:work
```

## Test

```bash
# Tum testleri calistir
php artisan test

# Belirli bir test calistir
php artisan test --filter=SellerKycApiTest
php artisan test --filter=AdminBulkImportApiTest
```

Feature testler `tests/Feature/` altindadir. SQLite kullanan testler `pdo_sqlite` eklentisi gerektirir; yoksa otomatik atlanir.

### Smoke Test

PHPUnit kullanilmadan HTTP uzerinden test:

```bash
bash tests/smoke/marketplace-feature-smoke.sh http://localhost:8000 "$SELLER_TOKEN" "$ADMIN_TOKEN"
```

## Proje Yapisi

```
app/
  Http/Controllers/     # API ve web controller'lar
    API/                 # Mobil/frontend API endpoint'leri
    WEB/Admin/           # Admin panel controller'lari
    WEB/Seller/          # Satici panel controller'lari
    Auth/                # Login, OTP, kayit
  Models/                # Eloquent modeller
  Services/              # Business logic servisleri
config/                  # Uygulama konfigurasyonu
database/
  migrations/            # Veritabani migration'lari
  seeders/               # Seed dosyalari
resources/views/         # Blade template'ler (admin & seller panel)
routes/
  api.php                # API route'lari
  web.php                # Web route'lari
public/uploads/          # Yuklenen dosyalar (resimler vb.)
```

## Entegrasyonlar

- **Iyzico** — Pazaryeri odeme modeli (sub-merchant destegi), admin panelden yapilandirilir
- **Iletimerkezi / Netgsm** — OTP SMS dogrulamasi
- **OpenAI / Claude / Groq** — AI ile urun icerigi olusturma
- **Pusher** — Real-time bildirimler
- **Google Maps** — Adres secimi ve harita

## Production

```bash
# Config ve route cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# .env'de
APP_ENV=production
APP_DEBUG=false
```
