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
        'refund_amount' => 'decimal:2',
    ];

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

    public function statusLabel(): string
    {
        $status = (int) $this->status;

        return self::statusLabels()[$status] ?? ('Durum '.$status);
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
