


# SEYFIBABA - Production Ortamı Dokümanı

**Son Güncelleme:** 27 Ağustos 2026  
**Durum:** ✅ Production Modunda Aktif  
**Erişim:** https://seyfibaba.com

---

## İÇİNDEKİLER

1. [Genel Bakış](#1-genel-bakış)
2. [Proje Yapısı ve Konumları](#2-proje-yapısı-ve-konumları)
3. [Teknoloji Stacki](#3-teknoloji-stacki)
4. [Sunucu Bilgileri](#4-sunucu-bilgileri)
5. [Hizmet Durumları](#5-hizmet-durumları)
6. [Domain ve DNS Yapısı](#6-domain-ve-dns-yapısı)
7. [SSL Sertifikası](#7-ssl-sertifikası)
8. [Nginx Yapısı](#8-nginx-yapısı)
9. [PHP-FPM Yapısı](#9-php-fpm-yapısı)
10. [Laravel Backend](#10-laravel-backend)
11. [Next.js Frontend](#11-nextjs-frontend)
12. [MySQL Veritabanı](#12-mysql-veritabanı)
13. [PM2 (Process Manager)](#13-pm2-process-manager)
14. [Güvenlik Önlemleri](#14-güvenlik-önlemleri)
15. [Firewall (UFW)](#15-firewall-ufw)
16. [Yedekleme](#16-yedekleme)
17. [Log Dosyaları](#17-log-dosyaları)
18. [Sık Kullanılan Komutlar](#18-sık-kullanılan-komutlar)
19. [Sorun Giderme](#19-sorun-giderme)
20. [İletişim ve Referanslar](#20-iletişim-ve-referanslar)

---

## 1. GENEL BAKIŞ

Seyfibaba, Türkiye odaklı bir e-ticaret pazaryeri platformudur. CodeCanyon Shopo teması üzerine inşa edilmiştir.

### Erişim Noktaları
| Domain | Amaç | Port |
|--------|------|------|
| `seyfibaba.com` | Ana mağaza | 443 (HTTPS) |
| `www.seyfibaba.com` | WWW variantı | 443 (HTTPS) |
| `admin.seyfibaba.com` | Admin paneli | 443 (HTTPS) |
| `ikinciel.seyfibaba.com` | İkinci el ürünler | 443 (HTTPS) |

### Mimari Özeti
```
Kullanıcı → Cloudflare (CDN/SSL) → Nginx (Port 80/443)
                                        ├── /api/* → Laravel Backend (PHP-FPM)
                                        ├── /admin/* → Laravel Backend (PHP-FPM)
                                        └── /* → Next.js Frontend (PM2, Port 3001)
```

---

## 2. PROJE YAPISI VE KONUMLARI

### Ana Dizin
```
/opt/
├── seyfibaba-main/           # Ana proje dizini
│   ├── backend/              # Laravel 10 backend
│   ├── frontend/             # Next.js 16 frontend
│   └── .mysql-root-password  # MySQL root şifresi (600 izni)
├── backup-2026-08-27/        # Veritabanı ve upload yedekleri
│   ├── seyfibaba-db-2026-08-27.sql.gz
│   └── seyfibaba-uploads-2026-08-27.tar.gz
└── SEYFIBABA-PRODUCTION-DOKUMANI.md  # Bu dosya
```

### Backend Dizini (`/opt/seyfibaba-main/backend/`)
```
backend/
├── app/                    # Uygulama kodları
│   ├── Http/Controllers/   # Controller sınıfları
│   │   ├── Admin/          # Admin paneli controller'ları
│   │   ├── API/            # API endpoint'leri
│   │   ├── Seller/         # Satıcı controller'ları
│   │   └── User/           # Kullanıcı controller'ları
│   ├── Models/             # 109 Eloquent model
│   ├── Services/           # Servis sınıfları (IyzicoService vb.)
│   └── Providers/          # Servis sağlayıcıları
├── config/                 # Yapılandırma dosyaları
├── database/               # Migration'lar ve seed'lar
├── public/                 # Web erişim noktası (document root)
│   ├── index.php           # Laravel giriş noktası
│   └── uploads/            # Yüklenen dosyalar
├── resources/              # View blade dosyaları
├── routes/                 # Route tanımları
│   ├── api.php             # API rotaları (63KB)
│   └── web.php             # Web rotaları (79KB)
├── storage/                # Log, cache, dosya depolama
│   ├── app/                # Uygulama dosyaları
│   ├── framework/          # Cache, session
│   └── logs/               # Laravel logları
├── vendor/                 # Composer bağımlılıkları
├── .env                    # Ortam değişkenleri (640, www-data)
├── composer.json           # PHP bağımlılıkları
└── composer.lock           # Kilitli versiyonlar
```

### Frontend Dizini (`/opt/seyfibaba-main/frontend/`)
```
frontend/
├── src/                    # Kaynak kodlar
│   ├── app/                # Next.js App Router sayfaları
│   ├── components/         # React bileşenleri (41 adet)
│   ├── redux/              # Redux state yönetimi
│   ├── hooks/              # Custom React hook'ları
│   └── utils/              # Yardımcı fonksiyonlar
├── public/                 # Statik dosyalar
├── .next/                  # Production build çıktısı
├── node_modules/           # npm bağımlılıkları
├── server.js               # Custom Node.js sunucusu (http-proxy)
├── ecosystem.config.cjs    # PM2 yapılandırması
├── .env.local              # Ortam değişkenleri (640, www-data)
├── next.config.mjs         # Next.js yapılandırması
├── package.json            # npm bağımlılıkları
└── package-lock.json       # Kilitli versiyonlar
```

---

## 3. TEKNOLOJİ STACKİ

### Backend
| Teknoloji | Versiyon | Amaç |
|-----------|----------|------|
| PHP | 8.3.6 | Programlama dili |
| Laravel | 10.x-dev | MVC framework |
| MySQL | 8.0.46 | Veritabanı |
| Composer | 2.10.3 | PHP paket yöneticisi |
| JWT Auth | tymon/jwt-auth | Kimlik doğrulama |
| Iyzico | - | Ödeme entegrasyonu (pazaryeri modu) |
| Intervention Image | - | Görsel işleme |
| maatwebsite/excel | - | Excel ithalat/ihracat |
| Firebase | - | Push bildirimler |
| NetGSM | - | SMS entegrasyonu |

### Frontend
| Teknoloji | Versiyon | Amaç |
|-----------|----------|------|
| Node.js | 20.20.2 | JavaScript runtime |
| Next.js | 16.3.3 | React framework (App Router) |
| React | 19.1.0 | UI kütüphanesi |
| Redux Toolkit | 2.8.2 | State yönetimi |
| Tailwind CSS | 4.1.12 | CSS framework |
| TypeScript | 5.8.3 | Tip güvenliği |
| http-proxy | 1.18.1 | API proxy (server.js) |

### Altyapı
| Teknoloji | Versiyon | Amaç |
|-----------|----------|------|
| Ubuntu | 24.04.4 LTS | İşletim sistemi |
| Nginx | 1.24.0 | Web sunucu / reverse proxy |
| PHP-FPM | 8.3 | FastCGI process manager |
| PM2 | 7.0.4 | Node.js process manager |
| UFW | - | Firewall |
| Let's Encrypt | - | SSL sertifikası |
| Cloudflare | - | CDN, DDoS koruması, SSL |

---

## 4. SUNUCU BİLGİLERİ

### Fiziksel/VPS Sunucu
```
IP Adresi:     45.138.183.101
İşletim Sistemi: Ubuntu 24.04.4 LTS
Kernel:        6.8.0-138-generic
Mimari:        x86_64
Hostname:      server
```

### Kaynak Kullanımı
```
RAM:    27GB toplam, ~2.9GB kullanılan, ~24GB müsait
Disk:   11GB toplam, 9.6GB kullanılan (%95 dolu)
CPU:    %17.4 kullanım
```

> ⚠️ **DİKKAT:** Disk kullanımı %95'e ulaşmış durumda. Temizlik yapılmalı.

---

## 5. HİZMET DURUMLARI

```bash
# Tüm hizmetleri kontrol et
systemctl is-active mysql nginx php8.3-fpm
pm2 list
```

| Hizmet | Durum | Başlatma | Port |
|--------|-------|----------|------|
| MySQL | ✅ active | Otomatik | 3306 (localhost) |
| Nginx | ✅ active | Otomatik | 80, 443 |
| PHP-FPM 8.3 | ✅ active | Otomatik | Unix socket |
| PM2 (sey-frontend) | ✅ online | Otomatik | 3001 |
| UFW Firewall | ✅ active | Otomatik | - |

### Otomatik Başlatma
```bash
systemctl enable mysql nginx php8.3-fpm
pm2 startup  # PM2'nin boot'ta başlaması için
pm2 save     # Mevcut process listesini kaydet
```

---

## 6. DOMAIN VE DNS YAPISI

### Cloudflare DNS Kayıtları
| Domain | Tip | Hedef | Proxy |
|--------|-----|-------|-------|
| seyfibaba.com | A | 45.138.183.101 | ✅ Turuncu bulut |
| www.seyfibaba.com | CNAME | seyfibaba.com | ✅ Turuncu bulut |
| admin.seyfibaba.com | A | 45.138.183.101 | ✅ Turuncu bulut |
| ikinciel.seyfibaba.com | A | 45.138.183.101 | ✅ Turuncu bulut |

### Cloudflare Ayarları
- **SSL Modu:** Full (Origin)
- **Proxy:** Aktif (turuncu bulut)
- **DDoS Koruması:** Aktif
- **Cache:** Cloudflare CDN tarafından yönetiliyor

### Gerçek IP Algılama
Nginx, Cloudflare IP aralıklarından gelen isteklerde `CF-Connecting-IP` başlığını kullanarak gerçek müşteri IP'lerini algılar.

---

## 7. SSL SERTİFİKASI

### Let's Encrypt Sertifikası
```
Sertifika Adı:    seyfibaba.com
Tür:              ECDSA
Bitiş Tarihi:     25 Kasım 2026 (89 gün kaldı)
Otomatik Yenileme: Evet (certbot timer)
```

### Kapsanan Domainler
- seyfibaba.com
- www.seyfibaba.com
- admin.seyfibaba.com
- ikinciel.seyfibaba.com

### SSL Dosya Konumları
```
/etc/letsencrypt/live/seyfibaba.com/
├── fullchain.pem    # Sertifika zinciri
├── privkey.pem      # Özel anahtar
├── chain.pem        # CA sertifikası
└── cert.pem         # Ana sertifika
```

### Yenileme Komutu
```bash
# Manuel yenileme (otomatik çalışır ama garanti olsun)
certbot renew --nginx

# Yenileme testi
certbot renew --dry-run
```

---

## 8. NGINX YAPISI

### Yapılandırma Dosyası
```
/etc/nginx/sites-available/seyfibaba    # Ana yapılandırma
/etc/nginx/sites-enabled/seyfibaba      # Aktif link
/etc/nginx/nginx.conf                   # Ana nginx config
```

### Upstream Tanımları
```nginx
upstream frontend {
    server 127.0.0.1:3001;  # Next.js PM2
}
```

### Domain Bazlı Yönlendirme

#### seyfibaba.com / www.seyfibaba.com
```
/api/*        → @php_fpm (Laravel backend)
/admin/*      → @php_fpm (Laravel backend)
/seller/*     → @php_fpm (Laravel backend)
/call-center/* → @php_fpm (Laravel backend)
/uploads/*    → /opt/seyfibaba-main/backend/public/uploads/
/storage/*    → /opt/seyfibaba-main/backend/public/storage/
/*            → http://frontend (Next.js)
```

#### admin.seyfibaba.com
```
/*            → Laravel backend (try_files + PHP-FPM)
```

#### ikinciel.seyfibaba.com
```
/*            → http://frontend (Next.js)
/uploads/*    → /opt/seyfibaba-main/backend/public/uploads/
```

### Named Location: @php_fpm
```nginx
location @php_fpm {
    fastcgi_pass unix:/run/php/php-fpm.sock;
    fastcgi_param SCRIPT_FILENAME /opt/seyfibaba-main/backend/public/index.php;
    fastcgi_read_timeout 300;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
}
```

> **ÖNEMLİ:** `try_files $uri $uri/ /index.php?$query_string` yerine `@php_fpm` kullanılıyor. Bu, Laravel rotalarının Next.js proxy'sine düşmesini engelliyor.

### Güvenlik Başlıkları
```nginx
# HSTS
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload";

# Clickjackling koruması
add_header X-Frame-Options "SAMEORIGIN";

# MIME koruması
add_header X-Content-Type-Options "nosniff";

# XSS koruması
add_header X-XSS-Protection "1; mode=block";

# Referrer politikası
add_header Referrer-Policy "strict-origin-when-cross-origin";

# Permissions policy
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()";
```

### Gzip Sıkıştırma
```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript 
           text/xml application/xml text/javascript image/svg+xml;
```

### Nginx Komutları
```bash
nginx -t                    # Yapılandırma testi
systemctl reload nginx      # Yeniden yükleme
systemctl restart nginx     # Yeniden başlatma
tail -f /var/log/nginx/access.log   # Erişim logları
tail -f /var/log/nginx/error.log    # Hata logları
```

---

## 9. PHP-FPM YAPISI

### Pool Yapılandırması
```
Dosya: /etc/php/8.3/fpm/pool.d/seyfibaba.conf
```

```ini
[seyfibaba]
user = www-data
group = www-data
listen = /run/php/php-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 10        # Maksimum child process
pm.start_servers = 2        # Başlangıç process sayısı
pm.min_spare_servers = 1    # Minimum boş process
pm.max_spare_servers = 5    # Maksimum boş process
pm.max_requests = 500       # Process yenileme
```

### PHP Ayarları (php.ini)
```
Dosya: /etc/php/8.3/fpm/php.ini
```

| Ayar | Değer | Açıklama |
|------|-------|----------|
| expose_php | Off | PHP versiyonu gizli |
| allow_url_fopen | Off | Uzak dosya erişimi kapalı |
| allow_url_include | Off | Uzak include kapalı |
| display_errors | Off | Hatalar gösterilmiyor |
| log_errors | On | Hatalar loglanıyor |
| memory_limit | 256M | Bellek limiti |
| upload_max_filesize | 64M | Maksimum dosya boyutu |
| post_max_size | 128M | Maksimum POST boyutu |
| max_execution_time | 300 | Maksimum çalışma süresi |

### PHP-FPM Komutları
```bash
systemctl restart php8.3-fpm
systemctl status php8.3-fpm
php -v                                # Versiyon
php -i | grep "Loaded Configuration"  # Aktif config
php -r "echo ini_get('expose_php');"  # Ayar kontrol
```

---

## 10. LARAVEL BACKEND

### Versiyon ve Yapılandırma
```
Laravel:   10.x-dev
PHP:       8.3.6
APP_ENV:   production
APP_DEBUG: false
```

### Ortam Değişkenleri (.env)
```
Dosya: /opt/seyfibaba-main/backend/.env
İzin:  640 (www-data:www-data)
```

#### Önemli Ayarlar
```env
APP_NAME=Seyfibaba
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seyfibaba.com

# Veritabanı
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seyfibaba
DB_USERNAME=root
DB_PASSWORD=uFyyoiALFJ2CAXpG7PKp

# JWT
JWT_SECRET=zmu3TSNgxN0jcUUU0JW5eqpB5U3QnZHYupPJs9aaDrAx7Uz0DDURm5HhazfDYz9H
JWT_TTL=10080  # 7 gün

# Firebase
FIREBASE_PROJECT_ID=seyfibabapp

# AWS (opsiyonel)
AWS_DEFAULT_REGION=eu-central-1
```

### Route Yapısı
```
/opt/seyfibaba-main/backend/routes/api.php  (63KB)
/opt/seyfibaba-main/backend/routes/web.php  (79KB)
```

### Rate Limiting
```php
// Global: 60 istek/dakika
\Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1'

// Auth route'ları: Özel limitler
throttle:auth-login
throttle:otp-send
throttle:otp-verify
throttle:public-form
```

### Cache Ayarları
```bash
php artisan config:cache    # Config cache
php artisan route:cache     # Route cache
php artisan view:cache      # View cache
php artisan storage:link    # Storage symlink
```

### Laravel Komutları
```bash
cd /opt/seyfibaba-main/backend

# Durum kontrol
php artisan about
php artisan route:list --columns=method,uri

# Cache yönetimi
php artisan config:cache
php artisan config:clear
php artisan route:cache
php artisan route:clear
php artisan view:cache
php artisan view:clear
php artisan cache:clear

# Log
tail -f storage/logs/laravel.log

# Yetkiler
chown -R www-data:www-data storage/ bootstrap/cache/
chmod -R 750 storage/ bootstrap/cache/
```

---

## 11. NEXT.JS FRONTEND

### Versiyon ve Yapılandırma
```
Next.js:    16.3.3 (Turbopack)
React:      19.1.0
Node.js:    20.20.2
PORT:       3001
```

### Ortam Değişkenleri (.env.local)
```
Dosya: /opt/seyfibaba-main/frontend/.env.local
İzin:  640 (www-data:www-data)
```

#### Ayarlar
```env
# Backend API URL
NEXT_PUBLIC_BASE_URL=https://admin.seyfibaba.com

# Application URL
NEXT_APPLICATION_URL=https://seyfibaba.com

# İkinci el subdomain
NEXT_PUBLIC_SECOND_HAND_SUBDOMAIN=1
NEXT_PUBLIC_SECOND_HAND_ORIGIN=https://ikinciel.seyfibaba.com
NEXT_PUBLIC_MARKETPLACE_ORIGIN=https://seyfibaba.com
NEXT_PUBLIC_COOKIE_DOMAIN=.seyfibaba.com

# PWA (devre dışı)
NEXT_PWA_STATUS=0

# Ortam
NODE_ENV=production
PORT=3001
```

### Custom Server (server.js)
```
Dosya: /opt/seyfibaba-main/frontend/server.js
```

- `http-proxy` ile API isteklerini Laravel backend'ine proxy'ler
- `/api/*` isteklerini `admin.seyfibaba.com:443` adresine yönlendirir
- Port 3001'de dinler

### PM2 Yapılandırması
```javascript
// ecosystem.config.cjs
module.exports = {
  apps: [{
    name: 'sey-frontend',
    script: 'server.js',
    cwd: '/opt/seyfibaba-main/frontend',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3001,
      BACKEND_PROXY_HOST: 'admin.seyfibaba.com',
    },
  }],
};
```

### Frontend Build
```bash
cd /opt/seyfibaba-main/frontend

# Production build
npm run build

# Development
npm run dev
```

### Frontend Komutları
```bash
pm2 status                    # Durum
pm2 logs sey-frontend         # Loglar
pm2 restart sey-frontend      # Yeniden başlat
pm2 stop sey-frontend         # Durdur
pm2 delete sey-frontend       # Sil
```

---

## 12. MYSQL VERİTABANI

### Versiyon ve Yapılandırma
```
MySQL:    8.0.46-0ubuntu0.24.04.3
Port:     3306 (sadece localhost)
Kullanıcı: root
Şifre:    uFyyoiALFJ2CAXpG7PKp
```

### Veritabanı
```
Veritabanı Adı: seyfibaba
Tablo Sayısı:   109+ (Eloquent modeller)
```

### Güvenlik
- ✅ Sadece localhost'ta dinliyor
- ✅ Güçlü root şifresi var
- ✅ UFW tarafından korunuyor (3306 portu sadece 127.0.0.1)

### MySQL Komutları
```bash
# Bağlanma
mysql -u root -p'uFyyoiALFJ2CAXpG7PKp'

# Veritabanı listesi
SHOW DATABASES;

# Tablo listesi
USE seyfibabamarket;
SHOW TABLES;

# Durum kontrol
SHOW STATUS;

# Backup alma
mysqldump -u root -p seyfibabamarket > backup.sql

# Backup geri yükle
mysql -u root -p seyfibabamarket < backup.sql
```

---

## 13. PM2 (PROCESS MANAGER)

### Durum
```
İsim:         sey-frontend
Durum:        online
PID:          28409
Uptime:       28+ dakika
RAM:          280.7MB
CPU:          %0
Restart:      1 (otomatik yeniden başladı)
```

### PM2 Komutları
```bash
pm2 list                          # Tüm process'leri listele
pm2 status                        # Durum
pm2 monit                         # İzleme arayüzü
pm2 logs sey-frontend             # Loglar
pm2 logs sey-frontend --lines 100 # Son 100 satır
pm2 restart sey-frontend          # Yeniden başlat
pm2 stop sey-frontend             # Durdur
pm2 delete sey-frontend           # Sil
pm2 save                          # Mevcut durumu kaydet
pm2 resurrect                     # Kaydedilen durumu geri yükle
pm2 startup                       # Boot'ta otomatik başlatma
```

### Log Konumları
```
~/.pm2/logs/sey-frontend-out.log   # Standart çıktı
~/.pm2/logs/sey-frontend-error.log # Hata logları
```

---

## 14. GÜVENLİK ÖNLEMLERİ

### Yapılan Güvenlik Düzeltmeleri

| # | Düzeltme | Durum |
|---|----------|-------|
| 1 | MySQL root şifresi güçlü şifreyle değiştirildi | ✅ |
| 2 | .env dosyası izinleri 640 www-data olarak ayarlandı | ✅ |
| 3 | PHP expose_php = Off | ✅ |
| 4 | PHP allow_url_fopen = Off | ✅ |
| 5 | Composer paket güvenlik açıkları güncellendi (42→7) | ✅ |
| 6 | Global rate limiting (60 istek/dk) eklendi | ✅ |
| 7 | UFW firewall etkinleştirildi (22,80,443) | ✅ |
| 8 | Logrotate yapılandırması eklendi | ✅ |
| 9 | Storage/log izinleri sıkılaştırıldı (750) | ✅ |

### Güvenlik Başlıkları
```
✅ HSTS: max-age=31536000; includeSubDomains; preload
✅ CSP: Tam tanımlı
✅ X-Frame-Options: SAMEORIGIN
✅ X-Content-Type-Options: nosniff
✅ X-XSS-Protection: 1; mode=block
✅ Referrer-Policy: strict-origin-when-cross-origin
✅ Permissions-Policy: camera=(), microphone=(), geolocation=()
✅ Server: cloudflare (versiyon gizli)
```

### Laravel Güvenlik
```
✅ APP_DEBUG=false (hata detayları gösterilmiyor)
✅ JWT secret güçlü (65 karakter)
✅ APP_KEY mevcut (51 karakter)
✅ CSRF koruması aktif
✅ XSS koruması aktif (mews/purifier)
✅ SQL injection koruması (Eloquent ORM)
```

### Dosya İzinleri
```
.env                    → 640 www-data:www-data
.env.local              → 640 www-data:www-data
storage/                → 750 www-data:www-data
bootstrap/cache/        → 750 www-data:www-data
storage/logs/laravel.log → 640 www-data:www-data
```

### MySQL Güvenlik
```
✅ Sadece localhost'ta dinliyor (127.0.0.1:3306)
✅ UFW tarafından korunuyor
✅ Güçlü root şifresi
```

### Kalan Düşük Öncelikli Açıklar (7 adet)
```
- laravel/framework: CRLF injection (medium)
- paragonie/sodium_compat: Ed25519 validation (low)
- symfony/yaml: ReDoS (low)
```

---

## 15. FIREWALL (UFW)

### Aktif Kurallar
```
Status: active

     To                         Action      From
     --                         ------      ----
[ 1] 22/tcp                     ALLOW IN    Anywhere                   # SSH
[ 2] 80/tcp                     ALLOW IN    Anywhere                   # HTTP
[ 3] 443/tcp                    ALLOW IN    Anywhere                   # HTTPS
[ 4] 3306                       ALLOW IN    127.0.0.1                  # MySQL localhost
[ 5] 22/tcp (v6)                ALLOW IN    Anywhere (v6)              # SSH
[ 6] 80/tcp (v6)                ALLOW IN    Anywhere (v6)              # HTTP
[ 7] 443/tcp (v6)               ALLOW IN    Anywhere (v6)              # HTTPS
```

### Firewall Komutları
```bash
ufw status                    # Durum
ufw status verbose            # Detaylı durum
ufw status numbered           # Numaralı kurallar
ufw allow 22/tcp              # SSH ekle
ufw allow 80/tcp              # HTTP ekle
ufw allow 443/tcp             # HTTPS ekle
ufw deny 3306                 # MySQL dışarıya kapat
ufw delete [numara]           # Kural sil
ufw reload                    # Yeniden yükle
ufw disable                   # Devre dışı bırak
```

---

## 16. YEDEKLEME

### Mevcut Yedek
```
Konum: /opt/backup-2026-08-27/
├── seyfibaba-db-2026-08-27.sql.gz      # Veritabanı yedeği
└── seyfibaba-uploads-2026-08-27.tar.gz # Upload dosyaları
```

### Manuel Yedek Alma
```bash
# Veritabanı yedeği
mysqldump -u root -p'uFyyoiALFJ2CAXpG7PKp' seyfibabamarket | gzip > /opt/backup-$(date +%Y-%m-%d)-db.sql.gz

# Upload dosyaları yedeği
tar -czf /opt/backup-$(date +%Y-%m-%d)-uploads.tar.gz -C /opt/seyfibaba-main/backend/public uploads/

# Tam yedek
tar -czf /opt/backup-$(date +%Y-%m-%d)-full.tar.gz \
  -C /opt/seyfibaba-main backend/.env backend/storage/ backend/database/ \
  -C /opt/seyfibaba-main/frontend .env.local
```

### Otomatik Yedek (Cron)
```bash
# Cron job ekle (her gün saat 03:00'te)
crontab -e
0 3 * * * /opt/backup-script.sh
```

---

## 17. LOG DOSYALARI

### Log Konumları
```
/opt/seyfibaba-main/backend/storage/logs/laravel.log    # Laravel uygulama logları
/var/log/nginx/access.log                                # Nginx erişim logları
/var/log/nginx/error.log                                 # Nginx hata logları
/var/log/php-fpm/seyfibaba-error.log                    # PHP-FPM hata logları
~/.pm2/logs/sey-frontend-out.log                        # Next.js çıktı logları
~/.pm2/logs/sey-frontend-error.log                      # Next.js hata logları
/var/log/mysql/error.log                                 # MySQL hata logları
/var/log/letsencrypt/letsencrypt.log                     # SSL sertifika logları
```

### Log İzleme
```bash
# Laravel
tail -f /opt/seyfibaba-main/backend/storage/logs/laravel.log

# Nginx erişim
tail -f /var/log/nginx/access.log

# Nginx hata
tail -f /var/log/nginx/error.log

# PM2
pm2 logs sey-frontend

# Tüm logları izle
multitail /opt/seyfibaba-main/backend/storage/logs/laravel.log \
          /var/log/nginx/access.log \
          /var/log/nginx/error.log
```

### Log Rotation
```
Dosya: /etc/logrotate.d/seyfibaba
Sıklık: Günlük
Saklama: 14 gün
Sıkıştırma: Aktif
```

---

## 18. SIK KULLANILAN KOMUTLAR

### Hizmet Yönetimi
```bash
# Durum kontrol
systemctl status mysql nginx php8.3-fpm
pm2 list

# Yeniden başlatma
systemctl restart mysql nginx php8.3-fpm
pm2 restart sey-frontend

# Durdurma
systemctl stop mysql nginx php8.3-fpm
pm2 stop sey-frontend
```

### Deployment (Güncelleme)
```bash
# Backend güncelleme
cd /opt/seyfibaba-main/backend
git pull origin main
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
chown -R www-data:www-data storage/ bootstrap/cache/
systemctl reload nginx

# Frontend güncelleme
cd /opt/seyfibaba-main/frontend
npm install --legacy-peer-deps
npm run build
pm2 restart sey-frontend
```

### Güvenlik Kontrolü
```bash
# Composer güvenlik
cd /opt/seyfibaba-main/backend && COMPOSER_ALLOW_SUPERUSER=1 composer audit

# SSL kontrol
openssl s_client -connect seyfibaba.com:443 -servername seyfibaba.com

# Nginx yapılandırma testi
nginx -t

# PHP ayar kontrolü
php -r "echo 'expose_php: ' . ini_get('expose_php') . PHP_EOL;"
php -r "echo 'allow_url_fopen: ' . ini_get('allow_url_fopen') . PHP_EOL;"

# Firewall durumu
ufw status
```

### Veritabanı
```bash
# MySQL'e bağlan
mysql -u root -p'uFyyoiALFJ2CAXpG7PKp'

# Veritabanı boyutu
mysql -u root -p'uFyyoiALFJ2CAXpG7PKp' -e "
SELECT table_schema AS 'Veritabanı',
ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Boyut (MB)'
FROM information_schema.tables
GROUP BY table_schema;"

# Tablo analizi
mysql -u root -p'uFyyoiALFJ2CAXpG7PKp' -e "
SELECT table_name AS 'Tablo',
table_rows AS 'Satır Sayısı',
ROUND(data_length / 1024 / 1024, 2) AS 'Veri (MB)'
FROM information_schema.tables
WHERE table_schema = 'seyfibabamarket'
ORDER BY data_length DESC
LIMIT 20;"
```

### Disk Temizliği
```bash
# Eski logları temizle
find /opt/seyfibaba-main/backend/storage/logs/ -name "*.log" -mtime +30 -delete

# npm cache temizle
npm cache clean --force

# Composer cache temizle
COMPOSER_ALLOW_SUPERUSER=1 composer clear-cache

# PM2 logları temizle
pm2 flush

# Eski backup'ları temizle (30 günden eski)
find /opt/backup-* -mtime +30 -delete
```

---

## 19. SORUN GİDERME

### Yaygın Sorunlar ve Çözümleri

#### 1. 502 Bad Gateway
```bash
# PHP-FPM durdu mu?
systemctl status php8.3-fpm

# Socket dosyası var mı?
ls -la /run/php/php-fpm.sock

# PHP-FPM'i yeniden başlat
systemctl restart php8.3-fpm
```

#### 2. 504 Gateway Timeout
```bash
# PM2 process çalışıyor mu?
pm2 list

# Port 3001'de bir şey dinliyor mu?
ss -tlnp | grep 3001

# PM2'yi yeniden başlat
pm2 restart sey-frontend
```

#### 3. Laravel Hataları
```bash
# Log kontrol
tail -50 /opt/seyfibaba-main/backend/storage/logs/laravel.log

# Cache temizle
cd /opt/seyfibaba-main/backend
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

#### 4. SSL Hataları
```bash
# Sertifika durumu
certbot certificates

# Yenileme
certbot renew --nginx

# Nginx'i yeniden başlat
systemctl reload nginx
```

#### 5. Disk Dolu
```bash
# Disk kullanımı
df -h

# Büyük dosyaları bul
du -sh /opt/seyfibaba-main/* | sort -rh

# Log temizliği
find /opt/seyfibaba-main/backend/storage/logs/ -name "*.log" -mtime +7 -delete
pm2 flush
```

#### 6. MySQL Bağlantı Hatası
```bash
# MySQL durumu
systemctl status mysql

# Bağlantı testi
mysql -u root -p'uFyyoiALFJ2CAXpG7PKp' -e "SELECT 1;"

# .env dosyasında DB_PASSWORD doğru mu?
grep DB_PASSWORD /opt/seyfibaba-main/backend/.env
```

#### 7. PM2 Process Çöktü
```bash
# Logları kontrol et
pm2 logs sey-frontend --lines 50

# Yeniden başlat
pm2 restart sey-frontend

# Memory limit aşıldı mı?
pm2 monit
```

---

## 20. İLETİŞİM VE REFERANSLAR

### Önemli Dosya Konumları
| Dosya | Konum |
|-------|-------|
| Backend .env | `/opt/seyfibaba-main/backend/.env` |
| Frontend .env.local | `/opt/seyfibaba-main/frontend/.env.local` |
| Nginx config | `/etc/nginx/sites-available/seyfibaba` |
| PHP-FPM config | `/etc/php/8.3/fpm/pool.d/seyfibaba.conf` |
| PM2 ecosystem | `/opt/seyfibaba-main/frontend/ecosystem.config.cjs` |
| MySQL şifresi | `/opt/seyfibaba-main/.mysql-root-password` |
| SSL sertifikası | `/etc/letsencrypt/live/seyfibaba.com/` |
| Laravel logları | `/opt/seyfibaba-main/backend/storage/logs/` |
| Nginx logları | `/var/log/nginx/` |

### Referans Dokümanlar
- Laravel: https://laravel.com/docs/10.x
- Next.js: https://nextjs.org/docs
- Nginx: https://nginx.org/en/docs/
- PHP-FPM: https://www.php.net/manual/en/install.fpm.php
- UFW: https://help.ubuntu.com/community/UFW
- Let's Encrypt: https://letsencrypt.org/docs/

### Geri Yükleme Prosedürü
```bash
# 1. Veritabanını geri yükle
gunzip /opt/backup-2026-08-27/seyfibaba-db-2026-08-27.sql.gz
mysql -u root -p'uFyyoiALFJ2CAXpG7PKp' seyfibabamarket < /opt/backup-2026-08-27/seyfibaba-db-2026-08-27.sql

# 2. Upload'ları geri yükle
tar -xzf /opt/backup-2026-08-27/seyfibaba-uploads-2026-08-27.tar.gz -C /opt/seyfibaba-main/backend/public/

# 3. Yetkileri ayarla
chown -R www-data:www-data /opt/seyfibaba-main/backend/storage/
chmod -R 750 /opt/seyfibaba-main/backend/storage/

# 4. Cache'leri temizle
cd /opt/seyfibaba-main/backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

**Bu doküman otomatik olarak oluşturulmuştur. Son güncelleme: 27 Ağustos 2026**
