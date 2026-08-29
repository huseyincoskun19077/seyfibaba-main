@extends('admin.master_layout')
@section('title')
<title>Sepete Eklenenler</title>
@endsection

@section('admin-content')
<div class=main-content>
    <section class=section>
        <div class="section-header">
            <h1>Sepete Eklenenler</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Envanter</div>
                <div class="breadcrumb-item">Sepete Eklenenler</div>
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
                                <a href="{{ route('admin.product-carts.index', ['filter' => 'daily']) }}" 
                                   class="btn {{ $filter == 'daily' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-day"></i> Günlük
                                </a>
                                <a href="{{ route('admin.product-carts.index', ['filter' => 'weekly']) }}" 
                                   class="btn {{ $filter == 'weekly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-week"></i> Haftalık
                                </a>
                                <a href="{{ route('admin.product-carts.index', ['filter' => 'monthly']) }}" 
                                   class="btn {{ $filter == 'monthly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar-alt"></i> Aylık
                                </a>
                                <a href="{{ route('admin.product-carts.index', ['filter' => 'yearly']) }}" 
                                   class="btn {{ $filter == 'yearly' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="fas fa-calendar"></i> Yıllık
                                </a>
                                <a href="{{ route('admin.product-carts.index', ['filter' => 'all']) }}" 
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
                                <p class="card-text text-muted">Sepete Eklenen Ürün Sayısı</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($cartStats['totalItems']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Sepete Ekleyen Kullanıcı</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($cartStats['uniqueCarts']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Farklı Ürün Eklendi</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($cartStats['uniqueProducts']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Toplam Sepet İşlemi</p>
                                <h3 class="mb-3 font-weight-bold">{{ number_format($cartStats['totalCarts']) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Most Added Products Table -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-shopping-cart text-info"></i> En Çok Sepete Eklenen Ürünler</h4>
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
                                            <th>Ekleyen Kullanıcı</th>
                                            <th>Toplam Adet</th>
                                            <th>Sepet İşlemi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($mostAddedProducts as $index => $stat)
                                        @if($stat['cart_count'] > 0)
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
                                            <td><span class="badge badge-primary">{{ number_format($stat['unique_users']) }}</span></td>
                                            <td><span class="badge badge-info">{{ number_format($stat['total_qty']) }}</span></td>
                                            <td><span class="badge badge-secondary">{{ number_format($stat['cart_count']) }}</span></td>
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(collect($mostAddedProducts)->sum('cart_count') == 0)
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x"></i>
                                <p>Henüz sepete ekleme verisi bulunmuyor.</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Cart Activities -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-history"></i> Son Sepet Eklemeleri</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Ürün</th>
                                            <th>Adet</th>
                                            <th>Kullanıcı</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($shoppingCarts->take(50) as $index => $cart)
                                        <tr>
                                            <td>{{ ++$index }}</td>
                                            <td>{{ $cart->product ? $cart->product->name : 'Ürün Silinmiş' }}</td>
                                            <td><span class="badge badge-info">{{ $cart->qty }}</span></td>
                                            <td>{{ $cart->user_id ? 'Üye: ' . $cart->user_id : ($cart->session_id ? 'Session' : 'Misafir') }}</td>
                                            <td>{{ $cart->created_at->format('d.m.Y H:i') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($shoppingCarts->count() == 0)
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x"></i>
                                <p>Henüz sepete ekleme verisi bulunmuyor.</p>
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