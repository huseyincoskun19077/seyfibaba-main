@extends('seller.master_layout')
@section('title')
<title>İade Talebi Detayı</title>
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
  $statusMeta = $statusLabels[$return->status] ?? ['label' => 'Bilinmiyor', 'class' => 'secondary'];
  $requestDetails = $return->description ?: $return->details;
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İade Talebi Detayı</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('seller.return-requests.index') }}">İade Talepleri</a></div>
        <div class="breadcrumb-item">Detay</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header justify-content-between">
              <h4 class="mb-0">Talep Özeti</h4>
              <span class="badge badge-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4 mb-3">
                  <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Müşteri</small>
                    <strong>{{ $return->user->name }}</strong><br>
                    <span class="text-muted">{{ $return->user->email }}</span><br>
                    <span class="text-muted">{{ $return->user->phone ?: '-' }}</span>
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Sipariş</small>
                    <strong>#{{ $return->order->order_id }}</strong><br>
                    <span class="text-muted">{{ optional($return->created_at)->format('d M Y H:i') }}</span><br>
                    <span class="text-muted">Adet: {{ $return->qty }}</span>
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">İade</small>
                    <strong>{{ $setting->currency_icon }}{{ number_format((float) ($return->refund_amount ?? 0), 2) }}</strong><br>
                    <span class="text-muted">{{ $return->refund_method ?: 'Yönetici kararı bekleniyor' }}</span><br>
                    <span class="text-muted">Talep No #{{ $return->id }}</span>
                  </div>
                </div>
              </div>

              <div class="table-responsive mt-2">
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th>Ürün</th>
                      <th>Birim Fiyat</th>
                      <th>Talep Edilen Adet</th>
                      <th>Talep Edilen İade</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <strong>{{ $return->orderProduct->product_name }}</strong><br>
                        <span class="text-muted text-capitalize">{{ str_replace('_', ' ', $return->reason) }}</span>
                      </td>
                      <td>{{ $setting->currency_icon }}{{ number_format((float) $return->orderProduct->unit_price, 2) }}</td>
                      <td>{{ $return->qty }}</td>
                      <td>{{ $setting->currency_icon }}{{ number_format((float) ($return->refund_amount ?? 0), 2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="row mt-4">
                <div class="col-md-6 mb-3">
                  <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">Müşteri Mesajı</h6>
                    <p class="mb-0 text-muted">{{ $requestDetails ?: 'Ek bir açıklama paylaşılmadı.' }}</p>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">Karar Notları</h6>
                    <p class="mb-2"><strong>Satıcı Notu:</strong><br>{{ $return->seller_note ?: 'Henüz satıcı notu eklenmedi.' }}</p>
                    <p class="mb-0"><strong>Yönetici Notu:</strong><br>{{ $return->admin_note ?: 'Henüz yönetici notu eklenmedi.' }}</p>
                  </div>
                </div>
              </div>

              @if($return->rejected_reason)
                <div class="alert alert-danger mb-0">
                  <strong>Red Nedeni:</strong><br>
                  {{ $return->rejected_reason }}
                </div>
              @endif

              @if($return->images->count() > 0)
                <div class="mt-4">
                  <h5>Kanıt Görselleri</h5>
                  <div class="row mt-3">
                    @foreach($return->images as $img)
                      <div class="col-md-3 col-sm-4 mb-3">
                        <a href="{{ asset($img->image) }}" target="_blank" rel="noopener noreferrer">
                          <img src="{{ asset($img->image) }}" class="img-fluid rounded border" alt="Kanıt görseli">
                        </a>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card mb-3">
            <div class="card-header"><h4 class="mb-0">Şu an sizin adımınız</h4></div>
            <div class="card-body seller-return-steps">
              @if ((int) $return->status === 0)
                <div class="alert alert-warning">
                  Bu talep <strong>sizin kararınızı</strong> bekliyor. Kanıtları inceleyin, sonra onaylayın veya red nedeni yazarak reddedin.
                </div>
                <div class="step-item">
                  <div class="step-num">1</div>
                  <div class="small">Müşteri mesajı ve kanıt görsellerini kontrol edin.</div>
                </div>
                <div class="step-item">
                  <div class="step-num">2</div>
                  <div class="small">Uygunsa onaylayın; değilse net red nedeni yazın.</div>
                </div>
                <div class="step-item mb-0">
                  <div class="step-num">3</div>
                  <div class="small">Onay sonrası süreç yöneticiye geçer; iade tutarı yönetici tarafından sonuçlandırılır.</div>
                </div>
              @elseif ((int) $return->status === 1)
                <div class="alert alert-info mb-0">
                  Talebi onayladınız. Şimdi <strong>yönetici incelemesi / ödeme süreci</strong> bekleniyor. Satıcı panelinden ek işlem gerekmez.
                </div>
              @elseif ((int) $return->status === 5)
                <div class="alert alert-danger mb-0">
                  Talebi reddettiniz. Gerekirse yönetici ayrıca değerlendirir. Yeni bir aksiyon gerekmez.
                </div>
              @elseif (in_array((int) $return->status, [2, 3], true))
                <div class="alert alert-info mb-0">
                  Yönetici süreci devam ediyor (onay / teslim). Ürün geri geldiyse depo sürecini yönetici takip eder.
                </div>
              @elseif ((int) $return->status === 4)
                <div class="alert alert-success mb-0">
                  İade tamamlandı. Ek satıcı aksiyonu yok.
                </div>
              @else
                <div class="alert alert-light border mb-0">
                  Bu talep kapanmış veya iptal edilmiş durumda. Satıcı panelinden işlem yapılamaz.
                </div>
              @endif
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h4>Satıcı İşlemleri</h4>
            </div>
            <div class="card-body">
              @if ((int) $return->status === 0)
                <form action="{{ route('seller.return-requests.update-status', $return->id) }}" method="POST" class="mb-4">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="status" value="1">
                  <div class="form-group">
                    <label>Satıcı Notu (opsiyonel)</label>
                    <textarea name="seller_note" class="form-control" rows="4" placeholder="Örn: Ürünü geri almayı kabul ediyoruz">{{ old('seller_note', $return->seller_note) }}</textarea>
                  </div>
                  <button type="submit" class="btn btn-primary btn-lg btn-block">Talebi Onayla</button>
                </form>

                <form action="{{ route('seller.return-requests.update-status', $return->id) }}" method="POST">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="status" value="5">
                  <div class="form-group">
                    <label>Red Nedeni <span class="text-danger">*</span></label>
                    <textarea name="rejected_reason" class="form-control" rows="4" required placeholder="Örn: Ürün kullanılmış / kanıt yetersiz">{{ old('rejected_reason', $return->rejected_reason) }}</textarea>
                  </div>
                  <button type="submit" class="btn btn-danger btn-lg btn-block">Talebi Reddet</button>
                </form>
              @else
                <div class="alert alert-light border mb-0">
                  Bu talep artık satıcı panelinden işleme alınamaz.
                </div>
              @endif

              @if ($return->admin_note)
                <div class="alert alert-info mt-3 mb-0">
                  <strong>Yönetici Notu:</strong><br>
                  {{ $return->admin_note }}
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
