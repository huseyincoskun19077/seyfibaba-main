@extends('admin.master_layout')
@section('title')<title>İşlem Raporu</title>@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header"><h1>İşlem Raporu</h1></div>

    <div class="section-body">
      {{-- Filtreler --}}
      <div class="card">
        <div class="card-body py-3">
          <form action="{{ route('admin.report.transactions') }}" method="GET" class="d-flex flex-wrap align-items-end" style="gap:10px;">
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
              <a href="{{ route('admin.report.transactions', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-sm btn-success">
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
            <div class="card-icon bg-primary"><i class="fas fa-exchange-alt"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Toplam İşlem</h4></div><div class="card-body">{{ $summary->total_transactions }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-coins"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Ödenen Hacim</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_paid, 2) }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-check-circle"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Ödenen</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_paid, 2) }}</div></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
            <div class="card-wrap"><div class="card-header"><h4>Bekleyen</h4></div><div class="card-body">{{ $setting->currency_icon }}{{ number_format($summary->total_pending, 2) }}</div></div>
          </div>
        </div>
      </div>

      <div class="row">
        {{-- Ödeme Yöntemi Kırılımı --}}
        <div class="col-lg-5">
          <div class="card">
            <div class="card-header"><h3>Ödeme Yöntemi Kırılımı</h3></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-sm">
                  <thead>
                    <tr><th>Yöntem</th><th class="text-right">Sipariş</th><th class="text-right">Toplam</th><th class="text-right">Ödenen</th><th class="text-right">Bekleyen</th></tr>
                  </thead>
                  <tbody>
                    @forelse($paymentBreakdown as $p)
                    <tr>
                      <td><strong>{{ $p->payment_method ?: 'Belirtilmemiş' }}</strong></td>
                      <td class="text-right">{{ $p->order_count }}</td>
                      <td class="text-right">{{ $setting->currency_icon }}{{ number_format($p->total_amount, 2) }}</td>
                      <td class="text-right text-success">{{ $setting->currency_icon }}{{ number_format($p->paid_amount, 2) }}</td>
                      <td class="text-right text-warning">{{ $setting->currency_icon }}{{ number_format($p->pending_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">Veri yok</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {{-- Günlük İşlem Grafiği --}}
        <div class="col-lg-7">
          <div class="card">
            <div class="card-header"><h3>Günlük İşlem Hacmi</h3></div>
            <div class="card-body">
              <canvas id="transactionChart" height="250"></canvas>
            </div>
          </div>
        </div>
      </div>

      {{-- Günlük Detay Tablosu --}}
      <div class="card">
        <div class="card-header"><h3>Günlük Detay</h3></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-sm">
              <thead>
                <tr><th>Tarih</th><th class="text-right">Sipariş</th><th class="text-right">Tutar</th><th class="text-right">Ödenen</th><th class="text-right">Bekleyen</th></tr>
              </thead>
              <tbody>
                @forelse($dailyVolume as $d)
                <tr>
                  <td>{{ \Carbon\Carbon::parse($d->date)->format('d.m.Y') }}</td>
                  <td class="text-right">{{ $d->order_count }}</td>
                  <td class="text-right">{{ $setting->currency_icon }}{{ number_format($d->total_amount, 2) }}</td>
                  <td class="text-right text-success">{{ $d->paid_count }}</td>
                  <td class="text-right text-warning">{{ $d->pending_count }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Bu dönemde işlem bulunamadı.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script src="{{ asset('backend/js/charts.js') }}"></script>
<script>
"use strict";
var ctx = document.getElementById('transactionChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json($chartLabels),
        datasets: [{
            label: 'İşlem Hacmi (₺)',
            data: @json($chartAmounts),
            backgroundColor: 'rgba(103, 119, 239, 0.7)',
            borderColor: '#6777ef',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                ticks: { callback: function(v) { return '₺' + v; } },
                grid: { color: '#D7DCE7', borderDash: [5, 5] }
            },
            x: { grid: { display: false } }
        },
        plugins: {
            legend: { display: false }
        }
    }
});
</script>
@endsection
