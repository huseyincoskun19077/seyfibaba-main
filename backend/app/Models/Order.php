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
        $maxOrderRefund = $paidProducts + ($includeShipping ? $shipping : 0);
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
        if ($refund > $remainingCap) {
            $refund = $remainingCap;
        }

        return [
            'line_gross' => $lineGross,
            'coupon_share' => $couponShare,
            'product_refund' => $productRefund,
            'shipping_included' => $includeShipping,
            'shipping' => $includeShipping ? $shipping : 0.0,
            'refund_amount' => $refund,
            'paid_unit_price' => $qty > 0
                ? round($productRefund / $qty, 2)
                : round((float) $orderProduct->unit_price, 2),
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
