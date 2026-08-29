# SEYFIBABA - Deployment (Yayınlama) Rehberi

**Son Güncelleme:** 27 Ağustos 2026  
**Sunucu:** 45.138.183.101  
**Domain:** seyfibaba.com

---

## İÇİNDEKİLER

1. [Genel Akış](#1-genel-akış)
2. [Kodu Sunucuya Aktarma](#2-kodu-sunucuya-aktarma)
3. [Backend Deployment](#3-backend-deployment)
4. [Frontend Deployment](#4-frontend-deployment)
5. [Nginx Config Değişikliği](#5-nginx-config-değişikliği)
6. [Hızlı Deployment (Tümü)](#6-hızlı-deployment-tümü)
7. [Deployment Sonrası Kontrol](#7-deployment-sonrası-kontrol)
8. [Sık Yapılan Hatalar](#8-sık-yapılan-hatalar)
9. [Geri Alma (Rollback)](#9-geri-alma-rollback)
10. [Deployment Kontrol Listesi](#10-deployment-kontrol-listesi)

---

## 1. GENEL AKIŞ

```
Bilgisayarında kodu değiştir
        ↓
Git ile push et veya dosyaları aktar
        ↓
Sunucuda pull/al
        ↓
Bağımlılıkları güncelle
        ↓
Build al
        ↓
Hizmetleri yeniden başlat
        ↓
Kontrol et
```

---

## 2. KODU SUNUCUYA AKTARMA

### Yöntem 1: Git ile (Önerilen)

```bash
# Bilgisayarında (değişikliklerden sonra)
git add .
git commit -m "Açıklama"
git push origin main

# Sunucuda
cd /opt/seyfibaba-main
git pull origin main
```

### Yöntem 2: SCP ile (Git yoksa)

```bash
# Bilgisayarından terminal ile
scp -r ./backend/* root@45.138.183.101:/opt/seyfibaba-main/backend/
scp -r ./frontend/* root@45.138.183.101:/opt/seyfibaba-main/frontend/
```

### Yöntem 3: SFTP ile (WinSCP, FileZilla vb.)

```
Sunucu: 45.138.183.101
Kullanıcı: root
Port: 22

Uzak dizin: /opt/seyfibaba-main/
```

### Yöntem 4: rsync ile (Büyük projeler için)

```bash
# Bilgisayarından
rsync -avz --progress ./backend/ root@45.138.183.101:/opt/seyfibaba-main/backend/
rsync -avz --progress ./frontend/ root@45.138.183.101:/opt/seyfibaba-main/frontend/
```

---

## 3. BACKEND DEPLOYMENT

### Adım Adım

```bash
# 1. Backend dizinine geç
cd /opt/seyfibaba-main/backend

# 2. Git'ten pull et
git pull origin main

# 3. Bağımlılık değişikliği varsa
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

# 4. Migration varsa
php artisan migrate --force

# 5. Seed varsa (dikkatli kullan)
php artisan db:seed --force

# 6. Config cache yenile
php artisan config:cache

# 7. Route cache yenile
php artisan route:cache

# 8. View cache yenile
php artisan view:cache

# 9. Storage linki kontrol et
php artisan storage:link

# 10. Dosya yetkilerini ayarla
chown -R www-data:www-data storage/ bootstrap/cache/
chmod -R 750 storage/ bootstrap/cache/
```

### Sadece Kod Değişikliği (Cache Yeterli)

```bash
cd /opt/seyfibaba-main/backend
git pull origin main
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Yeni Package Eklendiyse

```bash
cd /opt/seyfibaba-main/backend
git pull origin main
COMPOSER_ALLOW_SUPERUSER=1 composer update
php artisan config:cache
php artisan route:cache
```

### .env Değişikliği

```bash
# .env'i elle düzenle
nano /opt/seyfibaba-main/backend/.env

# Veya değişiklikleri doğrudan uygula
sed -i 's/ESKI_DEGER/YENI_DEGER/' .env

# Cache temizle
php artisan config:cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

> ⚠️ **ÖNEMLİ:** `.env` dosyası Git'e push edilmez! Sunucuda elle düzenlenir.

### PHP-FPM Yeniden Başlatma (php.ini değiştiyse)

```bash
systemctl restart php8.3-fpm
systemctl status php8.3-fpm
```

---

## 4. FRONTEND DEPLOYMENT

### Adım Adım

```bash
# 1. Frontend dizinine geç
cd /opt/seyfibaba-main/frontend

# 2. Git'ten pull et
git pull origin main

# 3. Bağımlılık değişikliği varsa
npm install --legacy-peer-deps

# 4. Production build al
npm run build

# 5. PM2'yi yeniden başlat
pm2 restart sey-frontend

# 6. Durum kontrol
pm2 list
```

### Sadece Kod Değişikliği (Build Yeterli)

```bash
cd /opt/seyfibaba-main/frontend
git pull origin main
npm run build
pm2 restart sey-frontend
```

### Yeni Package Eklendiyse

```bash
cd /opt/seyfibaba-main/frontend
git pull origin main
npm install --legacy-peer-deps
npm run build
pm2 restart sey-frontend
```

### .env.local Değişikliği

```bash
# .env.local'i elle düzenle
nano /opt/seyfibaba-main/frontend/.env.local

# Build al (önemli!)
npm run build
pm2 restart sey-frontend
```

### Frontend Log Kontrolü

```bash
# Son logları gör
pm2 logs sey-frontend --lines 50

# İzleme modu
pm2 logs sey-frontend
```

---

## 5. NGINX CONFIG DEĞİŞİKLİĞİ

```bash
# 1. Config'i düzenle
nano /etc/nginx/sites-available/seyfibaba

# 2. Yapılandırma testi (ZORUNLU!)
nginx -t

# 3. Test başarılıysa yeniden yükle
systemctl reload nginx

# 4. Test başarısızsa düzelt
# Hata mesajını oku, düzelt, tekrar test et
nginx -t
```

> ⚠️ **DİKKAT:** `nginx -t` başarısız olursa `reload` yapma! Mevcut sunucu çökebilir.

---

## 6. HIZLI DEPLOYMENT (TÜMÜ)

### Backend + Frontend Birlikte

```bash
cd /opt/seyfibaba-main && \
git pull origin main && \
cd backend && \
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
chown -R www-data:www-data storage/ bootstrap/cache/ && \
cd ../frontend && \
npm install --legacy-peer-deps && \
npm run build && \
pm2 restart sey-frontend && \
echo "✅ Deployment tamamlandı!"
```

### Migration ile

```bash
cd /opt/seyfibaba-main && \
git pull origin main && \
cd backend && \
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader && \
php artisan migrate --force && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
chown -R www-data:www-data storage/ bootstrap/cache/ && \
cd ../frontend && \
npm install --legacy-peer-deps && \
npm run build && \
pm2 restart sey-frontend && \
echo "✅ Deployment (migration ile) tamamlandı!"
```

### Tek Komut Alias'ı

```bash
# ~/.bashrc'ye ekle
alias deploy='cd /opt/seyfibaba-main && git pull origin main && cd backend && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader && php artisan config:cache && php artisan route:cache && php artisan view:cache && chown -R www-data:www-data storage/ bootstrap/cache/ && cd ../frontend && npm install --legacy-peer-deps && npm run build && pm2 restart sey-frontend && echo "✅ Deployment tamamlandı!"'

# Kullanım
deploy
```

---

## 7. DEPLOYMENT SONRASI KONTROL

### Hizmet Durumları

```bash
# Tüm hizmetleri kontrol et
echo "=== HİZMET DURUMLARI ==="
echo "MySQL:   $(systemctl is-active mysql)"
echo "Nginx:   $(systemctl is-active nginx)"
echo "PHP-FPM: $(systemctl is-active php8.3-fpm)"
echo "PM2:     $(pm2 list 2>&1 | grep sey-frontend | awk '{print $18}')"
```

### Site Erişim Testi

```bash
# Ana site
curl -sI https://seyfibaba.com | head -1
# Beklenen: HTTP/2 200

# Admin panel
curl -sI https://admin.seyfibaba.com | head -1
# Beklenen: HTTP/2 302

# API
curl -s https://seyfibaba.com/api/website-setup | head -c 50
# Beklenen: JSON verisi
```

### Nginx Log Kontrolü

```bash
# Son hatalar
tail -10 /var/log/nginx/error.log

# Erişim logları
tail -10 /var/log/nginx/access.log
```

### Laravel Log Kontrolü

```bash
# Son hatalar
tail -20 /opt/seyfibaba-main/backend/storage/logs/laravel.log
```

---

## 8. SIK YAPILAN HATALAR

### 1. "502 Bad Gateway" Hatası

**Neden:** PHP-FPM çöktü veya socket yok.

**Çözüm:**
```bash
systemctl status php8.3-fpm
ls -la /run/php/php-fpm.sock
systemctl restart php8.3-fpm
```

### 2. "504 Gateway Timeout" Hatası

**Neden:** Next.js PM2 process'i çöktü.

**Çözüm:**
```bash
pm2 list
pm2 restart sey-frontend
```

### 3. "Connection Refused" Hatası

**Neden:** Nginx durdu.

**Çözüm:**
```bash
systemctl status nginx
nginx -t
systemctl restart nginx
```

### 4. "Class not found" Hatası

**Neden:** Composer autoload yenilenmedi.

**Çözüm:**
```bash
cd /opt/seyfibaba-main/backend
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload
php artisan config:cache
```

### 5. "Route not defined" Hatası

**Neden:** Route cache eski.

**Çözüm:**
```bash
cd /opt/seyfibaba-main/backend
php artisan route:clear
php artisan route:cache
```

### 6. "View not found" Hatası

**Neden:** View cache eski.

**Çözüm:**
```bash
cd /opt/seyfibaba-main/backend
php artisan view:clear
php artisan view:cache
```

### 7. "Permission denied" Hatası

**Neden:** Dosya izinleri yanlış.

**Çözüm:**
```bash
chown -R www-data:www-data /opt/seyfibaba-main/backend/storage/
chmod -R 750 /opt/seyfibaba-main/backend/storage/
```

### 8. Build Hatası (Frontend)

**Neden:** Node modules eski veya bozuk.

**Çözüm:**
```bash
cd /opt/seyfibaba-main/frontend
rm -rf node_modules .next
npm install --legacy-peer-deps
npm run build
```

### 9. Migration Hatası

**Neden:** Migration zaten uygulanmış veya çakışma var.

**Çözüm:**
```bash
php artisan migrate:status
php artisan migrate:rollback
php artisan migrate
```

### 10. Cache Hatası

**Neden:** Cache dosyaları bozuk.

**Çözüm:**
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 9. GERİ ALMA (ROLLBACK)

### Git ile Geri Alma

```bash
cd /opt/seyfibaba-main

# Son commit'i geri al
git revert HEAD
git push origin main

# Belirli bir commit'e dön
git log --oneline -10  # Commit hash'lerini gör
git checkout <commit-hash>
git push origin main --force
```

### Migration Geri Alma

```bash
cd /opt/seyfibaba-main/backend

# Son 1 migration'ı geri al
php artisan migrate:rollback

# Son 5 migration'ı geri al
php artisan migrate:rollback --step=5

# Tüm migration'ları geri al (DİKKAT!)
php artisan migrate:reset
```

### Veritabanı Geri Alma

```bash
# Yedeği bul
ls -la /opt/backup-*/

# Veritabanını geri yükle
gunzip /opt/backup-2026-08-27/seyfibaba-db-2026-08-27.sql.gz
mysql -u root -p'uFyyoiALFJ2CAXpG7PKp' seyfibabamarket < /opt/backup-2026-08-27/seyfibaba-db-2026-08-27.sql
```

### PM2 Geri Alma

```bash
# Önceki build'i kullan (eğer .next dizini duruyorsa)
pm2 restart sey-frontend

# Veya eski node_modules ile yeniden build
cd /opt/seyfibaba-main/frontend
git checkout HEAD~1 -- .
npm install --legacy-peer-deps
npm run build
pm2 restart sey-frontend
```

---

## 10. DEPLOYMENT KONTROL LİSTESİ

### Deployment Öncesi
- [ ] Kod değişiklikleri Git'e commit edildi
- [ ] Push yapıldı (git push origin main)
- [ ] .env/.env.local değişikliği varsa not alındı
- [ ] Migration dosyası varsa test edildi
- [ ] Yeni package eklendiyse test edildi

### Deployment Sırasında
- [ ] Sunucuda `git pull` yapıldı
- [ ] Composer install çalıştırıldı (backend değiştiyse)
- [ ] Migration uygulandı (yeni migration varsa)
- [ ] Cache'ler yenilendi
- [ ] npm install çalıştırıldı (frontend değiştiyse)
- [ ] Build alındı
- [ ] Hizmetler yeniden başlatıldı

### Deployment Sonrası
- [ ] `nginx -t` başarılı
- [ ] `systemctl status mysql nginx php8.3-fpm` aktif
- [ ] `pm2 list` online
- [ ] https://seyfibaba.com erişilebilir (200)
- [ ] https://admin.seyfibaba.com erişilebilir (302→login)
- [ ] API çalışıyor (JSON dönüyor)
- [ ] Hata logları temiz

---

## HIZLI REFERANS

| İşlem | Komut |
|-------|-------|
| Git pull | `cd /opt/seyfibaba-main && git pull origin main` |
| Backend cache | `cd /opt/seyfibaba-main/backend && php artisan config:cache && php artisan route:cache && php artisan view:cache` |
| Frontend build | `cd /opt/seyfibaba-main/frontend && npm run build` |
| PM2 restart | `pm2 restart sey-frontend` |
| PHP-FPM restart | `systemctl restart php8.3-fpm` |
| Nginx reload | `systemctl reload nginx` |
| Yetki düzelt | `chown -R www-data:www-data /opt/seyfibaba-main/backend/storage/` |
| Log kontrol | `pm2 logs sey-frontend` |
| Hata log | `tail -f /opt/seyfibaba-main/backend/storage/logs/laravel.log` |
| Tüm hizmet durumu | `systemctl status mysql nginx php8.3-fpm && pm2 list` |

---

**Bu rehber otomatik olarak oluşturulmuştur. Son güncelleme: 27 Ağustos 2026**
