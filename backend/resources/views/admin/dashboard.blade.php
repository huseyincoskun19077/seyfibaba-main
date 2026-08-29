@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Dashboard')}}</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
      <div class="section-header">
        <h1>{{__('admin.Dashboard')}}</h1>
      </div>

      <div class="section-body">

      {{-- #52 Tarih Filtresi --}}
      <div class="card mb-4">
        <div class="card-body py-3">
          <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
              <a href="{{ route('admin.dashboard', ['period' => 'today']) }}"
                 class="btn btn-sm {{ $period == 'today' ? 'btn-primary' : 'btn-outline-primary' }}">Bugün</a>
              <a href="{{ route('admin.dashboard', ['period' => 'this_week']) }}"
                 class="btn btn-sm {{ $period == 'this_week' ? 'btn-primary' : 'btn-outline-primary' }}">Bu Hafta</a>
              <a href="{{ route('admin.dashboard', ['period' => 'this_month']) }}"
                 class="btn btn-sm {{ $period == 'this_month' ? 'btn-primary' : 'btn-outline-primary' }}">Bu Ay</a>
              <a href="{{ route('admin.dashboard', ['period' => 'this_year']) }}"
                 class="btn btn-sm {{ $period == 'this_year' ? 'btn-primary' : 'btn-outline-primary' }}">Bu Yıl</a>
              <span class="text-muted mx-2">|</span>
              <form action="{{ route('admin.dashboard') }}" method="GET" class="d-flex align-items-center" style="gap: 6px;">
                <input type="hidden" name="period" value="custom">
                <input type="date" name="start_date" class="form-control form-control-sm" style="width:140px;"
                       value="{{ $period == 'custom' ? $dateFrom->format('Y-m-d') : '' }}">
                <span class="text-muted">-</span>
                <input type="date" name="end_date" class="form-control form-control-sm" style="width:140px;"
                       value="{{ $period == 'custom' ? $dateTo->format('Y-m-d') : '' }}">
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-filter mr-1"></i>Filtrele</button>
              </form>
            </div>
            <span class="badge badge-light text-dark mt-2 mt-md-0" style="font-size:13px;">
              <i class="fas fa-calendar-alt mr-1"></i> {{ $periodLabel }}
            </span>
          </div>
        </div>
      </div>

      {{-- #46 Gelir Özeti — büyük kartlar --}}
      <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-coins"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Satış ({{ $periodLabel }})</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($filteredEarning, 2) }}</div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Admin Komisyonu (Ödenen)</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($commissionStats->settled_commission, 2) }}</div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-store"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Satıcı Payı (Ödenen)</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($commissionStats->settled_seller_net, 2) }}</div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-undo"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>İadeler</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($commissionStats->refunded_commission, 2) }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- #45 Komisyon Kartları --}}
      <h2 class="d-title">Komisyon Detayı</h2>
      <div class="row">
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-percentage"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam Komisyon</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($commissionStats->total_commission, 2) }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Bekleyen Komisyon</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($commissionStats->pending_commission, 2) }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Ödenen Komisyon</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($commissionStats->settled_commission, 2) }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-danger"><i class="fas fa-calendar-alt"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>İade Komisyon</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($commissionStats->refunded_commission, 2) }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- #51 Sipariş Durumu Dağılımı --}}
      <h2 class="d-title">Siparişler ({{ $periodLabel }})</h2>
      <div class="row">
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-shopping-cart"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam</h4></div>
              <div class="card-body">{{ $filteredTotal }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-hourglass-half"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Bekleyen</h4></div>
              <div class="card-body">{{ $filteredPending }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-spinner"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>İşlemde</h4></div>
              <div class="card-body">{{ $filteredProgress }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-truck"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Teslim Edildi</h4></div>
              <div class="card-body">{{ $filteredDelivered }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-check"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Tamamlanan</h4></div>
              <div class="card-body">{{ $filteredComplete }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-danger"><i class="fas fa-times"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>İptal</h4></div>
              <div class="card-body">{{ $filteredDeclined }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Kazanç Detayı --}}
      <h2 class="d-title">Tüm Zamanlar</h2>
      <div class="row">
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-dark"><i class="fas fa-chart-line"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam Satış</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($allTimeEarning, 2) }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-dark"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam Komisyon (Ödenen)</h4></div>
              <div class="card-body">{{ $setting->currency_icon }}{{ number_format($allTimeStats->settled_commission, 2) }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-dark"><i class="fas fa-shopping-bag"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam Sipariş (Ödenen)</h4></div>
              <div class="card-body">{{ $allTimePaidOrder }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-hourglass-half"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Bekleyen Sipariş</h4></div>
              <div class="card-body">{{ $allTimePendingOrder }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between py-2 px-3 mb-4">
        <div><strong>Tüm Sipariş:</strong> {{ $allTimeTotalOrder }}</div>
        <div><strong>Bekleyen Ödeme Tutarı:</strong> {{ $setting->currency_icon }}{{ number_format($filteredPendingEarning, 2) }}</div>
      </div>

      </div>

      {{-- #47 Aylık Satış + Komisyon Trend Grafiği --}}
      <div class="section-body row mt-4">
        <div class="col-12">
          <div class="card p-4">
            <div class="card-header">
              <h3>Aylık Satış & Komisyon Trendi</h3>
            </div>
            <div class="crancy-chart__inside crancy-chart__three">
              <canvas id="myChart_recent_statics"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="section-body">
        <div class="row mt-4">

          {{-- #48 En Çok Satan Ürünler --}}
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header"><h3>En Çok Satan Ürünler</h3></div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped table-sm">
                    <thead>
                      <tr>
                        <th>Ürün</th>
                        <th class="text-right">Satış</th>
                        <th class="text-right">Gelir</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($topProducts as $p)
                      <tr>
                        <td class="d-flex align-items-center">
                          <img src="{{ asset($p->thumb_image) }}" width="35" height="35" class="rounded mr-2" style="object-fit:cover;">
                          <span class="text-truncate" style="max-width:180px;">{{ $p->name }}</span>
                        </td>
                        <td class="text-right">{{ $p->total_sold }} adet</td>
                        <td class="text-right">{{ $setting->currency_icon }}{{ number_format($p->total_revenue, 2) }}</td>
                      </tr>
                      @empty
                      <tr><td colspan="3" class="text-center text-muted">Henüz satış yok</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          {{-- #49 En Çok Satış Yapan Satıcılar --}}
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header"><h3>En Çok Satış Yapan Satıcılar</h3></div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped table-sm">
                    <thead>
                      <tr>
                        <th>Satıcı</th>
                        <th class="text-right">Satış</th>
                        <th class="text-right">Komisyon</th>
                        <th class="text-right">Sipariş</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($topSellers as $s)
                      <tr>
                        <td>{{ $s->shop_name }}</td>
                        <td class="text-right">{{ $setting->currency_icon }}{{ number_format($s->total_sales, 2) }}</td>
                        <td class="text-right">{{ $setting->currency_icon }}{{ number_format($s->total_commission, 2) }}</td>
                        <td class="text-right">{{ $s->order_count }}</td>
                      </tr>
                      @empty
                      <tr><td colspan="4" class="text-center text-muted">Henüz satıcı satışı yok</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- #50 Son Siparişler --}}
        <div class="row mt-4">
          <div class="col">
            <div class="card">
              <div class="card-header"><h3>Bugünün Yeni Siparişleri</h3></div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped" id="dataTable">
                    <thead>
                      <tr>
                        <th width="5%">#</th>
                        <th>Müşteri</th>
                        <th>Sipariş No</th>
                        <th>Tarih</th>
                        <th>Adet</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Ödeme</th>
                        <th>İşlem</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($todayOrders as $index => $order)
                      <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $order->user->name ?? '-' }}</td>
                        <td>{{ $order->order_id }}</td>
                        <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        <td>{{ $order->displayProductQty() }}</td>
                        <td>{{ $setting->currency_icon }}{{ $order->total_amount }}</td>
                        <td>
                          @if ($order->order_status == 1)
                            <span class="badge badge-info">İşlemde</span>
                          @elseif ($order->order_status == 2)
                            <span class="badge badge-primary">Teslim Edildi</span>
                          @elseif ($order->order_status == 3)
                            <span class="badge badge-success">Tamamlandı</span>
                          @elseif ($order->order_status == 4)
                            <span class="badge badge-danger">İptal</span>
                          @else
                            <span class="badge badge-warning">Bekliyor</span>
                          @endif
                        </td>
                        <td>
                          @if($order->payment_status == 1)
                            <span class="badge badge-success">Ödendi</span>
                          @else
                            <span class="badge badge-danger">Bekliyor</span>
                          @endif
                        </td>
                        <td>
                          <a href="{{ route('admin.order-show', $order->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>
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

        {{-- Genel Sayılar --}}
        <h2 class="d-title">Genel</h2>
        <div class="row">
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card card-statistic-1">
              <div class="card-icon bg-info"><i class="fas fa-box"></i></div>
              <div class="card-wrap"><div class="card-header"><h4>Ürünler</h4></div><div class="card-body">{{ $totalProduct }}</div></div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card card-statistic-1">
              <div class="card-icon bg-info"><i class="fas fa-star"></i></div>
              <div class="card-wrap"><div class="card-header"><h4>Yorumlar</h4></div><div class="card-body">{{ $reviews }}</div></div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card card-statistic-1">
              <div class="card-icon bg-warning"><i class="far fa-user"></i></div>
              <div class="card-wrap"><div class="card-header"><h4>Satıcılar</h4></div><div class="card-body">{{ $sellers }}</div></div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card card-statistic-1">
              <div class="card-icon bg-warning"><i class="fas fa-users"></i></div>
              <div class="card-wrap"><div class="card-header"><h4>Kullanıcılar</h4></div><div class="card-body">{{ $users }}</div></div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card card-statistic-1">
              <div class="card-icon bg-warning"><i class="fas fa-th-large"></i></div>
              <div class="card-wrap"><div class="card-header"><h4>Kategoriler</h4></div><div class="card-body">{{ $categories }}</div></div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card card-statistic-1">
              <div class="card-icon bg-warning"><i class="fas fa-tags"></i></div>
              <div class="card-wrap"><div class="card-header"><h4>Markalar</h4></div><div class="card-body">{{ $brands }}</div></div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <a href="{{ route('admin.product-views.index') }}" class="text-dark">
              <div class="card card-statistic-1">
                <div class="card-icon bg-primary"><i class="fas fa-eye"></i></div>
                <div class="card-wrap"><div class="card-header"><h4>Bugün görüntüleme</h4></div><div class="card-body">{{ $todayProductViews }}</div></div>
              </div>
            </a>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <a href="{{ route('admin.product-carts.index') }}" class="text-dark">
              <div class="card card-statistic-1">
                <div class="card-icon bg-success"><i class="fas fa-shopping-cart"></i></div>
                <div class="card-wrap"><div class="card-header"><h4>Bugün sepete ekleme</h4></div><div class="card-body">{{ $todayCartAdds }}</div></div>
              </div>
            </a>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card card-statistic-1">
              <div class="card-icon bg-danger"><i class="fas fa-mobile-alt"></i></div>
              <div class="card-wrap"><div class="card-header"><h4>Mobil aktif (15 dk)</h4></div><div class="card-body">{{ $mobileActiveUsers }}</div></div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <a href="{{ route('admin.second-hand.index') }}" class="text-dark">
              <div class="card card-statistic-1">
                <div class="card-icon bg-info"><i class="fas fa-recycle"></i></div>
                <div class="card-wrap"><div class="card-header"><h4>İkinci el yayında / bekleyen</h4></div><div class="card-body">{{ $secondHandActive }} / {{ $secondHandPending }}</div></div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>

<script>
    function deleteData(id){
        $("#deleteForm").attr("action",'{{ url("admin/delete-order/") }}'+"/"+id)
    }
</script>

<script src="{{ asset('backend/js/charts.js') }}"></script>
<script>
    "use strict";

    let salesData = @json($chartSales);
    let commissionData = @json($chartCommissions);
    let dateLabels = @json($chartLabels);

    const ctx = document.getElementById('myChart_recent_statics').getContext('2d');

    const gradientSales = ctx.createLinearGradient(0, 0, 0, 300);
    gradientSales.addColorStop(0, 'rgba(103, 119, 239, 0.3)');
    gradientSales.addColorStop(1, 'rgba(103, 119, 239, 0.01)');

    const gradientCommission = ctx.createLinearGradient(0, 0, 0, 300);
    gradientCommission.addColorStop(0, 'rgba(252, 84, 75, 0.3)');
    gradientCommission.addColorStop(1, 'rgba(252, 84, 75, 0.01)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [
                {
                    label: 'Satış (₺)',
                    data: salesData,
                    backgroundColor: gradientSales,
                    borderColor: '#6777ef',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 3,
                    fill: true,
                },
                {
                    label: 'Komisyon (₺)',
                    data: commissionData,
                    backgroundColor: gradientCommission,
                    borderColor: '#fc544b',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 3,
                    fill: true,
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                x: {
                    ticks: { color: '#9AA2B1' },
                    grid: { display: false },
                },
                y: {
                    ticks: {
                        color: '#5D6A83',
                        callback: function(value) { return '₺' + value; }
                    },
                    grid: { color: '#D7DCE7', borderDash: [5, 5] },
                },
            },
            plugins: {
                tooltip: {
                    padding: 10,
                    backgroundColor: '#fff',
                    titleColor: '#000',
                    bodyColor: '#2F3032',
                    cornerRadius: 8,
                    borderColor: '#e0e0e0',
                    borderWidth: 1,
                },
                legend: {
                    position: 'bottom',
                    display: true,
                    labels: { usePointStyle: true, padding: 20 }
                },
            }
        }
    });
</script>

@endsection
