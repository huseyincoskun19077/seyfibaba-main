@extends('admin.master_layout')
@section('title')
<title>Satıcı Ürün Özeti</title>
@endsection
@section('admin-content')
@php
  $kycLabels = [
    'not_submitted' => 'Belge Yok',
    'pending' => 'Onay Bekliyor',
    'approved' => 'Onaylı',
    'rejected' => 'Reddedildi',
  ];
  $kycBadges = [
    'not_submitted' => 'badge-secondary',
    'pending' => 'badge-warning',
    'approved' => 'badge-success',
    'rejected' => 'badge-danger',
  ];
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Satıcı Ürün Özeti</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Kontrol Paneli</a></div>
        <div class="breadcrumb-item">Satıcı Ürün Özeti</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-store"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Aktif Satıcı</h4></div>
              <div class="card-body">{{ $summary['seller_count'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-box"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Ürün Ekleyen Satıcı</h4></div>
              <div class="card-body">{{ $summary['with_products'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-cubes"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam Satıcı Ürünü</h4></div>
              <div class="card-body">{{ $summary['total_products'] }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <form method="GET" class="row align-items-end">
            <div class="col-md-4">
              <label class="mb-1">Satıcı / Mağaza Ara</label>
              <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ad, e-posta veya mağaza adı">
            </div>
            <div class="col-md-3">
              <label class="mb-1">KYC Durumu</label>
              <select name="kyc_status" class="form-control">
                <option value="all" {{ request('kyc_status', 'all') === 'all' ? 'selected' : '' }}>Tümü</option>
                @foreach($kycLabels as $value => $label)
                  <option value="{{ $value }}" {{ request('kyc_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="mb-1">Sıralama</label>
              <select name="sort" class="form-control">
                <option value="products_desc" {{ ($sort ?? 'products_desc') === 'products_desc' ? 'selected' : '' }}>Ürün sayısı (çok → az)</option>
                <option value="products_asc" {{ ($sort ?? '') === 'products_asc' ? 'selected' : '' }}>Ürün sayısı (az → çok)</option>
                <option value="name" {{ ($sort ?? '') === 'name' ? 'selected' : '' }}>Mağaza adı</option>
                <option value="kyc" {{ ($sort ?? '') === 'kyc' ? 'selected' : '' }}>KYC durumu</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Filtrele</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped" id="dataTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Satıcı</th>
                  <th>KYC</th>
                  <th class="text-center">Toplam Ürün</th>
                  <th class="text-center">Onaylı</th>
                  <th class="text-center">Bekleyen</th>
                  <th class="text-center">Aktif</th>
                  <th>İşlem</th>
                </tr>
              </thead>
              <tbody>
                @forelse($sellers as $seller)
                  <tr>
                    <td>{{ $seller->id }}</td>
                    <td>
                      <strong>{{ optional($seller->user)->name ?? '-' }}</strong><br>
                      <span class="text-muted small">{{ $seller->shop_name }}</span><br>
                      <span class="text-muted small">{{ optional($seller->user)->email ?? '-' }}</span>
                    </td>
                    <td>
                      <span class="badge {{ $kycBadges[$seller->kyc_status] ?? 'badge-secondary' }}">
                        {{ $kycLabels[$seller->kyc_status] ?? $seller->kyc_status }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge badge-primary badge-lg">{{ $seller->products_total }}</span>
                    </td>
                    <td class="text-center">{{ $seller->products_approved }}</td>
                    <td class="text-center">
                      @if($seller->products_pending > 0)
                        <span class="badge badge-warning">{{ $seller->products_pending }}</span>
                      @else
                        {{ $seller->products_pending }}
                      @endif
                    </td>
                    <td class="text-center">{{ $seller->products_active }}</td>
                    <td>
                      <a href="{{ route('admin.product-by-seller', $seller->id) }}" class="btn btn-info btn-sm" title="Ürünleri gör">
                        <i class="fas fa-box-open"></i>
                      </a>
                      <a href="{{ route('admin.kyc.show', $seller->id) }}" class="btn btn-primary btn-sm" title="KYC incele">
                        <i class="fas fa-id-card"></i>
                      </a>
                      <a href="{{ route('admin.seller-show', $seller->id) }}" class="btn btn-secondary btn-sm" title="Satıcı profili">
                        <i class="fas fa-user"></i>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">Filtreye uygun satıcı bulunamadı.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
