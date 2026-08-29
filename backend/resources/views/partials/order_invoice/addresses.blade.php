{{-- Ortak: fatura / kargo adresi (admin ile aynı yapı) --}}
@if ($orderAddress ?? null)
    @php
        $oa = $orderAddress;
        $billingName = trim((string) ($oa->billing_name ?? '')) !== ''
            ? $oa->billing_name
            : trim(($oa->billing_first_name ?? '').' '.($oa->billing_last_name ?? ''));
        $shippingName = trim((string) ($oa->shipping_name ?? '')) !== ''
            ? $oa->shipping_name
            : trim(($oa->shipping_first_name ?? '').' '.($oa->shipping_last_name ?? ''));
    @endphp
    <div class="row order-invoice-addresses">
        <div class="col-md-6">
            <address>
                <strong>{{ __('admin.Billing Information') }}:</strong><br>
                {{ $billingName }}<br>
                @if ($oa->billing_email)
                    {{ $oa->billing_email }}<br>
                @endif
                @if ($oa->billing_phone)
                    {{ $oa->billing_phone }}<br>
                @endif
                {{ $oa->billing_address }},
                {{ $oa->billing_city.', '.$oa->billing_state.', '.$oa->billing_country }}<br>
            </address>
        </div>
        <div class="col-md-6 text-md-right">
            <address>
                <strong>{{ __('admin.Shipping Information') }} :</strong><br>
                {{ $shippingName }}<br>
                @if ($oa->shipping_email)
                    {{ $oa->shipping_email }}<br>
                @endif
                @if ($oa->shipping_phone)
                    {{ $oa->shipping_phone }}<br>
                @endif
                {{ $oa->shipping_address }},
                {{ $oa->shipping_city.', '.$oa->shipping_state.', '.$oa->shipping_country }}<br>
            </address>
        </div>
    </div>
@endif
