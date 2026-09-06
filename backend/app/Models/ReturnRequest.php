<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 0;
    public const STATUS_SELLER_APPROVED = 1;
    public const STATUS_ADMIN_APPROVED = 2;
    public const STATUS_ITEM_RECEIVED = 3;
    public const STATUS_REFUNDED = 4;
    public const STATUS_SELLER_REJECTED = 5;
    public const STATUS_ADMIN_REJECTED = 6;
    public const STATUS_USER_CANCELLED = 7;

    public const PAYER_SELLER = 'seller';
    public const PAYER_BUYER = 'buyer';
    public const PAYER_PLATFORM = 'platform';

    protected $fillable = [
        'order_id',
        'user_id',
        'seller_id',
        'order_product_id',
        'reason',
        'details',
        'description',
        'status',
        'qty',
        'refund_amount',
        'refund_method',
        'return_address',
        'return_shipping_payer',
        'return_carrier_name',
        'return_cargo_code',
        'return_shipping_instructions',
        'buyer_return_carrier',
        'buyer_return_tracking_number',
        'buyer_return_tracking_url',
        'buyer_shipped_at',
        'vendor_response',
        'seller_note',
        'admin_response',
        'admin_note',
        'rejected_reason',
        'refund_transaction_id',
        'refund_status',
        'refund_error',
        'approved_at',
        'rejected_at',
        'refunded_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'refunded_at' => 'datetime',
        'buyer_shipped_at' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    protected $appends = [
        'refund_info',
        'refund_method_label',
    ];

    public function getRefundInfoAttribute(): ?string
    {
        return $this->buyerRefundInfoText();
    }

    public function getRefundMethodLabelAttribute(): string
    {
        return self::refundMethodLabel($this->refund_method);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_SELLER_APPROVED => 'Satıcı onayladı — yönetici bekleniyor',
            self::STATUS_ADMIN_APPROVED => 'Yönetici onayladı',
            self::STATUS_ITEM_RECEIVED => 'İade ürünü teslim alındı',
            self::STATUS_REFUNDED => 'İade tamamlandı (para iadesi yapıldı)',
            self::STATUS_SELLER_REJECTED => 'Satıcı reddetti',
            self::STATUS_ADMIN_REJECTED => 'Yönetici reddetti',
            self::STATUS_USER_CANCELLED => 'Müşteri iptal etti',
        ];
    }

    /** Alıcıya gösterilen Trendyol-benzeri durum metinleri */
    public static function buyerStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'İade talebi alındı — satıcı/yönetici inceliyor',
            self::STATUS_SELLER_APPROVED => 'Satıcı onayladı — ürünü iade adresine kargolayın',
            self::STATUS_ADMIN_APPROVED => 'İade onaylandı — kargo talimatını uygulayın',
            self::STATUS_ITEM_RECEIVED => 'İade ürünü satıcıya / depoya ulaştı',
            self::STATUS_REFUNDED => 'İade tamamlandı — para iadesi yapıldı',
            self::STATUS_SELLER_REJECTED => 'İade talebi reddedildi',
            self::STATUS_ADMIN_REJECTED => 'İade talebi reddedildi',
            self::STATUS_USER_CANCELLED => 'İade talebi iptal edildi',
        ];
    }

    public static function statusBadgeClass(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'warning text-dark',
            self::STATUS_SELLER_APPROVED, self::STATUS_ITEM_RECEIVED => 'info',
            self::STATUS_ADMIN_APPROVED => 'primary',
            self::STATUS_REFUNDED => 'success',
            self::STATUS_SELLER_REJECTED, self::STATUS_ADMIN_REJECTED => 'danger',
            self::STATUS_USER_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    public static function reasonLabel(?string $reason): string
    {
        $map = [
            'defective' => 'Arızalı ürün',
            'wrong_item' => 'Yanlış ürün geldi',
            'not_as_described' => 'Açıklamadaki gibi değil',
            'changed_mind' => 'Karar değişikliği',
            'damaged_in_shipping' => 'Kargoda hasar gördü',
            'other' => 'Diğer',
        ];

        $key = trim((string) $reason);

        return $map[$key] ?? ($key !== '' ? str_replace('_', ' ', $key) : '-');
    }

    public static function refundMethodLabel(?string $method): string
    {
        $map = [
            'original_gateway' => 'Ödeme yöntemine iade',
            'bank_transfer' => 'Havale / EFT',
            'manual' => 'Manuel iade',
        ];

        $key = trim((string) $method);
        if ($key === '') {
            return 'Henüz belirlenmedi';
        }

        return $map[$key] ?? $key;
    }

    public static function shippingPayerLabel(?string $payer): string
    {
        $map = [
            self::PAYER_SELLER => 'Satıcı karşılar',
            self::PAYER_BUYER => 'Alıcı karşılar',
            self::PAYER_PLATFORM => 'Platform karşılar',
        ];

        $key = trim((string) $payer);

        return $map[$key] ?? ($key !== '' ? $key : 'Henüz belirlenmedi');
    }

    /** Trendyol-benzeri: kusurlu/yanlış → satıcı; pişmanlık → alıcı */
    public static function defaultShippingPayerForReason(?string $reason): string
    {
        $sellerPays = ['defective', 'wrong_item', 'damaged_in_shipping', 'not_as_described'];

        return in_array(trim((string) $reason), $sellerPays, true)
            ? self::PAYER_SELLER
            : self::PAYER_BUYER;
    }

    public static function buildSellerReturnAddress(?Vendor $seller): string
    {
        if (! $seller) {
            return '';
        }

        $parts = array_filter([
            $seller->shop_name ? 'Mağaza: '.$seller->shop_name : null,
            $seller->address ?: null,
            $seller->phone ? 'Tel: '.$seller->phone : null,
            $seller->email ? 'E-posta: '.$seller->email : null,
        ]);

        return implode("\n", $parts);
    }

    public function statusLabel(): string
    {
        $status = (int) $this->status;

        return self::statusLabels()[$status] ?? ('Durum '.$status);
    }

    public function buyerStatusLabel(): string
    {
        $status = (int) $this->status;
        $labels = self::buyerStatusLabels();

        return $labels[$status] ?? ('Durum '.$status);
    }

    public function canBuyerSubmitTracking(): bool
    {
        // Alıcıdan takip no istenmez; satıcı "ürünü aldım" der, admin öder.
        return false;
    }

    public function canSellerMarkReceived(): bool
    {
        return in_array((int) $this->status, [
            self::STATUS_SELLER_APPROVED,
            self::STATUS_ADMIN_APPROVED,
        ], true);
    }

    /**
     * Alıcıya gösterilecek para iadesi bilgilendirmesi (Trendyol kart iadesinden farklı: havale manuel).
     */
    public function buyerRefundInfoText(): ?string
    {
        $status = (int) $this->status;
        if (in_array($status, [
            self::STATUS_SELLER_REJECTED,
            self::STATUS_ADMIN_REJECTED,
            self::STATUS_USER_CANCELLED,
            self::STATUS_PENDING,
        ], true)) {
            return null;
        }

        $method = trim((string) ($this->refund_method ?? ''));
        $this->loadMissing('order');
        $paymentMethod = strtolower((string) ($this->order->payment_method ?? ''));
        $isBankOrder = $paymentMethod === 'bankpayment'
            || (string) ($this->order->discount_type ?? '') === 'bank_transfer'
            || $method === 'bank_transfer';

        if ($status === self::STATUS_REFUNDED) {
            if ($isBankOrder || $method === 'bank_transfer') {
                return 'Ödeme yapıldı. Para iadeniz havale/EFT ile gönderildi. Genellikle 1–3 iş günü içinde hesabınıza yansır. Bankanızın işlem süreleri bu süreyi uzatabilir.';
            }

            return 'Ödeme yapıldı. Para iadeniz kartınıza / ödeme yönteminize iade edildi. Bankanıza göre genellikle 2–10 iş günü içinde hesabınıza yansır.';
        }

        if ($isBankOrder || $method === 'bank_transfer') {
            return 'Bu sipariş havale ile ödendiği için para iadesi kart gibi otomatik değil; yönetici ürünü teslim alıp iadeyi tamamladıktan sonra havale/EFT ile ödenir. Genellikle iade tamamlandıktan sonra 1–3 iş günü sürer. Alışverişteki havale indirimi (%3) iade tutarından düşülür; size ödediğiniz tutar kadar iade edilir.';
        }

        return 'Ürün satıcıya ulaşıp yönetici iadeyi tamamladıktan sonra tutar ödeme yönteminize (kart) iade edilir. Bankanıza göre 2–10 iş günü sürebilir.';
    }

    public function logisticsPayload(): array
    {
        return [
            'return_address' => $this->return_address,
            'return_shipping_payer' => $this->return_shipping_payer,
            'return_shipping_payer_label' => self::shippingPayerLabel($this->return_shipping_payer),
            'return_carrier_name' => $this->return_carrier_name,
            'return_cargo_code' => $this->return_cargo_code,
            'return_shipping_instructions' => $this->return_shipping_instructions,
            'buyer_return_carrier' => $this->buyer_return_carrier,
            'buyer_return_tracking_number' => $this->buyer_return_tracking_number,
            'buyer_return_tracking_url' => $this->buyer_return_tracking_url,
            'buyer_shipped_at' => optional($this->buyer_shipped_at)?->toIso8601String(),
            'can_submit_tracking' => $this->canBuyerSubmitTracking(),
            'refund_method' => $this->refund_method,
            'refund_method_label' => self::refundMethodLabel($this->refund_method),
            'refund_info' => $this->buyerRefundInfoText(),
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function images()
    {
        return $this->hasMany(ReturnRequestImage::class);
    }

    public function seller()
    {
        return $this->belongsTo(Vendor::class, 'seller_id');
    }

    public function vendor()
    {
        return $this->seller();
    }
}
