# BACKEND APP

**Generated:** 2026-04-15
**Parent:** ../AGENTS.md

## OVERVIEW
Laravel 10 backend with 109 Eloquent models, service layer, and REST API.

## STRUCTURE
```
app/
├── Models/        # 109 models (User, Order, Product, Vendor, etc.)
├── Http/Controllers/
│   ├── Admin/      # Admin panel controllers
│   ├── WEB/       # Web controllers
│   └── User/      # User-facing controllers
├── Services/     # IyzicoService, SmsService, etc.
├── Providers/    # AppServiceProvider, RouteServiceProvider
├── Helpers/     # Utility classes
├── Library/     # Third-party integrations
├── Mail/        # Email templates
├── Notifications/
├── Events/      # Event classes
├── Exceptions/  # Custom exceptions
└── Rules/      # Validation rules
```

## WHERE TO LOOK
| Task | Location |
|------|----------|
| Core Models | app/Models/ |
| Payment | app/Services/IyzicoService.php |
| Controllers | app/Http/Controllers/ |
| Auth | app/Http/Controllers/User/AuthController.php |

## CONVENTIONS
- Models: Laravel Eloquent
- Services: Repository pattern
- Controllers: Resourceful or custom actions
- Middleware: Auth, Admin, Seller roles

## ANTI-PATTERNS
- DO NOT use raw SQL (use Eloquent)
- DO NOT skip validation
