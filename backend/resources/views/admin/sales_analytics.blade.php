@extends('admin.master_layout')
@section('title')
<title>Satış Analizi</title>
@endsection

@section('admin-content')
<div class=main-content>
    <section class=section>
        <div class="section-header">
            <h1>Satış Analizi</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Satış Analizi</div>
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
                                <a href="{{ route('admin.sales-analytics.index', ['filter' => 'daily']) }}" 
                                   class="btn {{ $filter == 'daily' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-day"></i> Günlük
                                </a>
                                <a href="{{ route('admin.sales-analytics.index', ['filter' => 'weekly']) }}" 
                                   class="btn {{ $filter == 'weekly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-week"></i> Haftalık
                                </a>
                                <a href="{{ route('admin.sales-analytics.index', ['filter' => 'monthly']) }}" 
                                   class="btn {{ $filter == 'monthly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-alt"></i> Aylık
                                </a>
                                <a href="{{ route('admin.sales-analytics.index', ['filter' => 'yearly']) }}" 
                                   class="btn {{ $filter == 'yearly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar"></i> Yıllık
                                </a>
                                <a href="{{ route('admin.sales-analytics.index', ['filter' => 'all']) }}" 
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

            <!-- Sales Statistics Cards -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Toplam Sipariş</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($salesStats['totalOrders']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Toplam Gelir</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($salesStats['totalRevenue'], 2) }} <small>TL</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Ortalama Sipariş</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($salesStats['avgOrderValue'], 2) }} <small>TL</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Satılan Ürün</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($salesStats['totalItemsSold']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Status Summary -->
            <div class="row mt-2">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Sipariş Durumları:</strong> 
                        Tamamlanan: {{ number_format($salesStats['completedOrders']) }} | 
                        Bekleyen: {{ number_format($salesStats['pendingOrders']) }} | 
                        İptal: {{ number_format($salesStats['cancelledOrders']) }} |
                        Komisyon: {{ number_format($salesStats['totalCommission'], 2) }} TL
                    </div>
                </div>
            </div>

            <!-- Best Selling Products -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-trophy text-warning"></i> En Çok Satılan Ürünler</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Ürün</th>
                                            <th>Satıcı</th>
                                            <th>Kategori</th>
                                            <th>Satılan Adet</th>
                                            <th>Gelir</th>
                                            <th>Sipariş Sayısı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($bestSellingProducts as $index => $sale)
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td>
                                                <strong>{{ $sale['product']->name }}</strong>
                                                @if($sale['product']->status == 0)
                                                <span class="badge badge-warning">Pasif</span>
                                                @endif
                                            </td>
                                            <td>{{ $sale['product']->seller ? $sale['product']->seller->shop_name : '-' }}</td>
                                            <td>{{ $sale['product']->category ? $sale['product']->category->name : '-' }}</td>
                                            <td><span class="badge badge-success">{{ number_format($sale['total_qty']) }}</span></td>
                                            <td><span class="badge badge-primary">{{ number_format($sale['total_revenue'], 2) }} TL</span></td>
                                            <td><span class="badge badge-info">{{ number_format($sale['order_count']) }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($bestSellingProducts) == 0)
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x"></i>
                                <p>Henüz satış verisi bulunmuyor.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversion Rates -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-percentage text-info"></i> Dönüşüm Oranları (Görüntüleme → Satış)</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Ürün</th>
                                            <th>Görüntüleme</th>
                                            <th>Satış</th>
                                            <th>Dönüşüm Oranı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($conversionRates as $index => $conv)
                                        @if($conv['views'] > 0)
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td><strong>{{ $conv['product']->name }}</td>
                                            <td><span class="badge badge-primary">{{ number_format($conv['views']) }}</span></td>
                                            <td><span class="badge badge-success">{{ number_format($conv['purchases']) }}</span></td>
                                            <td>
                                                <span class="badge {{ $conv['conversion_rate'] >= 5 ? 'badge-success' : ($conv['conversion_rate'] >= 1 ? 'badge-warning' : 'badge-secondary') }}">
                                                    {{ $conv['conversion_rate'] }}%
                                                </span>
                                            </td>
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-tags text-primary"></i> Kategori Bazlı Satış</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Kategori</th>
                                            <th>Satılan Adet</th>
                                            <th>Toplam Gelir</th>
                                            <th>Ürün Çeşidi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($salesByCategory as $index => $cat)
                                        @if($cat['total_qty'] > 0)
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td><strong>{{ $cat['category']->name }}</strong></td>
                                            <td><span class="badge badge-success">{{ number_format($cat['total_qty']) }}</span></td>
                                            <td><span class="badge badge-primary">{{ number_format($cat['total_revenue'], 2) }} TL</span></td>
                                            <td><span class="badge badge-info">{{ number_format($cat['product_count']) }}</span></td>
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Sales -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-chart-line text-success"></i> Günlük Satışlar</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Sipariş Sayısı</th>
                                            <th>Gelir</th>
                                            <th>Satılan Ürün</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_reverse(array_slice($dailySales, -14)) as $sale)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($sale['date'])->format('d.m.Y') }}</td>
                                            <td><span class="badge badge-primary">{{ $sale['order_count'] }}</span></td>
                                            <td><span class="badge badge-success">{{ number_format($sale['revenue'], 2) }} TL</span></td>
                                            <td><span class="badge badge-info">{{ $sale['items_sold'] }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($dailySales) == 0)
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x"></i>
                                <p>Henüz günlük satış verisi bulunmuyor.</p>
                            </div>
                            @endif
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