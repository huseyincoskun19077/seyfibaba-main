# Sipariş Yönetim Sistemi - Analiz Dokümanı

## Genel Bakış

Bu doküman, Seyfibaba Pazaryeri sipariş yönetim sisteminin mevcut durumunu ve eksikliklerini analiz eder. Sistem iyzico marketplace (submerchant) modeli ile entegre çalışmaktadır.

---

## 📊 MEVCUT DURUM

### 🔢 Mevcut Sipariş Status Değerleri

Sistem şu anda 5 farklı status değeri kullanmaktadır:

| Değer | Status (EN) | Status (TR) | Açıklama | Tarih Alanı |
|-------|------------|------------|----------|------------|
| 0 | PENDING | Beklemede | Sipariş verildi, ödeme beklentide | - |
| 1 | IN_PROGRESS | Hazırlanıyor | Satıcı onayladı, ürün hazırlanıyor | order_approval_date |
| 2 | DELIVERED | Teslim Edildi | Kargoya verildi / Teslim alındı | order_delivered_date |
| 3 | COMPLETED | Tamamlandı | Müşteri onayladı / Süre doldu | order_completed_date |
| 4 | DECLINED | Reddedildi | Sipariş reddedildi | order_declined_date |

**Önemli Not:** Status değerleri modelde tanımlı değil, doğrudan integer olarak veritabanında saklanıyor. PHP constant veya enum kullanılmıyor.

---

## ✅ OLAN ÖZELLİKLER

### 1. Temel Sipariş Akışı

- [x] Sipariş oluşturma (PaymentController, CheckoutController)
- [x] Ödeme kontrolü (IyzicoService)
- [x] Sepet yönetimi
- [x] Adres yönetimi
- [x] Kargo yönetimi

**İlgili Dosyalar:**
- `app/Http/Controllers/User/PaymentController.php`
- `app/Http/Controllers/User/CheckoutWithoutTokenController.php`
- `app/Http/Controllers/User/CheckoutController.php`

### 2. Satıcı Sipariş Yönetimi (SellerOrderController)

Satıcı panelinden sipariş yönetimi için endpoints:

| Metod | Endpoint | Status Filter | Açıklama |
|-------|---------|-------------|----------|
| index | /seller/orders | Tümü | Tüm siparişler |
| pendingOrder | /seller/orders/pending | 0 | Bekleyen siparişler |
| pregressOrder | /seller/orders/pregress | 1 | İşlemdeki siparişler (typo: pregress) |
| deliveredOrder | /seller/orders/delivered | 2 | Teslim edilenler |
| completedOrder | /seller/orders/completed | 3 | Tamamlananlar |
| declinedOrder | /seller/orders/declined | 4 | Reddedilenler |

**Satıcı Status Güncelleme Akışı:**
```
updateOrderStatus() metodu ile:
- 0 (Pending) -> 1 (Shipped/Approved)
- 1 (Shipped) -> 2 (Delivered)

Kontrol: order_status parametresi sadece 1 veya 2 olabilir (strict)
```

**İlgili Dosyalar:**
- `app/Http/Controllers/WEB/Seller/SellerOrderController.php`

### 3. Admin Sipariş Yönetimi

- [x] Tüm siparişleri listeleme
- [x] Durum güncelleme (updateOrderStatus)
- [x] Sipariş silme
- [x] Detay görüntüleme

**İlgili Dosyalar:**
- `app/Http/Controllers/Admin/OrderController.php`
- `app/Http/Controllers/WEB/Admin/OrderController.php`

### 4. Banka Havalesi Desteği

- [x] Bank transfer ödeme seçeneği
- [x] %3 indirim hesaplama (settings.bank_transfer_discount_percent)
- [x] Admin onay paneli (/admin/bank-transfer-pending)
- [x] Onay maili gönderimi (PaymentApprovedMail)

**İlgili Dosyalar:**
- `app/Http/Controllers/User/PaymentController.php` (payWithBank)
- `app/Http/Controllers/User/CheckoutWithoutTokenController.php`
- `app/Http/Controllers/WEB/Admin/OrderController.php` (approvePayment)
- `app/Mail/PaymentApprovedMail.php`

### 5. İyzico Entegrasyonu

#### 5.1 IyzicoService (`app/Services/IyzicoService.php`)

**Temel Metodlar:**
- `getConfig()` - İyzico config yükleme
- `options()` - Iyzipay Options oluşturma
- `createCheckoutForm(array $data)` - Ödeme sayfası oluşturma
- `retrieveCheckoutForm(string $token, string $conversationId)` - Ödeme doğrulama
- `refund(string $paymentTransactionId, float $amount, string $conversationId)` - İade

**Marketplace/Submerchant Metodları:**
- `createSubMerchant(array $data)` - Submerchant oluşturma
- `updateSubMerchant(array $data)` - Submerchant güncelleme
- `retrieveSubMerchant(string $subMerchantExternalId)` - Submerchant bilgisi

**Helper Metodları:**
- `makeBuyer(array $payload)` - Alıcı bilgisi oluşturma
- `makeAddress(array $payload)` - Adres oluşturma
- `makeBasketItems(array $items)` - Sepet kalemleri oluşturma

#### 5.2 IyzicoController (`app/Http/Controllers/User/IyzicoController.php`)

**Endpointler:**
- `createCheckoutSession()` - Girişli kullanıcı ödeme
- `createGuestCheckoutSession()` - Misafir ödeme
- `callback()` - İyzico webhook/callback

**Marketplace Desteği:**
- marketplace_mode aktifse, per-item sub_merchant_key ekleniyor
- Fallback: Tüm ürünlerde key yoksa standard moda geçiyor

#### 5.3 Ödeme Akışı

```
1. Kullanıcı checkout'a gider
2. createCheckoutSession() → IyzicoService.createCheckoutForm()
3. İyzico ödeme sayfası açılır
4. Ödeme sonrası callback() tetiklenir
5. retrieveCheckoutForm() ile doğrulama
6. Order Güncelleme: payment_status = 1
```

#### 5.4 İyzico Refund

- `IyzicoService::refund()` metodu mevcut
- Migration var: `2026_03_27_120000_add_iyzico_refund_support.php`
- **Not:** UI'da manuel tetikleme yok, kodda kullanılmıyor

**İlgili Dosyalar:**
- `app/Services/IyzicoService.php`
- `app/Http/Controllers/User/IyzicoController.php`
- `app/Models/IyzicoPayment.php`
- `database/migrations/2026_03_27_120000_add_iyzico_refund_support.php`

### 6. Otomatik Tamamlama

- [x] AutoCompleteOrders command (cron job)
- [x] 7 gün sonra otomatik tamamlama

**İlgili Dosyalar:**
- `app/Console/Commands/AutoCompleteOrders.php`

---

## ❌ EKSİK ÖZELLİKLER

### 1. State Machine Yapısı

**Mevcut Durum:**
- Strict transition kontrolü YOK
- Validation sadece seller tarafında (1 veya 2)
- Admin her status'e geçirebiliyor
- Invalid geçişler engellenmiyor

**Eksik:**
- [ ] StateMachine class/modeli
- [ ] Strict transition kuralları
- [ ] Event logging

### 2. Yeni Status Değerleri

Gerekli yeni status değerleri:

| # | Status | Açıklama | Gereken Alanlar |
|---|--------|----------|--------------|
| 5 | SHIPPED | Kargoya verildi, takip numarası girildi | cargo_tracking_number, courier |
| 6 | RETURN_REQUESTED | Müşteri iade istedi | return_reason, return_request_date |
| 7 | IN_REVIEW | Admin incelemesi | admin_review_date, review_notes |
| 8 | REFUNDED | İade edildi | refund_date, refund_amount |

### 3. Satıcı Eksiklikleri

- [ ] Satıcı → sipariş reddetme (status = 4)
- [ ] Satıcı → kargo takip numarası girme (SHIPPED)
- [ ] Satıcı → "kargoya ver" butonu
- [ ] Satıcıya yeni siparişte mail bildirimi (YOK)

### 4. Müşteri Eksiklikleri

- [ ] Müşteri → siparişi onayla (COMPLETED - manual)
- [ ] Müşteri → iade talebi (RETURN_REQUESTED)
- [ ] Müşteri onay maili (YOK)
- [ ] Müşteriye kargoya verildi maili (YOK)
- [ ] Müşteriye tamamlandı maili (YOK)

### 5. İade/İade Akışı Eksik

- [ ] ReturnRequest modeli
- [ ] Return talebi oluşturma
- [ ] Satıcı → iade onay/red
- [ ] Admin → iade inceleme
- [ ] İyzico refund tetikleme
- [ ] Return mailleri

### 6. İyzico Marketplace Eksiklikleri

- [ ] **Submerchant payout** (satıcıya ödeme) - YOK
- [ ] Platform komisyon hesaplama - YOK
- [ ] Escrow yönetimi - YOK
- [ ] Otomatik payout - YOK

### 7. Bildirim Sistemi

#### Mevcut Mailler:

| Tetikleyici | Mevcut | Giden | Alıcı |
|------------|--------|-------|-------|
| Sipariş oluştu | ✅ Var | "Siparişiniz alındı" | Müşteri |
| Bank transfer onaylandı | ✅ Var | "Ödemeniz onaylandı" | Müşteri |

#### Eksik Mailler:

| Tetikleyici | Durum | Alıcı |
|-----------|------|-------|
| Satıcı onayladı | ❌ Yok | Müşteri |
| Satıcı reddetti | ❌ Yok | Müşteri |
| Kargoya verildi | ❌ Yok | Müşteri |
| Teslim edildi | ❌ Yok | Müşteri |
| Tamamlandı | ❌ Yok | Müşteri, Satıcı |
| İade istendi | ❌ Yok | Satıcı |
| İade onaylandı | ❌ Yok | Müşteri |
| İade reddedildi | ❌ Yok | Müşteri |
| Yeni sipariş | ❌ Yok | Satıcı |

### 8. Webhook Eksiklikleri

- [ ] İyzico webhook full handling (sadece callback var)
- [ ] Retry mekanizması
- [ ] Idempotent kontrol
- [ ] Webhook logging

### 9. Event/Audit Logging

- [ ] Sipariş event log tutulmuyor
- [ ] Audit trail yok
- [ ] Kim, neyi, ne zaman

---

## 📋 ÖNERİLEN ORDER STATE MACHINE

```
SİPARİŞ AKIŞI (ÖNERİLEN):

PENDING (0)
    │
    ├─[Satıcı Onaylar]──> APPROVED (1) [YENİ]
    │                        │
    │                        ├─[Kargoya Ver]──> SHIPPED (5) [YENİ]
    │                        │                    │
    │                        │                    └─[Teslim]──> DELIVERED (2)
    │                        │                              │
    │                        │                    [15 gün bekle] veya
    │                        │                    [Müşteri Onaylar]
    │                        │                        │
    │                        │                        ├─[Timeout]──> AUTO_COMPLETED
    │                        │                        │
    │                        │                        └─[Müşteri Onay]──> COMPLETED (3)
    │                        │
    │                        └─[Satıcı Reddeder]──> CANCELLED (9) [YENİ]
    │
    └─[Ödeme Timeout]──> CANCELLED (9)

--- RETURN AKIŞI ---

DELIVERED (2)
    │
    └─[Müşteri İade İster]──> RETURN_REQUESTED (6) [YENİ]
                                │
                                ├─[Satıcı Onaylar]──> REFUNDED (8) [YENİ]
                                │                        │
                                │                        └─> İyzico refund tetikle
                                │
                                └─[Satıcı Reddeder]──> IN_REVIEW (7) [YENİ]
                                                      │
                                                      ├─[Admin Onaylar]──> REFUNDED (8)
                                                      │
                                                      └─[Admin Reddeder]──> COMPLETED (3)
```

---

## 🎯 YAPILMASI GEREKENLER (Öncelik Sırası)

### Öncelik 1 (Critical):

1. State Machine class oluşturma
2. Yeni status değerleri (5,6,7,8,9) ekleme
3. Satıcı → kargoya ver (SHIPPED) + takip no
4. Satıcı → sipariş reddetme
5. Return/Refund akışı
6. Mail bildirimleri (9 adet)

### Öncelik 2 (Important):

7. İyzico marketplace payout
8. Komisyon hesaplama
9. Event logging
10. Retry mekanizması

### Öncelik 3 (Nice to have):

11. Fraud detection
12. Seller SLA tracking
13. Rating sistemi
14. Push bildirimleri

---

## 📁 İLGİLİ DOSYALAR

### Modeller:
- `app/Models/Order.php`
- `app/Models/OrderProduct.php`
- `app/Models/OrderAddress.php`
- `app/Models/IyzicoPayment.php`

### Controllerlar:
- `app/Http/Controllers/User/PaymentController.php`
- `app/Http/Controllers/User/IyzicoController.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `app/Http/Controllers/WEB/Admin/OrderController.php`
- `app/Http/Controllers/WEB/Seller/SellerOrderController.php`

### Mailler:
- `app/Mail/OrderSuccessfully.php`
- `app/Mail/PaymentApprovedMail.php`

### Services:
- `app/Services/IyzicoService.php`
- `app/Services/SmsService.php`

### Commands:
- `app/Console/Commands/AutoCompleteOrders.php`

---

*Bu doküman analiz amaçlıdır. Mevcut ve eksik özellikler bu şekilde tespit edilmiştir.*

**Son Güncelleme:** 2026-04-20