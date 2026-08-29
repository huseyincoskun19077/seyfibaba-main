@extends('admin.master_layout')
@section('title')
<title>Ürün İstatistikleri</title>
@endsection

@section('admin-content')
<div class=main-content>
    <section class=section>
        <div class="section-header">
            <h1>Ürün İstatistikleri</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Ürün İstatistikleri</div>
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
                                <a href="{{ route('admin.product-stats.index', ['filter' => 'daily']) }}" 
                                   class="btn {{ $filter == 'daily' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-day"></i> Günlük
                                </a>
                                <a href="{{ route('admin.product-stats.index', ['filter' => 'weekly']) }}" 
                                   class="btn {{ $filter == 'weekly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-week"></i> Haftalık
                                </a>
                                <a href="{{ route('admin.product-stats.index', ['filter' => 'monthly']) }}" 
                                   class="btn {{ $filter == 'monthly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-alt"></i> Aylık
                                </a>
                                <a href="{{ route('admin.product-stats.index', ['filter' => 'yearly']) }}" 
                                   class="btn {{ $filter == 'yearly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar"></i> Yıllık
                                </a>
                                <a href="{{ route('admin.product-stats.index', ['filter' => 'all']) }}" 
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

            <!-- Filtered Statistics Cards Row 1 -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Görüntülemeler</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($filteredStats['views']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Sepete Eklemeler</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($filteredStats['cartAdds']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Satın Almalar</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($filteredStats['purchases']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Sepetteki Ürünler</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($filteredStats['inCarts']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Time Statistics Cards Row 2 -->
            <div class="row mt-2">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Tüm Zaman İstatistikleri:</strong> 
                        Toplam Görüntüleme: {{ number_format($totalViews) }} | 
                        Aylık: {{ number_format($monthlyViews) }} | 
                        Yıllık: {{ number_format($yearlyViews) }} | 
                        Sepette: {{ number_format($inCarts) }} | 
                        İstek Listesi: {{ number_format($inWishlists) }}
                    </div>
                </div>
            </div>

            <!-- Product View Sessions Summary (links to dedicated page) -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-eye"></i> Ürün Görüntülemeleri</h4>
                            <a href="{{ route('admin.product-views.index') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-external-link-alt"></i> Detaylı Görüntüle
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon bg-primary">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h5>{{ number_format($sessionStats['uniqueViewers']) }}</h5>
                                            <p> Benzersiz Ziyaretçi</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon bg-info">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h5>{{ gmdate('H:i:s', $sessionStats['totalDuration']) }}</h5>
                                            <p>Toplam Süre</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon bg-warning">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h5>{{ gmdate('H:i:s', $sessionStats['avgDuration']) }}</h5>
                                            <p>Ortalama Süre</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon bg-success">
                                            <i class="fas fa-heart"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h5>{{ $sessionStats['engagedCount'] }}</h5>
                                            <p>İlgilenen ({{ $sessionStats['engagementRate'] }}%)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Ürün Listesi</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Ürün Adı</th>
                                            <th>Satıcı</th>
                                            <th>Kategori</th>
                                            <th>Görüntüleme</th>
                                            <th>Sepete Eklenme</th>
                                            <th>Satın Alma</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($products as $index => $product)
                                        <?php
                                            $view = $productViews->firstWhere('product_id', $product->id);
                                        ?>
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td>
                                                <strong>{{ $product->name }}</strong>
                                                @if($product->status == 0)
                                                <span class="badge badge-warning">Pasif</span>
                                                @endif
                                            </td>
                                            <td>{{ $product->seller ? $product->seller->shop_name : '-' }}</td>
                                            <td>{{ $product->category ? $product->category->name : '-' }}</td>
                                            <td><span class="badge badge-primary">{{ $view ? $view->view_count : 0 }}</span></td>
                                            <td><span class="badge badge-info">{{ $view ? $view->add_to_cart_count : 0 }}</span></td>
                                            <td><span class="badge badge-success">{{ $view ? $view->purchase_count : 0 }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
.stat-card {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}
.stat-icon.bg-primary { background: #6776ff; color: white; }
.stat-icon.bg-info { background: #0dcaf0; color: white; }
.stat-icon.bg-warning { background: #ffc107; color: white; }
.stat-icon.bg-success { background: #198754; color: white; }
.stat-icon i { font-size: 20px; }
.stat-info h5 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}
.stat-info p {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}
</style>
@endsection