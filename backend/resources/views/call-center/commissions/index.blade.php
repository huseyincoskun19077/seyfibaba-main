@extends('call-center.layout.master')

@section('title')
<title>Hakedişlerim</title>
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Hakedişlerim</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('call-center.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item active">Hakedişlerim</div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary"><i class="fas fa-calculator"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Toplam Hakediş</h4></div>
                        <div class="card-body">{{ number_format($totals['calculated_total'] ?? 0, 2, ',', '.') }} TL</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Onay Bekleyen</h4></div>
                        <div class="card-body">{{ number_format($totals['pending'] ?? 0, 2, ',', '.') }} TL</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-info"><i class="fas fa-hourglass-half"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Ödeme Bekleyen</h4></div>
                        <div class="card-body">{{ number_format($totals['awaiting_payment'] ?? 0, 2, ',', '.') }} TL</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Ödenen</h4></div>
                        <div class="card-body">{{ number_format($totals['paid_total'] ?? 0, 2, ',', '.') }} TL</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h4>Satıcı Bazlı Hakediş</h4></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Firma</th>
                                <th>Ürün</th>
                                <th>KYC</th>
                                <th>Hesaplanan</th>
                                <th>Ödenen</th>
                                <th>Kalan</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registrations as $registration)
                                @php
                                    $commission = $registration->callCenterCommission;
                                    $preview = $registration->commission_preview ?? [];
                                    $calcTotal = (float) ($commission->calculated_total ?? ($preview['total'] ?? 0));
                                    $paidTotal = (float) ($commission->paid_total ?? 0);
                                    $remaining = max(0, $calcTotal - $paidTotal);
                                    $canApprove = $commission
                                        && ! $commission->isAwaitingPayment()
                                        && $remaining > 0
                                        && ($preview['eligible'] ?? false);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $registration->shop_name }}</strong>
                                        @if(!empty($preview['summary']))
                                            <br><small class="text-muted">{{ $preview['summary'] }}</small>
                                        @endif
                                    </td>
                                    <td>{{ (int) $registration->products_count }}</td>
                                    <td>
                                        @if($registration->kyc_status === 'approved')
                                            <span class="badge badge-success">Onaylı</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $registration->kyc_status ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($calcTotal, 2, ',', '.') }} TL</td>
                                    <td>{{ number_format($paidTotal, 2, ',', '.') }} TL</td>
                                    <td>{{ number_format($remaining, 2, ',', '.') }} TL</td>
                                    <td>
                                        @if($commission && $commission->isAwaitingPayment())
                                            <span class="badge badge-info">Admin ödemesi bekleniyor</span>
                                        @elseif($canApprove)
                                            <span class="badge badge-warning">Onayınız bekleniyor</span>
                                        @elseif($remaining <= 0 && $paidTotal > 0)
                                            <span class="badge badge-success">Tamamlandı</span>
                                        @else
                                            <span class="badge badge-secondary">Henüz yok</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if($canApprove)
                                            <form method="POST"
                                                  action="{{ route('call-center.commissions.approve', $registration->id) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('{{ number_format($remaining, 2, ',', '.') }} TL hakediş onaylansın mı?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Onayla</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('call-center.registrations.show', $registration->id) }}" class="btn btn-sm btn-light">Detay</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Henüz kayıt yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($registrations->hasPages())
                <div class="card-footer">{{ $registrations->links() }}</div>
            @endif
        </div>

        @if($recentPayments->isNotEmpty())
            <div class="card">
                <div class="card-header"><h4>Son Ödemeler</h4></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Firma</th>
                                    <th>Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPayments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at?->format('d.m.Y H:i') }}</td>
                                        <td>{{ $payment->vendor?->shop_name ?? '—' }}</td>
                                        <td class="text-success">{{ number_format((float) $payment->amount, 2, ',', '.') }} TL</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
