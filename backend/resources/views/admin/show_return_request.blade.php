@extends('admin.master_layout')
@section('title')
<title>İade Talebi Detayları</title>
@endsection
@section('admin-content')
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
            <h1>İade Talebi Detayları</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item active"><a href="{{ route('admin.return-requests.index') }}">İade Talepleri</a></div>
              <div class="breadcrumb-item">Detaylar</div>
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
                                        <strong>{{ optional($return->user)->name ?: 'Silinmiş Kullanıcı' }}</strong><br>
                                        <span class="text-muted">{{ optional($return->user)->email ?: '-' }}</span><br>
                                        <span class="text-muted">{{ optional($return->user)->phone ?: '-' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <small class="text-muted d-block">Sipariş</small>
                                        <strong>#{{ optional($return->order)->order_id ?: 'N/A' }}</strong><br>
                                        <span class="text-muted">{{ optional($return->created_at)->format('d M Y H:i') }}</span><br>
                                        <span class="text-muted">Adet: {{ $return->qty }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <small class="text-muted d-block">İade Tutarı</small>
                                        <strong>{{ $setting->currency_icon }}{{ number_format((float) ($return->refund_amount ?? 0), 2) }}</strong><br>
                                        <span class="text-muted">{{ $return->refund_method ?: 'Henüz atanmadı' }}</span><br>
                                        <span class="text-muted">Talep #{{ $return->id }}</span>
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
                                                <strong>{{ optional($return->orderProduct)->product_name ?: 'Silinmiş Ürün' }}</strong><br>
                                                <span class="text-muted text-capitalize">{{ str_replace('_', ' ', $return->reason) }}</span>
                                            </td>
                                            <td>{{ $setting->currency_icon }}{{ number_format((float) optional($return->orderProduct)->unit_price, 2) }}</td>
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
                                        <p class="mb-0 text-muted">{{ $requestDetails ?: 'Ek açıklama girilmedi.' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="mb-2">Notlar</h6>
                                        <p class="mb-2"><strong>Satıcı Notu:</strong><br>{{ $return->seller_note ?: 'Henüz satıcı notu yok.' }}</p>
                                        <p class="mb-0"><strong>Yönetici Notu:</strong><br>{{ $return->admin_note ?: 'Henüz yönetici notu yok.' }}</p>
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
                    <div class="card">
                        <div class="card-header">
                            <h4>Yönetici İşlemleri</h4>
                        </div>
                        <div class="card-body">
                            @if ((int) $return->status === 1 || (int) $return->status === 5)
                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST" class="mb-4">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="2">
                                    <div class="form-group">
                                        <label>İade Tutarı</label>
                                        <input type="number" step="0.01" min="0" name="refund_amount" class="form-control" value="{{ old('refund_amount', $return->refund_amount) }}" required>
                                        @if(!empty($suggestedRefund))
                                            <small class="form-text text-muted">
                                                Önerilen tutar: {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['refund_amount'], 2) }}
                                                (ürün {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['line_gross'], 2) }}
                                                − kupon payı {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['coupon_share'], 2) }}
                                                @if(!empty($suggestedRefund['shipping_included']))
                                                    + kargo {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['shipping'], 2) }}
                                                @endif
                                                ). Kupon siparişe orantılı dağıtılır; iade edilen ürüne düşen indirim payı düşülür.
                                            </small>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label>İade Yöntemi</label>
                                        <input type="text" name="refund_method" class="form-control" value="{{ old('refund_method', $return->refund_method ?: 'original_gateway') }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Yönetici Notu</label>
                                        <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block shadow-sm">Talebi Onayla</button>
                                </form>

                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="6">
                                    <div class="form-group">
                                        <label>Red Nedeni</label>
                                        <textarea name="rejected_reason" class="form-control" rows="3" required>{{ old('rejected_reason', $return->rejected_reason) }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Yönetici Notu</label>
                                        <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-block shadow-sm">Talebi Reddet</button>
                                </form>
                            @elseif ((int) $return->status === 2)
                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST" class="mb-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="3">
                                    <div class="form-group">
                                        <label>Yönetici Notu</label>
                                        <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-block shadow-sm">Teslim Alındı Olarak İşaretle</button>
                                </form>

                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="4">
                                    <div class="form-group">
                                        <label>Yönetici Notu</label>
                                        <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block shadow-sm">İadeyi Tamamla</button>
                                </form>
                            @elseif ((int) $return->status === 3)
                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="4">
                                    <div class="form-group">
                                        <label>Yönetici Notu</label>
                                        <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block shadow-sm">İadeyi Tamamla</button>
                                </form>
                            @else
                                <div class="alert alert-light border mb-0">
                                    Bu talep nihai duruma ulaştı. Ek yönetici işlemi yapılamaz.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h4>Satıcı Bilgisi</h4>
                        </div>
                        <div class="card-body">
                            <strong>Satıcı:</strong> <a href="{{ route('admin.seller-show', $return->seller_id) }}">{{ optional($return->seller)->shop_name ?: 'Bilinmiyor' }}</a><br>
                            <strong>Son Satıcı Notu:</strong><br>
                            <p class="mt-2 text-muted">{{ $return->seller_note ?: 'Henüz yanıt yok.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </section>
      </div>
@endsection
