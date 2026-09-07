<?php

namespace App\Models;

use App\Support\OrderQuantityHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $appends = ['display_product_qty'];

    public function getDisplayProductQtyAttribute(): int
    {
        return $this->displayProductQty();
    }

    public function user(){
        return $this->belongsTo(User::class) ->withDefault([
            'name' => $this->orderAddress?->billing_name ?? 'Guest'
        ]);
    }

    public function orderProducts(){
        return $this->hasMany(OrderProduct::class);
    }

    public function orderAddress(){
        return $this->hasOne(OrderAddress::class);
    }

    public function deliveryman(){
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id', 'id');
    }

    public function cargoShipment()
    {
        return $this->hasOne(CargoShipment::class)->latestOfMany();
    }

    /**
     * Liste ekranlari icin gosterilecek toplam adet.
     */
    public function displayProductQty(): int
    {
        if ($this->relationLoaded('orderProducts')) {
            $fromLines = OrderQuantityHelper::fromOrderProducts($this->orderProducts);
            if ($fromLines > 0) {
                return $fromLines;
            }
        }

        return max((int) ($this->product_qty ?? 0), 0);
    }

    /**
     * Kupon sipariş geneline yayılır. İade, ürünün ödenen payıdır (liste fiyatı − kupon payı).
     * Havale ile ödenen siparişlerde alışveriş indirimi (%3 vb.) iade tutarından düşülür.
     * Kısmi iadede kargo iade edilmez; siparişteki son ürün(ler) dönünce kargo da eklenir.
     */
    public function suggestedReturnRefund(OrderProduct $orderProduct, int $qty, ?int $excludeReturnId = null): array
    {
        $this->loadMissing('orderProducts');

        $qty = max(0, (int) $qty);
        $subtotal = round((float) $this->orderProducts->sum(
            static fn ($line) => (float) $line->unit_price * (int) $line->qty
        ), 2);
        $coupon = round((float) ($this->coupon_coast ?? 0), 2);
        $shipping = round((float) ($this->shipping_cost ?? 0), 2);
        $discountType = (string) ($this->discount_type ?? '');
        $discountAmount = round((float) ($this->discount_amount ?? 0), 2);
        $paymentMethod = strtolower(trim((string) ($this->payment_method ?? '')));
        $isBankPayment = $paymentMethod === 'bankpayment'
            || $paymentMethod === 'bank_transfer'
            || str_contains($paymentMethod, 'bank')
            || $discountType === 'bank_transfer'
            || ($discountAmount > 0 && str_contains($discountType, 'bank'));

        $settingsPercent = (float) (Setting::query()->value('bank_transfer_discount_percent') ?? 3);
        if ($settingsPercent <= 0 || $settingsPercent > 15) {
            $settingsPercent = 3.0;
        }

        // Havale tutarı coupon_coast'a yanlış yazılmışsa kupon uygulama.
        if ($isBankPayment && $coupon > 0 && empty($this->coupon_id)
            && ($discountAmount <= 0 || abs($coupon - $discountAmount) < 0.05)
        ) {
            if ($discountAmount <= 0) {
                $discountAmount = $coupon;
            }
            $coupon = 0.0;
        }

        $lineGross = round((float) $orderProduct->unit_price * $qty, 2);
        $couponShare = ($subtotal > 0 && $coupon > 0)
            ? round(($lineGross / $subtotal) * $coupon, 2)
            : 0.0;
        $productRefund = max(0, round($lineGross - $couponShare, 2));
        $includeShipping = $this->isCompletingOrderReturn($orderProduct, $qty, $excludeReturnId);
        $refundBeforeBank = $includeShipping ? round($productRefund + $shipping, 2) : $productRefund;

        $bankDiscountShare = 0.0;
        $refund = $refundBeforeBank;
        if ($isBankPayment && $refundBeforeBank > 0) {
            // Her zaman satır üzerinden ayar %'si (varsayılan 3). Şişmiş discount_amount kullanma.
            $bankDiscountShare = round(($refundBeforeBank * $settingsPercent) / 100, 2);
            $refund = max(0, round($refundBeforeBank - $bankDiscountShare, 2));
        }

        $productAfterBank = $productRefund;
        if ($bankDiscountShare > 0 && $refundBeforeBank > 0) {
            $productAfterBank = max(0, round(
                $productRefund - ($productRefund / $refundBeforeBank) * $bankDiscountShare,
                2
            ));
        }

        // Sipariş tavanı: diğer iadeler düşülür; bozuk total_amount satırı %3 altına çekmesin.
        $paidProducts = max(0, round($subtotal - $coupon, 2));
        $orderBase = max(0.01, round($paidProducts + $shipping, 2));
        $percentBasedTotal = round($orderBase * (1 - ($settingsPercent / 100)), 2);
        $maxOrderRefund = max(0, round((float) ($this->total_amount ?? 0), 2));
        if ($isBankPayment) {
            if ($maxOrderRefund <= 0 || ($coupon <= 0 && $maxOrderRefund + 0.05 < $percentBasedTotal)) {
                $maxOrderRefund = $percentBasedTotal;
            }
        } elseif ($maxOrderRefund <= 0) {
            $maxOrderRefund = $paidProducts + ($includeShipping ? $shipping : 0);
        }

        $reserved = (float) ReturnRequest::query()
            ->where('order_id', $this->id)
            ->whereIn('status', [
                ReturnRequest::STATUS_PENDING,
                ReturnRequest::STATUS_SELLER_APPROVED,
                ReturnRequest::STATUS_ADMIN_APPROVED,
                ReturnRequest::STATUS_ITEM_RECEIVED,
                ReturnRequest::STATUS_REFUNDED,
            ])
            ->when($excludeReturnId, fn ($query) => $query->where('id', '!=', $excludeReturnId))
            ->sum('refund_amount');
        $remainingCap = max(0, round($maxOrderRefund - $reserved, 2));

        if ($remainingCap > 0 && $refund > $remainingCap) {
            // Kupon yokken adil satır (ürün-%3) altına çekme.
            $fairLine = $isBankPayment && $couponShare <= 0
                ? max(0, round($refundBeforeBank - round(($refundBeforeBank * $settingsPercent) / 100, 2), 2))
                : $refund;
            if ($isBankPayment && $couponShare <= 0 && $fairLine >= $remainingCap) {
                $refund = $fairLine;
            } else {
                $preCap = $refund;
                $refund = $remainingCap;
                if ($preCap > 0 && $productAfterBank > 0) {
                    $productAfterBank = max(0, round($productAfterBank * ($refund / $preCap), 2));
                }
            }
        } elseif ($remainingCap <= 0 && $reserved > 0.009) {
            $refund = 0.0;
            $productAfterBank = 0.0;
            $bankDiscountShare = 0.0;
        }

        return [
            'line_gross' => $lineGross,
            'coupon_share' => $couponShare,
            'product_refund' => $productRefund,
            'bank_discount_share' => $bankDiscountShare,
            'is_bank_payment' => $isBankPayment,
            'shipping_included' => $includeShipping,
            'shipping' => $includeShipping ? $shipping : 0.0,
            'refund_amount' => $refund,
            'paid_unit_price' => $qty > 0
                ? round($productAfterBank / $qty, 2)
                : round((float) $orderProduct->unit_price, 2),
            'refund_method_hint' => $isBankPayment ? 'bank_transfer' : 'original_gateway',
        ];
    }

    private function isCompletingOrderReturn(OrderProduct $orderProduct, int $qty, ?int $excludeReturnId = null): bool
    {
        $this->loadMissing('orderProducts');

        $taken = ReturnRequest::query()
            ->where('order_id', $this->id)
            ->whereIn('status', [
                ReturnRequest::STATUS_PENDING,
                ReturnRequest::STATUS_SELLER_APPROVED,
                ReturnRequest::STATUS_ADMIN_APPROVED,
                ReturnRequest::STATUS_ITEM_RECEIVED,
                ReturnRequest::STATUS_REFUNDED,
            ])
            ->when($excludeReturnId, fn ($query) => $query->where('id', '!=', $excludeReturnId))
            ->selectRaw('order_product_id, SUM(qty) as taken')
            ->groupBy('order_product_id')
            ->pluck('taken', 'order_product_id');

        $remaining = 0;
        foreach ($this->orderProducts as $line) {
            $left = (int) $line->qty - (int) ($taken[$line->id] ?? 0);
            if ((int) $line->id === (int) $orderProduct->id) {
                $left -= $qty;
            }
            $remaining += max(0, $left);
        }

        return $remaining <= 0;
    }

    /**
     * Satıcının bu siparişteki tüm satır adedi tamamlanmış iade (STATUS_REFUNDED) ile kapanmış mı?
     */
    public function isFullyRefundedForSeller(?int $sellerId = null): bool
    {
        $this->loadMissing('orderProducts');
        $lines = $this->orderProducts;
        if ($sellerId !== null) {
            $lines = $lines->where('seller_id', $sellerId);
        }
        if ($lines->isEmpty()) {
            return false;
        }

        $taken = ReturnRequest::query()
            ->where('order_id', $this->id)
            ->where('status', ReturnRequest::STATUS_REFUNDED)
            ->when($sellerId !== null, fn ($q) => $q->where('seller_id', $sellerId))
            ->selectRaw('order_product_id, SUM(qty) as taken')
            ->groupBy('order_product_id')
            ->pluck('taken', 'order_product_id');

        foreach ($lines as $line) {
            if ((int) ($taken[$line->id] ?? 0) < (int) $line->qty) {
                return false;
            }
        }

        return true;
    }

    public function scopeForSeller($query, int $sellerId)
    {
        return $query->whereHas('orderProducts', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        });
    }

    public function scopePaidRealized($query)
    {
        return $query->where('payment_status', 1)->whereIn('order_status', [1, 2, 3]);
    }

    public function scopePaidCompleted($query)
    {
        return $query->where('payment_status', 1)->where('order_status', 3);
    }
}
