@extends('admin.master_layout')
@section('title')
<title>Kullanıcı Analizi</title>
@endsection

@section('admin-content')
<div class=main-content>
    <section class=section>
        <div class="section-header">
            <h1>Kullanıcı Analizi</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Kullanıcı Analizi</div>
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
                                <a href="{{ route('admin.user-analytics.index', ['filter' => 'daily']) }}" 
                                   class="btn {{ $filter == 'daily' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-day"></i> Günlük
                                </a>
                                <a href="{{ route('admin.user-analytics.index', ['filter' => 'weekly']) }}" 
                                   class="btn {{ $filter == 'weekly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-week"></i> Haftalık
                                </a>
                                <a href="{{ route('admin.user-analytics.index', ['filter' => 'monthly']) }}" 
                                   class="btn {{ $filter == 'monthly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-alt"></i> Aylık
                                </a>
                                <a href="{{ route('admin.user-analytics.index', ['filter' => 'yearly']) }}" 
                                   class="btn {{ $filter == 'yearly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar"></i> Yıllık
                                </a>
                                <a href="{{ route('admin.user-analytics.index', ['filter' => 'all']) }}" 
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

            <!-- User Statistics Cards -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Toplam Kullanıcı</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($userStats['totalUsers']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Yeni Kayıt</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($userStats['newUsers']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Aktif Kullanıcı</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($userStats['activeUsers']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Toplam Sipariş</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($userStats['totalOrders']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Segments -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-users"></i> Kullanıcı Segmentasyonu</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Yeni Kullanıcılar ({{ $userSegments['newUsersCount'] }})</span>
                                    <span class="badge badge-primary">{{ $userStats['totalUsers'] > 0 ? round(($userSegments['newUsersCount'] / $userStats['totalUsers']) * 100, 1) : 0 }}%</span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $userStats['totalUsers'] > 0 ? ($userSegments['newUsersCount'] / $userStats['totalUsers']) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Mevcut Kullanıcılar ({{ $userSegments['existingUsersCount'] }})</span>
                                    <span class="badge badge-info">{{ $userStats['totalUsers'] > 0 ? round(($userSegments['existingUsersCount'] / $userStats['totalUsers']) * 100, 1) : 0 }}%</span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $userStats['totalUsers'] > 0 ? ($userSegments['existingUsersCount'] / $userStats['totalUsers']) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <h4 class="text-primary">{{ number_format($userSegments['registeredPurchasers']) }}</h4>
                                    <small class="text-muted">Üye Alışveriş</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-warning">{{ number_format($userSegments['guestPurchasers']) }}</h4>
                                    <small class="text-muted">Misafir Alışveriş</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Repeat Purchase Analysis -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-redo"></i> Tekrar Satın Alma Analizi</h4>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-4">
                                <div class="col-6">
                                    <h3 class="text-success">{{ $repeatPurchaseData['repeatRate'] }}%</h3>
                                    <small class="text-muted">Tekrar Satın Alma Oranı</small>
                                </div>
                                <div class="col-6">
                                    <h3 class="text-info">{{ number_format($repeatPurchaseData['repeatBuyers']) }}</h3>
                                    <small class="text-muted">Tekrar Alan Kullanıcı</small>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Sipariş Sayısı</th>
                                            <th>Kullanıcı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1 Sipariş</td>
                                            <td><span class="badge badge-primary">{{ number_format($repeatPurchaseData['ordersByCount']['1']) }}</span></td>
                                        </tr>
                                        <tr>
                                            <td>2 Sipariş</td>
                                            <td><span class="badge badge-info">{{ number_format($repeatPurchaseData['ordersByCount']['2']) }}</span></td>
                                        </tr>
                                        <tr>
                                            <td>3 Sipariş</td>
                                            <td><span class="badge badge-warning">{{ number_format($repeatPurchaseData['ordersByCount']['3']) }}</span></td>
                                        </tr>
                                        <tr>
                                            <td>4+ Sipariş</td>
                                            <td><span class="badge badge-success">{{ number_format($repeatPurchaseData['ordersByCount']['4+']) }}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-crown text-warning"></i> En Fazla Harcayan Müşteriler</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Müşteri</th>
                                            <th>E-posta</th>
                                            <th>Sipariş Sayısı</th>
                                            <th>Toplam Harcama</th>
                                            <th>Kayıt Tarihi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topCustomers as $index => $customer)
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td>
                                                <strong>{{ $customer['user']->name }}</strong>
                                                @if($customer['user']->status == 0)
                                                <span class="badge badge-warning">Pasif</span>
                                                @endif
                                            </td>
                                            <td>{{ $customer['user']->email }}</td>
                                            <td><span class="badge badge-primary">{{ $customer['order_count'] }}</span></td>
                                            <td><span class="badge badge-success">{{ number_format($customer['total_spent'], 2) }} TL</span></td>
                                            <td>{{ $customer['user']->created_at->format('d.m.Y') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($topCustomers) == 0)
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x"></i>
                                <p>Henüz sipariş veren kullanıcı bulunmuyor.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-user-plus"></i> Son Kayı olan Kullanıcılar</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Kullanıcı</th>
                                            <th>E-posta</th>
                                            <th>Telefon</th>
                                            <th>Durum</th>
                                            <th>Kayıt Tarihi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users->take(20) as $index => $user)
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td><strong>{{ $user->name }}</strong></td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->phone ?? '-' }}</td>
                                            <td>
                                                @if($user->status == 1)
                                                <span class="badge badge-success">Aktif</span>
                                                @else
                                                <span class="badge badge-warning">Pasif</span>
                                                @endif
                                            </td>
                                            <td>{{ $user->created_at->format('d.m.Y') }}</td>
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
</style>
@endsection