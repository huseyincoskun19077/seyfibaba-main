@extends('seller.master_layout')
@section('title')
<title>İade Talebi Detayı</title>
@endsection
@section('seller-content')
@php
  $status = (int) $return->status;
  $statusLabel = $return->statusLabel();
  $statusClass = \App\Models\ReturnRequest::statusBadgeClass($status);
  $requestDetails = $return->description ?: $return->details;
  $reasonLabel = \App\Models\ReturnRequest::reasonLabel($return->reason);
  $refundMethodLabel = \App\Models\ReturnRequest::refundMethodLabel($return->refund_method);
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
      @if(session('messege'))
        <div class="alert alert-{{ session('alert-type') === 'error' ? 'danger' : (session('alert-type') === 'warning' ? 'warning' : 'success') }}">
          {{ session('messege') }}
        </div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0 pl-3">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header justify-content-between">
              <h4 class="mb-0">Talep Özeti</h4>
              <span class="badge badge-{{ $statusClass }}">{{ $statusLabel }}</span>
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
                    <span class="text-muted">{{ optional($return->created_at)->format('d.m.Y H:i') }}</span><br>
                    <span class="text-muted">Adet: {{ $return->qty }}</span>
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Talep edilen iade tutarı</small>
                    <strong>{{ $setting->currency_icon }}{{ number_format((float) ($return->refund_amount ?? 0), 2) }}</strong><br>
                    <span class="text-muted">İade yöntemi: {{ $refundMethodLabel }}</span><br>
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
                        <span class="text-muted">İade nedeni: {{ $reasonLabel }}</span>
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
                    <p class="mb-0 text-muted">{{ $requestDetails ?: 'Müşteri ek açıklama yazmadı.' }}</p>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">Karar Notları</h6>
                    <p class="mb-2">
                      <strong>Satıcı notu:</strong><br>
                      {{ $return->seller_note ?: 'Henüz satıcı notu yok.' }}
                    </p>
                    <p class="mb-0">
                      <strong>Yönetici notu:</strong><br>
                      @if($return->admin_note)
                        {{ $return->admin_note }}
                      @else
                        Yönetici henüz not yazmadı. Nihai karar ve ödeme iadesi yönetici tarafından yapılır.
                      @endif
                    </p>
                    @if(in_array($status, [5, 6], true) && $return->rejected_reason)
                      <p class="mb-0 mt-2 text-danger">
                        <strong>Red gerekçesi (müşteriye görünür):</strong><br>
                        {{ $return->rejected_reason }}
                      </p>
                    @endif
                  </div>
                </div>
              </div>

              @if(in_array($status, [5, 6], true) && ($return->rejected_reason || $return->seller_note || $return->admin_note))
                <div class="alert alert-danger mb-0">
                  <strong>Alıcıya görünen red bilgisi</strong><br>
                  {{ $return->admin_note ?: ($return->rejected_reason ?: $return->seller_note) }}
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
            <div class="card-header"><h4 class="mb-0">Şu an süreçte neredesiniz?</h4></div>
            <div class="card-body seller-return-steps">
              @if ($status === 0)
                <div class="alert alert-warning">
                  Bu talep <strong>sizin kararınızı</strong> bekliyor. Kanıtları inceleyin; sonra onaylayın veya açık bir gerekçe yazarak reddedin.
                </div>
                <div class="step-item">
                  <div class="step-num">1</div>
                  <div class="small">Müşteri mesajını ve kanıt fotoğraflarını kontrol edin.</div>
                </div>
                <div class="step-item">
                  <div class="step-num">2</div>
                  <div class="small">Uygunsa onaylayın. Değilse müşterinin anlayacağı net bir red nedeni yazın.</div>
                </div>
                <div class="step-item mb-0">
                  <div class="step-num">3</div>
                  <div class="small">Onaylarsanız sonraki adım yöneticiye geçer. Reddederseniz müşteri red gerekçesini görür.</div>
                </div>
              @elseif ($status === 1)
                <div class="alert alert-info mb-0">
                  <strong>Talebi onayladınız.</strong><br>
                  Şimdi yönetici inceliyor. Para iadesi henüz yapılmadı; yönetici onaylayıp tamamladığında süreç biter.
                </div>
              @elseif ($status === 5)
                <div class="alert alert-danger mb-0">
                  <strong>Talebi reddettiniz.</strong><br>
                  Müşteri panelinde “İade talebi reddedildi” ve yazdığınız gerekçe görünür. Yönetici gerekirse ayrıca bakabilir; sizin ek işleminiz yok.
                </div>
              @elseif (in_array($status, [2, 3], true))
                <div class="alert alert-info mb-0">
                  Yönetici süreci devam ediyor. Ürün geri alındıysa veya ödeme iadesi hazırlanıyorsa bunu yönetici tamamlar.
                </div>
              @elseif ($status === 4)
                <div class="alert alert-success mb-0">
                  <strong>İade tamamlandı.</strong><br>
                  Müşteriye para iadesi işlenmiş kabul edilir. Satıcı panelinden ek işlem gerekmez.
                </div>
              @elseif ($status === 6)
                <div class="alert alert-danger mb-0">
                  <strong>Yönetici talebi reddetti.</strong><br>
                  Müşteri yönetici notunu / red gerekçesini görür. Satıcı panelinden ek işlem yok.
                </div>
              @else
                <div class="alert alert-light border mb-0">
                  Bu talep kapanmış veya müşteri tarafından iptal edilmiş. Satıcı panelinden işlem yapılamaz.
                </div>
              @endif
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h4>Satıcı İşlemleri</h4>
            </div>
            <div class="card-body">
              @if ($status === 0)
                <form action="{{ route('seller.return-requests.update-status', $return->id) }}" method="POST" class="mb-4">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="status" value="1">
                  <div class="form-group">
                    <label>Onay notu (opsiyonel)</label>
                    <textarea name="seller_note" class="form-control" rows="4" placeholder="Örn: Ürünü geri almayı kabul ediyoruz. Kutusu eksiksiz gelsin.">{{ old('seller_note', $return->seller_note) }}</textarea>
                  </div>
                  <button type="submit" class="btn btn-primary btn-lg btn-block">Talebi Onayla</button>
                </form>

                <hr>
                <p class="text-muted small">Reddederseniz yazdığınız gerekçe müşteriye gösterilir.</p>
                <form action="{{ route('seller.return-requests.update-status', $return->id) }}" method="POST">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="status" value="5">
                  <div class="form-group">
                    <label>Red gerekçesi <span class="text-danger">*</span></label>
                    <textarea name="rejected_reason" class="form-control" rows="4" required placeholder="Örn: Ürün kullanılmış görünüyor / kanıt fotoğrafları yetersiz">{{ old('rejected_reason') }}</textarea>
                  </div>
                  <button type="submit" class="btn btn-danger btn-lg btn-block">Talebi Reddet</button>
                </form>
              @else
                <div class="alert alert-light border mb-0">
                  Bu talep artık satıcı panelinden işleme alınamaz. Güncel durum: <strong>{{ $statusLabel }}</strong>
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
