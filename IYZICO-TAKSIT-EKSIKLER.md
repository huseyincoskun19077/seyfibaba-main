# Iyzico Taksit — Eksik Kısımlar

Sistemde zaten vardı; sandbox testinde eklenen / düzeltilen sadece şunlar:

---

## 1. Ana değişiklik — `CategoryInstallmentService.php`

**Dosya:** `backend/app/Services/CategoryInstallmentService.php`

Eksik olan: Kategori → Iyzico taksit + basket eşlemesi (sadece mobilya değil, tüm kategoriler).

Bu dosyada yapılanlar:

- `IYZICO_RULES` tablosu (kozmetik, tablet, telefon, mobilya, dayanıklı/küçük ev aletleri)
- `classifyRule()` — kategori adına göre kural
- `resolveIyzicoCategory()` — basket `category_1` / `category_2` (mobilya artık `Mobilya` / `Ev Mobilyalari`)
- `maxInstallmentForProduct()` — alt kategori `0` ise ana kategoriye düş
- `enabledInstallmentsForCart()` — sepette en kısıtlayıcı ürün
- Mobilya ana kategori + alt isimde "ekipman" → yine Mobilya kalsın

Bu dosyanın tamamını diğer ortama kopyalayın veya `main`'den alın.

---

## 2. Seeder güncellemesi

**Dosya:** `backend/database/seeders/IyzicoInstallmentSeeder.php`

Eksik: Mobilya kuralı yorumu + `CategoryInstallmentService::suggestedInstallmentsByCategoryName()` ile DB senkronu.

Çalıştırın:

```bash
php artisan db:seed --class=IyzicoInstallmentSeeder --force
```

Güncellenen `categories.max_installment`:

| Kategori | Max Taksit |
|----------|------------|
| Kuaför Mobilyaları | 12 |
| Kuaför Malzemeleri | 9 |
| Kozmetik | 1 |
| Kuaför Yedek Parçaları | 9 |
| Servis ve Boya Arabası | 9 |

---

## 3. Unit testler (opsiyonel)

**Dosya:** `backend/tests/Unit/Services/CategoryInstallmentServiceTest.php`

Eklenen testler: mobilya, yedek parça, `max_installment=0` fallback.

```bash
php artisan test --filter=CategoryInstallmentServiceTest
```

---

## 4. CORS (sadece local test için)

**Dosya:** `backend/config/cors.php`

Eksik satır:

```php
'http://127.0.0.1:3000',
```

Production'da şart değil; local'de `127.0.0.1:3000` kullanıyorsanız gerekli.

---

## Dokunulmayan / zaten vardı

- `IyzicoController.php` — zaten `CategoryInstallmentService` kullanıyordu
- `IyzicoService.php` — değişmedi
- Ödeme akışı, Checkout Form — zaten vardı

---

## Yapılacaklar özeti

1. `CategoryInstallmentService.php` dosyasını güncelle
2. `IyzicoInstallmentSeeder` çalıştır → DB `max_installment` senkronu
3. (Local test) `cors.php` → `127.0.0.1:3000` ekle
