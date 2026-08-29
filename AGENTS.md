# PROJECT KNOWLEDGE BASE

**Generated:** 2026-04-15
**Project:** Shopo (Seyfibaba Pazaryeri)
**Stack:** Laravel 10 + Next.js 15

## OVERVIEW
CodeCanyon Shopo temasına dayalı Laravel 10 + Next.js 15 e-ticaret pazaryeri.
Müşteri: Seyfibaba — Türkiye odaklı pazaryeri platformu.

## STRUCTURE
```
shopo/
├── backend/          # Laravel 10 (PHP 8.x)
│   ├── app/
│   │   ├── Models/     # 109 models
│   │   ├── Services/  # IyzicoService, etc.
│   │   ├── Http/Controllers/
│   │   └── Providers/
│   ├── routes/       # web.php (79KB), api.php (63KB)
│   ├── config/       # app.php, jwt.php, cart.php
│   └── database/
├── frontend/        # Next.js 15 (React, Redux, Tailwind)
│   ├── src/
│   │   ├── components/ # 41 components
│   │   ├── redux/
│   │   ├── hooks/
│   │   ├── utils/
│   │   └── app/
│   └── package.json
└── docs/
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| API Routes | backend/routes/api.php | Main REST endpoints |
| Controllers | backend/app/Http/Controllers/ | WEB, Admin, User, Seller |
| Models | backend/app/Models/ | 109 Eloquent models |
| Iyzico | backend/app/Services/IyzicoService.php | Payment integration |
| Frontend Pages | frontend/src/app/ | Next.js 15 routing |
| Redux Store | frontend/src/redux/ | State management |

## CODE MAP
| Symbol | Type | Location | Role |
|--------|------|----------|------|
| IyzicoController | Controller | backend/app/Http/Controllers/User/ | Payment handling |
| IyzicoService | Service | backend/app/Services/ | Iyzico API wrapper |
| Vendor | Model | backend/app/Models/ | Marketplace vendor |
| Order | Model | backend/app/Models/ | Order entity |
| CartItem | Model | backend/app/Models/ | Shopping cart |

## CONVENTIONS
- PHP: PSR-4 autoloading, Laravel conventions
- JS/React: Functional components, hooks
- Styling: Tailwind CSS
- Auth: JWT (jwt.php config)

## ANTI-PATTERNS (THIS PROJECT)
- DO NOT commit .env files
- DO NOT commit SQL dumps (in .gitignore)
- Iyzico must use marketplace mode (sub-merchant support)

## COMMANDS
```bash
# Backend
cd backend && composer install && php artisan serve

# Frontend  
cd frontend && bun install && bun run dev
```

## NOTES
- Iyzico marketplace mode enabled
- 3 sub_merchant_keys defined in vendors table
- Checkout: guest user supported
