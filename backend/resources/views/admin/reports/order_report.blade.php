@extends('admin.master_layout')
@section('title')<title>Sipariş Raporu</title>@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Sipariş Raporu</h1>
    </div>

    <div class="section-body">
      {{-- Filtreler --}}
      <div class="card">
        <div class="card-body py-3">
          <form action="{{ route('admin.report.orders') }}" method="GET" class="d-flex flex-wrap align-items-end" style="gap:10px;">
            <div>
              <label class="small mb-1">Başlangıç</label>
              <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div>
              <label class="small mb-1">Bitiş</label>
              <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div>
              <label class="small mb-1">Sipariş Durumu</label>
              <select name="order_status" class="form-control form-control-sm">
                <option value="">Tümü</option>
                <option value="0" {{ request('order_status') === '0' ? 'selected' : '' }}>Bekliyor</option>
                <option value="1" {{ request('order_status') === '1' ? 'selected' : '' }}>İşlemde</option>
                <option value="2" {{ request('order_status') === '2' ? 'selected' : '' }}>Teslim Edildi</option>
                <option value="3" {{ request('order_status') === '3' ? 'selected' : '' }}>Tamamlandı</option>
                <option value="4" {{ request('order_status') === '4' ? 'selected' : '' }}>İptal</option>
              </select>
            </div>
            <div>
              <label class="small mb-1">Ödeme</label>
              <select name="payment_status" class="form-control form-control-sm">
                <option value="">Tümü</option>
                <option value="0" {{ request('payment_status') === '0' ? 'selected' : '' }}>Bekliyor</option>
                <option value="1" {{ request('payment_status') === '1' ? 'selected' : '' }}>Ödendi</option>
              </select>
            </div>
            <div>
              <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i>Filtrele</button>
            </div>
            <div>
              <a href="{{ route('admin.report.orders', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-csv mr-1"></i>CSV İndir
              </a>
            </div>
          </form>
        </div>
      </div>

      {{-- Özet Kartları --}}
      <div class="row">
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-shopping-cart"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Sipariş Sayısı</h4></div><div class="card-body">{{ $summary->total_orders }} <small class="text-muted">(ödendi: {{ $summary->paid_orders ?? 0 }})</small></div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-coins"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Ödenen Tutar</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->paid_amount ?? $summary->total_amount, 2) }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-truck"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Toplam Kargo</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_shipping, 2) }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-ticket-alt"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Toplam Kupon</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_coupon, 2) }}</div></div>
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
                  <th>Sipariş No</th>
                  <th>Tarih</th>
                  <th>Müşteri</th>
                  <th class="text-right">Adet</th>
                  <th class="text-right">Tutar</th>
                  <th class="text-right">Kargo</th>
                  <th>Ödeme</th>
                  <th>Durum</th>
                  <th>İşlem</th>
                </tr>
              </thead>
              <tbody>
                @forelse($orders as $order)
                <tr>
                  <td><strong>{{ $order->order_id }}</strong></td>
                  <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                  <td>{{ $order->user->name ?? '-' }}</td>
                  <td class="text-right">{{ $order->displayProductQty() }}</td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($order->total_amount, 2) }}</td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($order->shipping_cost, 2) }}</td>
                  <td>
                    @if($order->payment_status == 1)
                      <span class="badge badge-success">Ödendi</span>
                    @else
                      <span class="badge badge-danger">Bekliyor</span>
                    @endif
                  </td>
                  <td>
                    @switch($order->order_status)
                      @case(1) <span class="badge badge-info">İşlemde</span> @break
                      @case(2) <span class="badge badge-primary">Teslim</span> @break
                      @case(3) <span class="badge badge-success">Tamamlandı</span> @break
                      @case(4) <span class="badge badge-danger">İptal</span> @break
                      @default <span class="badge badge-warning">Bekliyor</span>
                    @endswitch
                  </td>
                  <td>
                    <a href="{{ route('admin.order-show', $order->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>
                  </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">Bu filtreye uygun sipariş bulunamadı.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">{{ $orders->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
