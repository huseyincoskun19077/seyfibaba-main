@extends('admin.master_layout')
@section('title')
<title>Satıcı Performans</title>
@endsection

@section('admin-content')
<div class=main-content>
    <section class=section>
        <div class="section-header">
            <h1>Satıcı Performans</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Satıcı Performans</div>
            </div>
        </div>

        <div class="section-body">
            <!-- Filter Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Filtrele</h4>
                        </div>
                        <div class="card-body">
                            <div class="filter-buttons">
                                <a href="{{ route('admin.seller-performance.index', ['filter' => 'daily']) }}" 
                                   class="btn {{ $filter == 'daily' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-day"></i> Günlük
                                </a>
                                <a href="{{ route('admin.seller-performance.index', ['filter' => 'weekly']) }}" 
                                   class="btn {{ $filter == 'weekly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-week"></i> Haftalık
                                </a>
                                <a href="{{ route('admin.seller-performance.index', ['filter' => 'monthly']) }}" 
                                   class="btn {{ $filter == 'monthly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-alt"></i> Aylık
                                </a>
                                <a href="{{ route('admin.seller-performance.index', ['filter' => 'yearly']) }}" 
                                   class="btn {{ $filter == 'yearly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar"></i> Yıllık
                                </a>
                                <a href="{{ route('admin.seller-performance.index', ['filter' => 'all']) }}" 
                                   class="btn {{ $filter == 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-infinity"></i> Tüm Zaman
                                </a>
                            </div>
                            @if($filter != 'all')
                            <div class="mt-3 text-muted">
                                <small>
                                    <i class="fas fa-info-circle"></i> 
                                    Filtre: {{ $filter == 'daily' ? 'Bugün' : ($filter == 'weekly' ? 'Bu Hafta' : ($filter == 'monthly' ? 'Bu Ay' : 'Bu Yıl')) }}
                                    ({{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }})
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Ranking Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-trophy text-warning"></i> Satıcı Performans Sıralaması</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Satıcı</th>
                                            <th>Puan</th>
                                            <th>Toplam Satış</th>
                                            <th>Sipariş</th>
                                            <th>Ürün Satış</th>
                                            <th>Aktif Ürün</th>
                                            <th>Puanlama</th>
                                            <th>Yorum</th>
                                            <th>Tamamlanan</th>
                                            <th>Bekleyen</th>
                                            <th>Komisyon</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vendorPerformance as $index => $perf)
                                        <tr>
                                            <td>
                                                @if($index == 0)
                                                <span class="badge badge-warning">🥇</span>
                                                @elseif($index == 1)
                                                <span class="badge badge-secondary">🥈</span>
                                                @elseif($index == 2)
                                                <span class="badge badge-info">🥉</span>
                                                @else
                                                {{ ++$index }}
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $perf['vendor']->shop_name }}</strong>
                                                @if($perf['vendor']->status == 0)
                                                <span class="badge badge-warning">Pasif</span>
                                                @endif
                                                @if($perf['vendor']->kyc_approved_at)
                                                <span class="badge badge-success">KYC</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $scoreBadge = $perf['total_score'] >= 80 ? 'badge-success' : ($perf['total_score'] >= 50 ? 'badge-warning' : 'badge-secondary');
                                                @endphp
                                                <span class="badge {{ $scoreBadge }}">
                                                    {{ $perf['total_score'] }}/100
                                                </span>
                                            </td>
                                            <td><span class="badge badge-primary">{{ number_format($perf['total_sales'], 2) }} TL</span></td>
                                            <td><span class="badge badge-info">{{ $perf['order_count'] }}</span></td>
                                            <td><span class="badge badge-secondary">{{ $perf['products_sold'] }}</span></td>
                                            <td>
                                                <span class="badge badge-success">{{ $perf['active_products'] }}</span>
                                                @if($perf['inactive_products'] > 0)
                                                <span class="badge badge-warning" title="Pasif ürün">{{ $perf['inactive_products'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $ratingBadge = $perf['avg_rating'] >= 4 ? 'badge-success' : ($perf['avg_rating'] >= 3 ? 'badge-warning' : 'badge-secondary');
                                                @endphp
                                                <span class="badge {{ $ratingBadge }}">
                                                    {{ $perf['avg_rating'] }}/5
                                                </span>
                                            </td>
                                            <td><span class="badge badge-info">{{ $perf['total_reviews'] }}</span></td>
                                            <td>
                                                <span class="badge badge-success">{{ $perf['completed_orders'] }}</span>
                                                @if($perf['cancelled_orders'] > 0)
                                                <span class="badge badge-danger" title="İptal">{{ $perf['cancelled_orders'] }}</span>
                                                @endif
                                            </td>
                                            <td><span class="badge badge-warning">{{ $perf['pending_orders'] }}</span></td>
                                            <td><span class="badge badge-primary">{{ number_format($perf['total_commission'], 2) }} TL</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Stats by Seller -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-chart-pie text-info"></i> Detaylı İstatistikler</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Satıcı</th>
                                            <th>Satış Puanı</th>
                                            <th>Sipariş Puanı</th>
                                            <th>Puanlama Puanı</th>
                                            <th>Ürün Puanı</th>
                                            <th>Tamamlanma Puanı</th>
                                            <th>Net Kazanç</th>
                                            <th>Çekilen</th>
                                            <th>Bekleyen Çekim</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vendorPerformance as $perf)
                                        <tr>
                                            <td><strong>{{ $perf['vendor']->shop_name }}</strong></td>
                                            <td><span class="badge badge-primary">{{ $perf['sales_score'] }}</span></td>
                                            <td><span class="badge badge-info">{{ $perf['order_score'] }}</span></td>
                                            <td><span class="badge badge-warning">{{ $perf['rating_score'] }}</span></td>
                                            <td><span class="badge badge-secondary">{{ $perf['product_score'] }}</span></td>
                                            <td><span class="badge badge-success">{{ $perf['completion_score'] }}</span></td>
                                            <td><span class="badge badge-success">{{ number_format($perf['seller_net_amount'], 2) }} TL</span></td>
                                            <td><span class="badge badge-info">{{ number_format($perf['total_withdrawn'], 2) }} TL</span></td>
                                            <td><span class="badge badge-warning">{{ number_format($perf['pending_withdrawal'], 2) }} TL</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Score Explanation -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-calculator"></i> Puanlama Sistemi</h5>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <ul>
                                    <li><strong>Satış Puanı:</strong> 1.000.000 TL = 100 puan</li>
                                    <li><strong>Sipariş Puanı:</strong> 500 sipariş = 100 puan</li>
                                    <li><strong>Ürün Puanı:</strong> 100 aktif ürün = 100 puan</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <ul>
                                    <li><strong>Puanlama Puanı:</strong> 5 yıldız = 100 puan</li>
                                    <li><strong>Tamamlanma Puanı:</strong> %100 tamamlanan = 100 puan</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <ul>
                                    <li><strong>Toplam Puan:</strong> (5 puan türü ortalaması)</li>
                                    <li><strong>KYC:</strong> Onaylı KYC varsa rozet gösterilir</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.filter-buttons .btn {
    min-width: 120px;
}
</style>
@endsection