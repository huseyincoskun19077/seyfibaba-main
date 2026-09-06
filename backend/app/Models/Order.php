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
        $lineGross = round((float) $orderProduct->unit_price * $qty, 2);
        $couponShare = ($subtotal > 0 && $coupon > 0)
            ? round(($lineGross / $subtotal) * $coupon, 2)
            : 0.0;
        $productRefund = max(0, round($lineGross - $couponShare, 2));
        $includeShipping = $this->isCompletingOrderReturn($orderProduct, $qty, $excludeReturnId);
        $refund = $includeShipping ? round($productRefund + $shipping, 2) : $productRefund;

        $paidProducts = max(0, round($subtotal - $coupon, 2));
        $bankDiscountShare = 0.0;
        $discountType = (string) ($this->discount_type ?? '');
        $discountAmount = round((float) ($this->discount_amount ?? 0), 2);
        $paymentMethod = strtolower(trim((string) ($this->payment_method ?? '')));
        $isBankPayment = $paymentMethod === 'bankpayment'
            || $paymentMethod === 'bank_transfer'
            || str_contains($paymentMethod, 'bank')
            || $discountType === 'bank_transfer'
            || ($discountAmount > 0 && $discountType !== '' && str_contains($discountType, 'bank'));

        $grossBeforeBank = $refund;
        if ($isBankPayment && $refund > 0) {
            $discountedBase = max(0.01, round($paidProducts + $shipping, 2));
            if ($discountAmount > 0) {
                $bankDiscountShare = round(($refund / $discountedBase) * $discountAmount, 2);
            } else {
                $percent = (float) (Setting::query()->value('bank_transfer_discount_percent') ?? 3);
                $bankDiscountShare = round(($refund * max(0, $percent)) / 100, 2);
            }
            $refund = max(0, round($refund - $bankDiscountShare, 2));
        }

        $productAfterBank = $productRefund;
        if ($bankDiscountShare > 0 && $grossBeforeBank > 0) {
            $productAfterBank = max(0, round($productRefund - ($productRefund / $grossBeforeBank) * $bankDiscountShare, 2));
        }

        $maxOrderRefund = max(0, round((float) ($this->total_amount ?? 0), 2));
        if ($maxOrderRefund <= 0) {
            $maxOrderRefund = max(0, round($paidProducts + ($includeShipping ? $shipping : 0) - ($isBankPayment ? $discountAmount : 0), 2));
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
        $preCapRefund = $refund;
        if ($refund > $remainingCap) {
            $refund = $remainingCap;
            // Keep paid_unit_price aligned when only the order total cap applied the havale cut.
            if ($preCapRefund > 0 && $productAfterBank > 0) {
                $productAfterBank = max(0, round($productAfterBank * ($refund / $preCapRefund), 2));
                $bankDiscountShare = max(0, round($bankDiscountShare + ($preCapRefund - $refund), 2));
                $isBankPayment = $isBankPayment || ($preCapRefund - $refund) > 0.009;
            }
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
