@extends('admin.master_layout')
@section('title')<title>Satıcı Raporu</title>@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Satıcı Raporu</h1>
    </div>

    <div class="section-body">
      {{-- Filtreler --}}
      <div class="card">
        <div class="card-body py-3">
          <form action="{{ route('admin.report.sellers') }}" method="GET" class="d-flex flex-wrap align-items-end" style="gap:10px;">
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
              <a href="{{ route('admin.report.sellers', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-sm btn-success">
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
            <div class="card-icon bg-primary"><i class="fas fa-store"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Aktif Satıcı</h4></div><div class="card-body">{{ $summary->seller_count }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-coins"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Toplam Satış</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_sales, 2) }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-percentage"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Toplam Komisyon</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_commission, 2) }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Satıcı Net</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_net, 2) }}</div></div>
          </div>
        </div>
      </div>

      {{-- Tablo --}}
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th>Satıcı</th>
                  <th>E-posta</th>
                  <th class="text-right">Sipariş</th>
                  <th class="text-right">Satış</th>
                  <th class="text-right">Komisyon</th>
                  <th class="text-right">Net</th>
                  <th class="text-right">Bekleyen</th>
                  <th class="text-right">Ödenen</th>
                </tr>
              </thead>
              <tbody>
                @forelse($sellers as $s)
                <tr>
                  <td><strong>{{ $s->shop_name }}</strong></td>
                  <td>{{ $s->email }}</td>
                  <td class="text-right">{{ $s->order_count }}</td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($s->total_sales, 2) }}</td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($s->total_commission, 2) }}</td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($s->total_net, 2) }}</td>
                  <td class="text-right text-warning">{{ $setting->currency_icon }}{{ number_format($s->pending_net, 2) }}</td>
                  <td class="text-right text-success">{{ $setting->currency_icon }}{{ number_format($s->settled_net, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Bu dönemde satıcı verisi bulunamadı.</td></tr>
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
