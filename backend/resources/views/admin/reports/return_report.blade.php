@extends('admin.master_layout')
@section('title')<title>İade Raporu</title>@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header"><h1>İade Raporu</h1></div>

    <div class="section-body">
      {{-- Filtreler --}}
      <div class="card">
        <div class="card-body py-3">
          <form action="{{ route('admin.report.returns') }}" method="GET" class="d-flex flex-wrap align-items-end" style="gap:10px;">
            <div>
              <label class="small mb-1">Başlangıç</label>
              <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div>
              <label class="small mb-1">Bitiş</label>
              <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div>
              <label class="small mb-1">Durum</label>
              <select name="status" class="form-control form-control-sm">
                <option value="">Tümü</option>
                @foreach($statusMap as $key => $label)
                  <option value="{{ $key }}" {{ request('status') === (string)$key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Filtrele</button>
            </div>
            <div>
              <a href="{{ route('admin.report.returns', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-csv mr-1"></i>CSV İndir
              </a>
            </div>
          </form>
        </div>
      </div>

      {{-- Özet --}}
      <div class="row">
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-danger"><i class="fas fa-undo"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Toplam İade</h4></div><div class="card-body">{{ $summary->total_returns }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-percentage"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>İade Oranı</h4></div><div class="card-body">%{{ $summary->return_rate }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-money-bill-alt"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>İade Tutarı</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_refund, 2) }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-hourglass-half"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Bekleyen</h4></div><div class="card-body">{{ $summary->pending }}</div></div>
          </div>
        </div>
      </div>

      <div class="row">
        {{-- İade Nedeni Kırılımı --}}
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h3>İade Nedenleri</h3></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-sm">
                  <thead><tr><th>Neden</th><th class="text-right">Adet</th></tr></thead>
                  <tbody>
                    @forelse($reasonBreakdown as $r)
                    <tr>
                      <td>{{ $r->reason }}</td>
                      <td class="text-right"><strong>{{ $r->count }}</strong></td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center text-muted">Veri yok</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {{-- Satıcı Bazlı İade --}}
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h3>Satıcı Bazlı İade</h3></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-sm">
                  <thead><tr><th>Satıcı</th><th class="text-right">İade</th><th class="text-right">Tutar</th></tr></thead>
                  <tbody>
                    @forelse($sellerBreakdown as $s)
                    <tr>
                      <td>{{ $s->shop_name ?? 'Admin' }}</td>
                      <td class="text-right">{{ $s->return_count }}</td>
                      <td class="text-right">{{ $setting->currency_icon }}{{ number_format($s->total_refund, 2) }}</td>
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

      {{-- İade Detay Tablosu --}}
      <div class="card">
        <div class="card-header"><h3>İade Detayları</h3></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th>Tarih</th>
                  <th>Sipariş No</th>
                  <th>Müşteri</th>
                  <th>Ürün</th>
                  <th>Satıcı</th>
                  <th>Neden</th>
                  <th class="text-right">Tutar</th>
                  <th>Durum</th>
                </tr>
              </thead>
              <tbody>
                @forelse($returns as $r)
                <tr>
                  <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d.m.Y') }}</td>
                  <td><strong>{{ $r->order_number }}</strong></td>
                  <td>{{ $r->customer_name }}</td>
                  <td>
                    @if($r->thumb_image)
                      <img src="{{ asset($r->thumb_image) }}" width="25" height="25" class="rounded mr-1" style="object-fit:cover;">
                    @endif
                    <span class="text-truncate" style="max-width:150px;display:inline-block;vertical-align:middle;">{{ $r->product_name ?? '-' }}</span>
                  </td>
                  <td>{{ $r->shop_name ?? 'Admin' }}</td>
                  <td>{{ $r->reason }}</td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($r->refund_amount, 2) }}</td>
                  <td>
                    @switch($r->status)
                      @case(0) <span class="badge badge-warning">Bekliyor</span> @break
                      @case(1) <span class="badge badge-info">Onaylandı</span> @break
                      @case(2) <span class="badge badge-danger">Reddedildi</span> @break
                      @case(3) <span class="badge badge-success">Tamamlandı</span> @break
                    @endswitch
                  </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Bu filtreye uygun iade bulunamadı.</td></tr>
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
