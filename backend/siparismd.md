# Sipariş Yönetim Sistemi Analizi

## 📊 MEVCUT DURUM

### 🔢 Mevcut Order Status Değerleri

| Değer | Durum (TR) | Durum (EN) | Açıklama |
|-------|-----------|-----------|----------|
| 0 | PENDING | PENDING | Beklemede - Sipariş verildi, ödeme beklentide |
| 1 | IN_PROGRESS | IN_PROGRESS | Hazırlanıyor - Satıcı onayladı, ürün hazırlanıyor |
| 2 | DELIVERED | DELIVERED | Teslim Edildi - Kargoya verildi/teslim alındı |
| 3 | COMPLETED | COMPLETED | Tamamlandı - Müşteri onayladı |
| 4 | DECLINED | DECLINED | Reddedildi - Sipariş reddedildi |

---

## ✅ OLAN ÖZELLİKLER

### 1. Temel Sipariş Akışı
- [x] Sipariş oluşturma (PaymentController, CheckoutController)
- [x] Ödeme kontrolü (IyzicoService)
- [x] Sepet yönetimi
- [x] Adres yönetimi
- [x] Kargo yönetimi

### 2. Satıcı Sipariş Yönetimi
- [x] Satıcı sipariş listesi (SellerOrderController)
- [x] Onay bekleyen siparişler (pendingOrder)
- [x] İşlemdeki siparişler (progressOrder)
- [x] Teslim edilen siparişler (deliveredOrder)
- [x] Tamamlanan siparişler (completedOrder)
- [x] Reddedilen siparişler (declinedOrder)

### 3. Admin Sipariş Yönetimi
- [x] Tüm siparişleri listeleme
- [x] Durum güncelleme
- [x] Silme

### 4. Banka Havalesi Desteği
- [x] Bank transfer ödeme seçeneği
- [x] %3 indirim hesaplama
- [x] Admin onay paneli
- [x] Onay.maili gönderimi

### 5. Iyzico Entegrasyonu
- [x] IyzicoService (app/Services)
- [x] Ödeme sayfası oluşturma
- [x] Callback handling
- [x] Marketplace mode desteği

### 6. Temel Mail Bildirimleri
- [x] Sipariş oluşturulduğunda müşteriye mail
- [x] Bank transfer onaylandığında mail

### 7. Otomatik Tamamlama
- [x] AutoCompleteOrders command (cron job)
- [x] 7 gün sonra otomatik tamamlama

---

## ❌ EKSİK ÖZELLİKLER

### 1. State Machine Yapısı
- [ ] Strict state transition kontrollü yok
- [ ] Invalid geçişler engellenmiyor
- [ ] State machine class/modeli yok

### 2. Yeni Status Değerleri Gerekli
| # | Status | Açıklama | Gerekli |
|---|--------|----------|--------|
| 5 | SHIPPED | Kargoya verildi, takip numarası girildi | ✓ |
| 6 | RETURN_REQUESTED | Müşteri iade istedi | ✓ |
| 7 | IN_REVIEW | Admin/iade incelemesi | ✓ |
| 8 | REFUNDED | İade edildi | ✓ |

### 3. Satıcı Özellikleri Eksik
- [ ] Satıcı → sipariş reddetme (order_status = 4)
- [ ] Satıcı → kargo takip numarası girme
- [ ] Satıcı → "kargoya ver" butonu (SHIPPED durumu)
- [ ] Satıcıya yeni siparişte mail bildirimi yok

### 4. Müşteri Özellikleri Eksik
- [ ] Müşteri → siparişi onayla (COMPLETED)
- [ ] Müşteri → iade talebi (RETURN_REQUESTED)
- [ ] Müşteriye onay maili yok
- [ ] Müşteriye kargoya verildi maili yok
- [ ] Müşteriye tamamlandı maili yok

### 5. Return/Refund Akışı Eksik
- [ ] Return talebi modeli yok
- [ ] Return onay/red akışı yok
- [ ] Admin inceleme yok
- [ ] İyzico refund entegrasyonu yok

### 6. İyzico Marketplace Eksiklikleri
- [ ] Submerchant payout (satıcıya ödeme)
- [ ] Marketplace escrow yönetimi
- [ ] Komisyon hesaplama
- [ ] Otomatik refund

### 7. Bildirim Sistemi Eksiklikleri

#### Mevcut Mailler:
| Tetikleyici | Mevcut | Hedef |
|-----------|--------|-------|
| Sipariş oluştu | ✅ Var | Müşteri |
| Bank transfer onaylandı | ✅ Var | Müşteri |

#### Eksik Mailler:
| Tetikleyici | Durum | Hedef |
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

### 8. Event/Audit Logging Eksik
- [ ] Sipariş event log tutulmuyor
- [ ] Audit trail yok
- [ ] Kim, neyi, ne zaman kaydetmiyor

### 9. Webhook Eksiklikleri
- [ ] İyzico webhook tam handling yok
- [ ] Retry mekanizması eksik
- [ ] Idempotent kontrol eksik

### 10. Kargo Entegrasyonu
- [ ] GDelivery entegrasyonu var ama kullanımı belirsiz
- [ ] Takip numarası zorunluluğu yok

---

## 🎯 ÖNERİLEN YAPILMASI GEREKENLER

### Öncelik 1 (Critical):
1. State Machine class oluşturma
2. Yeni status değerleri ekleme (5,6,7,8)
3. Satıcı → kargoya ver (SHIPPED)
4. Return/Refund akışı
5. Mail bildirimleri

### Öncelik 2 (Important):
6. İyzico marketplace payout
7. Event logging
8. Retry mekanizması

### Öncelik 3 (Nice to have):
9. Fraud detection
10. Seller SLA tracking
11. Rating sistemi

---

## 📁 İLGİLİ DOSYALAR

### Modeller:
- `app/Models/Order.php`
- `app/Models/OrderProduct.php`
- `app/Models/OrderAddress.php`
- `app/Models/OrderProductVariant.php`

### Controllerlar:
- `app/Http/Controllers/User/PaymentController.php` - Ödeme
- `app/Http/Controllers/Admin/OrderController.php` - Admin
- `app/Http/Controllers/WEB/Seller/SellerOrderController.php` - Satıcı
- `app/Http/Controllers/WEB/Admin/OrderController.php` - Web Admin

### Mailler:
- `app/Mail/OrderSuccessfully.php`
- `app/Mail/PaymentApprovedMail.php`

### Services:
- `app/Services/IyzicoService.php`
- `app/Services/SmsService.php`

### Commands:
- `app/Console/Commands/AutoCompleteOrders.php`

---

## 📋 ORDER STATE MACHINE (ÖNERİLEN)

```
PENDING (0)
    ↓ (Satıcı onaylarsa)
APPROVED (1) [yeni]
    ↓ (Kargoya verilirse)
SHIPPED (5) [yeni]
    ↓ (Teslim edilirse)
DELIVERED (2)
    ↓ (Müşteri onaylarsa veya 15 gün geçerse)
COMPLETED (3) / AUTO_COMPLETED

--- RETURN AKIŞI ---
DELIVERED (2)
    ↓ (Müşteri iade isterse)
RETURN_REQUESTED (6) [yeni]
    ↓ (Satıcı onaylarsa)
REFUNDED (8) [yeni]
    ↓ (Satıcı reddederse)
IN_REVIEW (7) [yeni]
    ↓ (Admin karar verirse)
REFUNDED (8) / COMPLETED (3)
```

---

*Bu dosya analiz amaçlıdır. Eksiklikler ve mevcut özellikler bu şekilde tespit edilmiştir.*