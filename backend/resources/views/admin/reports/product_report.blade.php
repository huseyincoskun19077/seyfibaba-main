@extends('admin.master_layout')
@section('title')<title>Ürün Raporu</title>@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Ürün Raporu</h1>
    </div>

    <div class="section-body">
      {{-- Filtreler --}}
      <div class="card">
        <div class="card-body py-3">
          <form action="{{ route('admin.report.products') }}" method="GET" class="d-flex flex-wrap align-items-end" style="gap:10px;">
            <div>
              <label class="small mb-1">Başlangıç</label>
              <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div>
              <label class="small mb-1">Bitiş</label>
              <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div>
              <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Filtrele</button>
            </div>
            <div>
              <a href="{{ route('admin.report.products', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-csv mr-1"></i>CSV İndir
              </a>
            </div>
          </form>
        </div>
      </div>

      {{-- En Çok Satan Ürünler --}}
      <div class="card">
        <div class="card-header"><h3>En Çok Satan Ürünler</h3></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th>Ürün</th>
                  <th class="text-right">Fiyat</th>
                  <th class="text-right">Satış Adedi</th>
                  <th class="text-right">Gelir</th>
                  <th class="text-right">Sipariş</th>
                </tr>
              </thead>
              <tbody>
                @forelse($products as $p)
                <tr>
                  <td class="d-flex align-items-center">
                    <img src="{{ asset($p->thumb_image) }}" width="35" height="35" class="rounded mr-2" style="object-fit:cover;">
                    <span class="text-truncate" style="max-width:250px;">{{ $p->name }}</span>
                  </td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($p->price, 2) }}</td>
                  <td class="text-right"><strong>{{ $p->total_sold }}</strong></td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($p->total_revenue, 2) }}</td>
                  <td class="text-right">{{ $p->order_count }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Bu dönemde satış verisi bulunamadı.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="row">
        {{-- En Çok Yorumlanan --}}
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header"><h3>En Çok Yorumlanan</h3></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-sm">
                  <thead><tr><th>Ürün</th><th class="text-right">Yorum</th><th class="text-right">Puan</th></tr></thead>
                  <tbody>
                    @forelse($topReviewed as $p)
                    <tr>
                      <td class="d-flex align-items-center">
                        <img src="{{ asset($p->thumb_image) }}" width="30" height="30" class="rounded mr-2" style="object-fit:cover;">
                        <span class="text-truncate" style="max-width:180px;">{{ $p->name }}</span>
                      </td>
                      <td class="text-right">{{ $p->review_count }}</td>
                      <td class="text-right">{{ $p->avg_rating }} <i class="fas fa-star text-warning" style="font-size:11px;"></i></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted">Veri yok</td></tr>
                    @endforelse
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
@endsection
