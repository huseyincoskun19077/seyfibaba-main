# Seyfibaba Pazaryeri

Turkiye odakli cok-saticiyi e-ticaret pazaryeri platformu. CodeCanyon Shopo temasina dayali, Laravel 10 + Next.js 15 stack.

## Stack

| Katman | Teknoloji |
|--------|-----------|
| Backend | Laravel 10, PHP 8.x, MySQL |
| Frontend | Next.js 15, React, Tailwind CSS, Redux Toolkit |
| Odeme | Iyzico (pazaryeri modeli, sub-merchant) |
| SMS | Iletimerkezi / Netgsm (OTP dogrulama) |
| AI | OpenAI, Claude, Groq (urun icerik uretimi) |
| Real-time | Pusher (bildirimler) |
| Harita | Google Maps API |
| Auth | JWT (tymon/jwt-auth) |

## Proje Yapisi

```
shopo/
  backend/          # Laravel 10 API + Admin/Seller panelleri
  frontend/         # Next.js 15 musteri web uygulamasi
  docs/             # Teknik dokumantasyon ve test raporlari
  tests/smoke/      # HTTP smoke test scriptleri
  scripts/          # Yardimci scriptler
```

## Hizli Baslangic

### Gereksinimler

- PHP >= 8.1, Composer
- Node.js >= 18, Bun (veya npm)
- MySQL 8.x

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# .env'de DB_DATABASE, DB_USERNAME, DB_PASSWORD ayarla

php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Backend: `http://localhost:8000`
Admin panel: `http://localhost:8000/admin`

### Frontend

```bash
cd frontend
bun install
bun run dev
```

Frontend: `http://localhost:3000`

Frontend'in backend'e baglanmasi icin `frontend/.env.local` dosyasinda `NEXT_PUBLIC_BASE_URL=http://localhost:8000` ayarlanmalidir.

## Temel Ozellikler

- **Pazaryeri Odeme** — Iyzico sub-merchant modeli ile otomatik komisyon bolme
- **AI Urun Icerigi** — Admin panelden yapilandirilan OpenAI/Claude/Groq ile baslik, aciklama, SEO metni olusturma
- **OTP Dogrulama** — Kayit sirasinda SMS ile telefon dogrulama
- **Satici Paneli** — KYC sureci, toplu urun import, stok uyarilari
- **Admin Paneli** — Urun, satici, siparis, kargo ve odeme yonetimi
- **Real-time Bildirimler** — Pusher ile anlik bildirimler
- **Harita Entegrasyonu** — Adres seciminde Google Maps
- **SEO** — Next.js SSR, meta tag yonetimi

## Ortam Degiskenleri

Backend `.env` icin detayli bilgi: [backend/README.md](backend/README.md)

Kritik ayarlar:
- `DB_*` — MySQL baglanti bilgileri
- `JWT_SECRET` — `php artisan jwt:secret`
- `SMS_PROVIDER` — `iletimerkezi` veya `netgsm`
- Iyzico, AI ve Google Maps ayarlari admin panelden yapilandirilir

## Test

```bash
# Backend unit/feature testleri
cd backend && php artisan test

# Smoke test (PHPUnit'siz ortamlar icin)
bash tests/smoke/marketplace-feature-smoke.sh http://localhost:8000
```

## Dokumantasyon

- [backend/README.md](backend/README.md) — Backend kurulum ve komutlar
- [docs/](docs/) — Teknik dokumantasyon ve test raporlari
- [CLAUDE.md](CLAUDE.md) — AI asistan talimatlari
- [AGENTS.md](AGENTS.md) — Codex agent talimatlari
- [YAPILACAKLAR.md](YAPILACAKLAR.md) — Gorev listesi

## Deployment

```bash
# Backend production cache
cd backend
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend production build
cd frontend
bun run build
pm2 start ecosystem.config.cjs --only sey-frontend   # ilk kurulum
pm2 restart sey-frontend                           # güncelleme sonrası
# veya: pm2 reload ecosystem.config.cjs --only sey-frontend
```

Sunucu: Nginx reverse proxy + PM2 (frontend) + PHP-FPM (backend)
