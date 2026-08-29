@extends('admin.master_layout')
@section('title')
<title>Ürün Görüntülemeleri</title>
@endsection

@section('admin-content')
<div class=main-content>
    <section class=section>
        <div class="section-header">
            <h1>Ürün Görüntülemeleri</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Envanter</div>
                <div class="breadcrumb-item">Ürün Görüntülemeleri</div>
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
                                <a href="{{ route('admin.product-views.index', ['filter' => 'daily']) }}" 
                                   class="btn {{ $filter == 'daily' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-day"></i> Günlük
                                </a>
                                <a href="{{ route('admin.product-views.index', ['filter' => 'weekly']) }}" 
                                   class="btn {{ $filter == 'weekly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-week"></i> Haftalık
                                </a>
                                <a href="{{ route('admin.product-views.index', ['filter' => 'monthly']) }}" 
                                   class="btn {{ $filter == 'monthly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-alt"></i> Aylık
                                </a>
                                <a href="{{ route('admin.product-views.index', ['filter' => 'yearly']) }}" 
                                   class="btn {{ $filter == 'yearly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar"></i> Yıllık
                                </a>
                                <a href="{{ route('admin.product-views.index', ['filter' => 'all']) }}" 
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

            <!-- Statistics Cards -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Benzersiz Ziyaretçi</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($sessionStats['uniqueViewers']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Toplam Görüntüleme</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($sessionStats['totalViews']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Ortalama Süre</p>
                                <h3 class="mb-3 font-weight-bold">{{ gmdate('H:i:s', $sessionStats['avgDuration']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">İlgilenen</p>
                                <h3 class="mb-3 font-weight-bold">{{ $sessionStats['engagedCount'] }} <small>({{ $sessionStats['engagementRate'] }}%)</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Most Viewed Products Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-trophy text-warning"></i> En Çok Görüntülenen Ürünler</h4>
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
                                            <th>Benzersiz Ziyaretçi</th>
                                            <th>Toplam Görüntüleme</th>
                                            <th>Ortalama Süre</th>
                                            <th>İlgilenen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($mostViewedProducts as $index => $stat)
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td>
                                                <strong>{{ $stat['product']->name }}</strong>
                                                @if($stat['product']->status == 0)
                                                <span class="badge badge-warning">Pasif</span>
                                                @endif
                                            </td>
                                            <td>{{ $stat['product']->seller ? $stat['product']->seller->shop_name : '-' }}</td>
                                            <td>{{ $stat['product']->category ? $stat['product']->category->name : '-' }}</td>
                                            <td><span class="badge badge-primary">{{ number_format($stat['unique_viewers']) }}</span></td>
                                            <td><span class="badge badge-info">{{ number_format($stat['total_views']) }}</span></td>
                                            <td><span class="badge badge-secondary">{{ gmdate('H:i:s', $stat['avg_duration']) }}</span></td>
                                            <td>
                                                <span class="badge badge-success">{{ $stat['engaged_count'] }}</span>
                                                <small class="text-muted">({{ $stat['engagement_rate'] }}%)</small>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sessions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-history"></i> Son Görüntülemeler</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Ürün</th>
                                            <th>IP Adresi</th>
                                            <th>Süre</th>
                                            <th>İlgilendi</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($viewSessions->take(50) as $index => $session)
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td>{{ $session->product ? $session->product->name : 'Ürün Silinmiş' }}</td>
                                            <td>{{ $session->ip_address ?? '-' }}</td>
                                            <td>{{ gmdate('H:i:s', $session->duration) }}</td>
                                            <td>
                                                @if($session->engaged)
                                                <span class="badge badge-success">Evet</span>
                                                @else
                                                <span class="badge badge-secondary">Hayır</span>
                                                @endif
                                            </td>
                                            <td>{{ $session->created_at->format('d.m.Y H:i') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($viewSessions->count() == 0)
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x"></i>
                                <p>Henüz görüntüleme verisi bulunmuyor.</p>
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