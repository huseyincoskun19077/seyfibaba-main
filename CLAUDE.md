# CLAUDE.md — Shopo (Seyfibaba Pazaryeri)

## Proje Ozeti
CodeCanyon Shopo temasina dayali Laravel 10 + Next.js 15 e-ticaret pazaryeri.
Musteri: Seyfibaba — Turkiye odakli pazaryeri platformu.

## Stack
- **Backend:** Laravel 10, PHP 8.x, MySQL, Composer
- **Frontend:** Next.js 15, React, Tailwind CSS, Redux Toolkit
- **Odeme:** Iyzico (pazaryeri modeli), mevcut: Stripe/PayPal/Razorpay
- **SMS:** Iletimerkezi veya Netgsm (Turkiye icin)
- **Hosting:** VPS, Nginx, PM2

## Portlar
- Frontend: `localhost:3000`
- Backend (Laravel): `localhost:8000`
- Admin Panel: `localhost:8000/admin`

## Komutlar
```bash
# Backend
cd backend && composer install && php artisan serve

# Frontend
cd frontend && bun install && bun run dev
```

## Orkestrasyon Kurallari (Shopo-ozel)
- Claude Code: Mimari plan, guvenlik audit, API kontrat tasarimi
- Codex: Kod yazma (controller, model, migration, component)
- Antigravity: UI dogrulama, Lighthouse, form test, auth akisi
- Copilot: Typo fix, import duzeltme, boilerplate

## Kritik Notlar
- `.env` dosyalari commit edilmez
- SQL dump'lar `.gitignore`'da
- Iyzico entegrasyonu **pazaryeri modeli** olmali (sub-merchant destegi)
- SMS sadece kayit OTP icin, siparis bildirimi degil
- SEO uzmani tavsiyeleri YAPILACAKLAR.md #3'te detayli
