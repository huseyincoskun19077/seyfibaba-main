@php
    $productCount = $productCount
        ?? ($vendor->products_count ?? ($vendor->relationLoaded('products') ? $vendor->products->count() : $vendor->products()->count()));
    $kycStatus = $vendor->kyc_status ?? 'not_submitted';
    $kycLabels = [
        'approved' => ['Onaylı', 'success'],
        'pending' => ['İnceleniyor', 'warning'],
        'rejected' => ['Reddedildi', 'danger'],
        'not_submitted' => ['Yüklenmedi', 'secondary'],
    ];
    [$kycLabel, $kycBadge] = $kycLabels[$kycStatus] ?? ['Yüklenmedi', 'secondary'];
@endphp

<div class="{{ $wrapperClass ?? 'mt-3' }}">
    <h6 class="mb-2">{{ $title ?? 'Ürün & KYC' }}</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <tr>
                <td style="width:40%">Ürün yükleme</td>
                <td>
                    @if((int) $productCount > 0)
                        <span class="badge badge-success">{{ (int) $productCount }} ürün</span>
                    @else
                        <span class="badge badge-warning">Ürün yüklenmedi</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td>KYC</td>
                <td>
                    <span class="badge badge-{{ $kycBadge }}">{{ $kycLabel }}</span>
                </td>
            </tr>
        </table>
    </div>
</div>
