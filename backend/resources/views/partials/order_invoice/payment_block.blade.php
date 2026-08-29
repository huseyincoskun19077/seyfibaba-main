{{-- Ortak: ödeme + sipariş bilgisi + (admin) teslimat görevlisi --}}
<div class="row">
    <div class="col-md-6">
        <address>
            <strong>{{ __('admin.Payment Information') }}:</strong><br>
            {{ __('admin.Method') }}: {{ $order->payment_method }}<br>
            {{ __('admin.Status') }} :
            @if ($order->payment_status == 1)
                <span class="badge badge-success">{{ __('admin.Success') }}</span>
            @else
                <span class="badge badge-danger">{{ __('admin.Pending') }}</span>
            @endif
            <br>
            {{ __('admin.Transaction') }}: {!! clean(nl2br($order->transection_id)) !!}
        </address>
    </div>
    <div class="col-md-6 text-md-right">
        <address>
            <strong>{{ __('admin.Order Information') }}:</strong><br>
            {{ __('admin.Date') }}: {{ $order->created_at->format('d F, Y') }}<br>
            {{ __('admin.Shipping') }}: {{ $order->shipping_method }}<br>

            @if (($invoiceContext ?? 'admin') === 'seller')
                {{-- Satıcı için seller_status kontrolü --}}
                @php
                    $myProducts = $order->orderProducts;
                    $hasPending = $myProducts->contains(fn($op) => $op->seller_status == 0);
                    $hasProgress = $myProducts->contains(fn($op) => $op->seller_status == 1);
                    $hasDelivered = $myProducts->contains(fn($op) => $op->seller_status == 2);
                @endphp

                @if ($hasPending)
                    <div class="mt-2">
                        <form action="{{ route('seller.update-order-status', $order->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="order_status" value="1" />
                            <button class="btn btn-success btn-sm" type="submit">Siparişi Onayla</button>
                        </form>
                    </div>
                @endif

                @if ($hasProgress && !$hasDelivered)
                    <div class="mt-2">
                        <form action="{{ route('seller.update-order-status', $order->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="order_status" value="2" />
                            <button class="btn btn-info btn-sm" type="submit">Kargoya Verildi</button>
                        </form>
                    </div>
                @endif

                @if ($hasDelivered)
                    <span class="badge badge-info">Kargoya Verildi</span>
                @endif
            @endif
        </address>
    </div>
    {{-- Not: satıcı süreçleri seller_status üzerinden yürür; order_status admin özetidir. --}}

    @include('partials.order_invoice.delivery_man_block', ['order' => $order, 'invoiceContext' => $invoiceContext ?? 'admin'])
</div>

@if (($invoiceContext ?? 'admin') === 'admin')
    <div class="row mt-3 print-area">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Payout Kontrolü</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap" style="gap:10px; align-items:flex-end;">
                        <div>
                            <div class="text-muted" style="font-size:12px;">Blok durumu</div>
                            @if ($order->payout_blocked_at)
                                <span class="badge badge-danger">BLOKLU</span>
                            @else
                                <span class="badge badge-success">AÇIK</span>
                            @endif
                            @if ($order->payout_hold_until)
                                <span class="badge badge-warning">Beklet: {{ \Carbon\Carbon::parse($order->payout_hold_until)->format('Y-m-d H:i') }}</span>
                            @endif
                        </div>

                        <form action="{{ route('admin.orders.payout.block', $order->id) }}" method="POST" class="d-flex" style="gap:8px;">
                            @csrf
                            <input type="text" name="reason" class="form-control" placeholder="Blok sebebi (opsiyonel)" style="min-width:260px;">
                            <button type="submit" class="btn btn-danger btn-sm">Blokla</button>
                        </form>

                        <form action="{{ route('admin.orders.payout.unblock', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">Blok Kaldır</button>
                        </form>

                        <form action="{{ route('admin.orders.payout.hold', $order->id) }}" method="POST" class="d-flex" style="gap:8px;">
                            @csrf
                            <input type="datetime-local" name="hold_until" class="form-control" style="min-width:220px;" required>
                            <input type="text" name="reason" class="form-control" placeholder="Bekletme notu (opsiyonel)" style="min-width:240px;">
                            <button type="submit" class="btn btn-warning btn-sm">Beklet</button>
                        </form>

                        <form action="{{ route('admin.orders.payout.hold.clear', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning btn-sm">Bekletmeyi Kaldır</button>
                        </form>
                    </div>

                    @if ($order->payout_block_reason)
                        <div class="mt-2">
                            <small class="text-muted">Not: {!! clean(nl2br($order->payout_block_reason)) !!}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
