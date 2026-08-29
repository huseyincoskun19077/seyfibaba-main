{{--
  Aynı toplam mantığı: admin ile birebir (setting.tex / grand_total).
  Çok satıcılı siparişte satıcı ekranı: yalnızca bu satıcının kalemleri üzerinden ara toplam (sellerLinesSubtotal).
--}}
@php
    $ctx = $invoiceContext ?? 'admin';
    $multiSeller = ($orderDistinctSellerCount ?? 1) > 1;
    $sellerSub = round((float) ($sellerLinesSubtotal ?? 0), 2);

    if ($ctx === 'admin' || ! $multiSeller) {
        $sub_total = $order->total_amount - $order->shipping_cost;
        $grand_total = ($sub_total + $order->shipping_cost + $order->total_amount * ($setting->tex / 100)) - $order->coupon_coast;
    } else {
        $sub_total = $sellerSub;
        $grand_total = $sellerSub;
    }
@endphp
<div class="col-lg-6 text-right">
    <div class="invoice-detail-item">
        <div class="invoice-detail-name">{{ __('admin.Subtotal') }} : {{ $setting->currency_icon }}{{ round($sub_total, 2) }}</div>
    </div>
    <div class="invoice-detail-item">
        <div class="invoice-detail-name">{{ __('admin.Discount') }}(-) :
            @if ($ctx === 'seller' && $multiSeller)
                <span class="text-muted">—</span>
            @else
                {{ $setting->currency_icon }}{{ round($order->coupon_coast, 2) }}
            @endif
        </div>
    </div>
    <div class="invoice-detail-item">
        <div class="invoice-detail-name">{{ __('admin.Shipping') }} :
            @if ($ctx === 'seller' && $multiSeller)
                <span class="text-muted">—</span>
            @else
                {{ $setting->currency_icon }}{{ round($order->shipping_cost, 2) }}
            @endif
        </div>
    </div>
    <div class="invoice-detail-item">
        <div class="invoice-detail-name">{{ __('admin.Tax') }} : {{ $setting->tax }}%</div>
    </div>
    @if ($ctx === 'seller' && $multiSeller)
        <div class="invoice-detail-item small text-muted text-right">
            <div class="invoice-detail-name">Bu siparişte birden fazla satıcı var; üstteki ara toplam yalnızca sizin ürünlerinizdir.</div>
        </div>
    @endif

    <hr class="mt-2 mb-2">
    <div class="invoice-detail-item">
        <div class="invoice-detail-value invoice-detail-value-lg">{{ __('admin.Total') }} : {{ $setting->currency_icon }}{{ round($grand_total, 2) }}</div>
    </div>
</div>
