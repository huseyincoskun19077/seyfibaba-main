@php
    use App\Support\SellerOrderFlow;

    $myProducts = $order->orderProducts;
    $sellerStatus = SellerOrderFlow::sellerStatus($myProducts);
    $payout = SellerOrderFlow::payoutInfo($order, $myProducts);
    $steps = SellerOrderFlow::steps($sellerStatus, $payout['state']);
    $latestCargo = \App\Models\CargoShipment::query()
        ->where('order_id', $order->id)
        ->where('seller_id', auth()->user()->seller->id ?? 0)
        ->whereNotIn('status', ['cancelled'])
        ->latest()
        ->first();
@endphp

<style>
    .seller-flow-card { border: 1px solid #e8eaed; border-radius: 12px; background: #fff; }
    .seller-flow-steps { list-style: none; margin: 0; padding: 0; }
    .seller-flow-step { display: flex; gap: 14px; padding: 14px 0; position: relative; }
    .seller-flow-step:not(:last-child) { border-bottom: 1px dashed #eef0f3; }
    .seller-flow-step__icon {
        width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 13px; flex-shrink: 0;
    }
    .seller-flow-step--done .seller-flow-step__icon { background: #d4edda; color: #155724; }
    .seller-flow-step--current .seller-flow-step__icon { background: #fff3cd; color: #856404; box-shadow: 0 0 0 2px #ffc107; }
    .seller-flow-step--upcoming .seller-flow-step__icon { background: #f1f3f5; color: #adb5bd; }
    .seller-flow-step--cancelled .seller-flow-step__icon { background: #f8d7da; color: #721c24; }
    .seller-flow-step__title { font-weight: 700; margin-bottom: 4px; }
    .seller-flow-step__desc { color: #6c757d; font-size: 13px; margin-bottom: 0; }
    .seller-flow-action {
        margin-top: 12px; padding: 14px; border-radius: 10px; background: #fffdf5; border: 1px solid #ffe08a;
    }
    .seller-flow-payout {
        margin-top: 16px; padding: 14px; border-radius: 10px; background: #f8f9fa; border: 1px solid #e9ecef;
    }
</style>

<div class="card seller-flow-card mb-4">
    <div class="card-header">
        <h4 class="mb-0">Sipariş Süreci</h4>
        <small class="text-muted">Adımları sırayla tamamlayın. Sonraki adım, önceki adım bitmeden açılmaz.</small>
    </div>
    <div class="card-body">
        @if ($sellerStatus === 4)
            <div class="alert alert-danger mb-3">Bu sipariş iptal edilmiş veya reddedilmiş.</div>
        @endif

        <ul class="seller-flow-steps">
            @foreach ($steps as $index => $step)
                <li class="seller-flow-step seller-flow-step--{{ $step['state'] }}">
                    <div class="seller-flow-step__icon">
                        @if ($step['state'] === 'done')
                            <i class="fas fa-check"></i>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>
                    <div class="w-100">
                        <div class="seller-flow-step__title">{{ $step['title'] }}</div>
                        <p class="seller-flow-step__desc">{{ $step['description'] }}</p>

                        @if ($step['state'] === 'current' && $sellerStatus === 0 && $step['key'] === 'received')
                            <div class="seller-flow-action">
                                <p class="mb-2"><strong>Siparişi hazırlayacağınızı onaylayın.</strong> Bu adımdan sonra kargo bilgisi girebilirsiniz.</p>
                                <form action="{{ route('seller.update-order-status', $order->id) }}" method="POST"
                                      onsubmit="return confirm('Siparişi hazırlamaya başlayacağınızı onaylıyor musunuz?');">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="order_status" value="1" />
                                    <button class="btn btn-warning" type="submit">
                                        <i class="fas fa-box-open mr-1"></i> Siparişi Hazırlayacağımı Onayla
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if ($step['state'] === 'current' && $sellerStatus === 1 && $step['key'] === 'preparing')
                            <div class="seller-flow-action">
                                <p class="mb-2"><strong>Kargoya teslim ettiniz mi?</strong> Kargo firması ve takip numarası zorunludur.</p>
                                <form action="{{ route('seller.orders.cargo.manual.ship', $order->id) }}" method="POST"
                                      onsubmit="return confirm('Kargo bilgilerini kaydedip siparişi kargoya verildi olarak işaretlemek istiyor musunuz?');">
                                    @csrf
                                    <div class="row" style="row-gap:10px;">
                                        <div class="col-md-4">
                                            <label class="mb-1">Kargo firması *</label>
                                            <input type="text" name="carrier_name" class="form-control" placeholder="Örn: Yurtiçi Kargo" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="mb-1">Takip numarası *</label>
                                            <input type="text" name="tracking_number" class="form-control" placeholder="Takip no" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="mb-1">Takip linki (opsiyonel)</label>
                                            <input type="url" name="tracking_url" class="form-control" placeholder="https://...">
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-shipping-fast mr-1"></i> Kargoya Teslim Ettim
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif

                        @if ($step['state'] === 'current' && $sellerStatus === 2 && $step['key'] === 'shipped')
                            <div class="alert alert-info mb-0 mt-2">
                                <div><strong>Kargo yolda.</strong> Müşteri uygulamadan “Teslim Aldım” dediğinde sipariş tamamlanır.</div>
                                @if ($latestCargo)
                                    <div class="mt-2 small">
                                        {{ $latestCargo->carrier_name }} — {{ $latestCargo->tracking_number }}
                                        @if ($latestCargo->tracking_url)
                                            <a href="{{ $latestCargo->tracking_url }}" target="_blank" rel="noopener">Takip et</a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($step['state'] === 'done' && $step['key'] === 'shipped' && $latestCargo)
                            <div class="small text-muted mt-1">
                                {{ $latestCargo->carrier_name }} — {{ $latestCargo->tracking_number }}
                            </div>
                        @endif

                        @if ($step['state'] === 'done' && $step['key'] === 'completed' && $sellerStatus >= 3)
                            <div class="small text-success mt-1">Müşteri teslim aldı, sipariş tamamlandı.</div>
                        @endif

                        @if ($step['key'] === 'payout' && in_array($step['state'], ['current', 'done']))
                            <div class="alert alert-{{ $payout['badge'] }} mb-0 mt-2 py-2 px-3">
                                <strong>{{ $payout['label'] }}</strong><br>
                                <span class="small">{{ $payout['detail'] }}</span>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="seller-flow-payout">
            <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:8px;">
                <div>
                    <div class="text-muted" style="font-size:12px;">Hakediş durumu</div>
                    <div class="font-weight-bold">{{ $payout['label'] }}</div>
                    <div class="small text-muted mt-1">{{ $payout['detail'] }}</div>
                </div>
                <span class="badge badge-{{ $payout['badge'] }}">{{ $payout['label'] }}</span>
            </div>
        </div>
    </div>
</div>
