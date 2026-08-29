{{-- Ortak: sol admin durum formu / sağ toplamlar --}}
<div class="row mt-3">
    @if (($invoiceContext ?? 'admin') === 'admin')
        @include('partials.order_invoice.admin_order_status_form', [
            'order' => $order,
            'deliverymans' => $deliverymans,
        ])
    @else
        <div class="col-lg-6 order-status"></div>
    @endif

    @include('partials.order_invoice.totals', [
        'order' => $order,
        'setting' => $setting,
        'invoiceContext' => $invoiceContext ?? 'admin',
        'orderDistinctSellerCount' => $orderDistinctSellerCount ?? 1,
        'sellerLinesSubtotal' => $sellerLinesSubtotal ?? 0,
    ])
</div>
