@extends('seller.master_layout')
@section('title')
<title>İade Talepleri</title>
@endsection
@section('seller-content')
@php
  $statusLabels = [
      0 => ['label' => 'Beklemede', 'class' => 'warning text-dark'],
      1 => ['label' => 'Satıcı Onayladı', 'class' => 'info'],
      2 => ['label' => 'Yönetici Onayladı', 'class' => 'primary'],
      3 => ['label' => 'Teslim Alındı', 'class' => 'info'],
      4 => ['label' => 'İade Tamamlandı', 'class' => 'success'],
      5 => ['label' => 'Satıcı Reddetti', 'class' => 'danger'],
      6 => ['label' => 'Yönetici Reddetti', 'class' => 'danger'],
      7 => ['label' => 'İptal Edildi', 'class' => 'secondary'],
  ];
  $activeStatus = request('status');
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İade Talepleri</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">İade Talepleri</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card mb-4">
        <div class="card-header"><h4 class="mb-0"><i class="fas fa-route mr-1"></i> İade sürecinde ne yapmalısınız?</h4></div>
        <div class="card-body seller-return-steps">
          <div class="step-item">
            <div class="step-num">1</div>
            <div>
              <strong>Bekleyen talebi açın</strong>
              <div class="text-muted small">Müşteri iade istediğinde durum “Beklemede” olur. Detaya girip ürün, adet ve kanıt görsellerini kontrol edin.</div>
            </div>
          </div>
          <div class="step-item">
            <div class="step-num">2</div>
            <div>
              <strong>Onaylayın veya reddedin</strong>
              <div class="text-muted small">Uygunsa “Talebi Onayla”, değilse zorunlu red nedeni yazarak “Talebi Reddet”.</div>
            </div>
          </div>
          <div class="step-item mb-0">
            <div class="step-num">3</div>
            <div>
              <strong>Sonraki adımlar yönetimde</strong>
              <div class="text-muted small">Onayınızdan sonra yönetici iade/ödeme sürecini tamamlar. Reddederseniz yönetici kararı beklenir.</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-undo"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam Talep</h4></div>
              <div class="card-body">{{ $returns->count() }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Bekleyen</h4></div>
              <div class="card-body">{{ $returns->where('status', 0)->count() }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-check"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Onaylanan</h4></div>
              <div class="card-body">{{ $returns->whereIn('status', [1, 2, 3, 4])->count() }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-danger"><i class="fas fa-times"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Reddedilen</h4></div>
              <div class="card-body">{{ $returns->whereIn('status', [5, 6])->count() }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
            <a href="{{ route('seller.return-requests.index') }}" class="btn {{ $activeStatus === null ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">Tümü</a>
            @foreach($statusLabels as $statusValue => $statusMeta)
              <a href="{{ route('seller.return-requests.index', ['status' => $statusValue]) }}" class="btn {{ (string) $activeStatus === (string) $statusValue ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                {{ $statusMeta['label'] }}
              </a>
            @endforeach
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>SN</th>
                  <th>Müşteri</th>
                  <th>Sipariş No</th>
                  <th>Ürün</th>
                  <th>Adet</th>
                  <th>İade Tutarı</th>
                  <th>Durum</th>
                  <th>Tarih</th>
                  <th>İşlem</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($returns as $index => $return)
                  <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                      <strong>{{ $return->user->name }}</strong><br>
                      <span class="text-muted">{{ $return->user->email }}</span>
                    </td>
                    <td>#{{ $return->order->order_id }}</td>
                    <td>
                      <strong>{{ $return->orderProduct->product_name }}</strong><br>
                      <span class="text-muted text-capitalize">{{ str_replace('_', ' ', $return->reason) }}</span>
                    </td>
                    <td>{{ $return->qty }}</td>
                    <td>{{ $setting->currency_icon }}{{ number_format((float) $return->refund_amount, 2) }}</td>
                    <td>
                      <span class="badge badge-{{ $statusLabels[$return->status]['class'] ?? 'secondary' }}">
                        {{ $statusLabels[$return->status]['label'] ?? 'Bilinmiyor' }}
                      </span>
                    </td>
                    <td>{{ optional($return->created_at)->format('d M Y') }}</td>
                    <td>
                      <a href="{{ route('seller.return-requests.show', $return->id) }}" class="btn btn-primary btn-sm">
                        @if((int) $return->status === 0)
                          İncele / Karar Ver
                        @else
                          Detay
                        @endif
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center">Seçilen filtre için iade talebi bulunamadı.</td>
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
