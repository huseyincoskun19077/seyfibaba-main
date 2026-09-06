@extends('admin.master_layout')
@section('title')
<title>İade Talebi Detayları</title>
@endsection
@section('admin-content')
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
            <h1>İade Talebi Detayları</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item active"><a href="{{ route('admin.return-requests.index') }}">İade Talepleri</a></div>
              <div class="breadcrumb-item">Detaylar</div>
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
                                        <strong>{{ optional($return->user)->name ?: 'Silinmiş Kullanıcı' }}</strong><br>
                                        <span class="text-muted">{{ optional($return->user)->email ?: '-' }}</span><br>
                                        <span class="text-muted">{{ optional($return->user)->phone ?: '-' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <small class="text-muted d-block">Sipariş</small>
                                        <strong>#{{ optional($return->order)->order_id ?: 'N/A' }}</strong><br>
                                        <span class="text-muted">{{ optional($return->created_at)->format('d.m.Y H:i') }}</span><br>
                                        <span class="text-muted">Adet: {{ $return->qty }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <small class="text-muted d-block">İade Tutarı</small>
                                        <strong>{{ $setting->currency_icon }}{{ number_format((float) ($return->refund_amount ?? 0), 2) }}</strong><br>
                                        <span class="text-muted">İade yöntemi: {{ $refundMethodLabel }}</span><br>
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
                                                <span class="text-muted">İade nedeni: {{ $reasonLabel }}</span>
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
                                        <p class="mb-0 text-muted">{{ $requestDetails ?: 'Müşteri ek açıklama yazmadı.' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="mb-2">Notlar</h6>
                                        <p class="mb-2"><strong>Satıcı notu:</strong><br>{{ $return->seller_note ?: 'Satıcı henüz not yazmadı.' }}</p>
                                        <p class="mb-0"><strong>Yönetici notu:</strong><br>{{ $return->admin_note ?: 'Yönetici henüz not yazmadı.' }}</p>
                                    </div>
                                </div>
                            </div>

                            @if($return->rejected_reason && in_array($status, [5, 6], true))
                                <div class="alert alert-danger mb-0">
                                    <strong>Red gerekçesi (müşteriye görünür):</strong><br>
                                    {{ $return->rejected_reason }}
                                </div>
                            @endif

                            @if($return->return_address || $return->buyer_return_tracking_number)
                                <div class="border rounded p-3 mt-4">
                                    <h6 class="mb-3">İade kargo bilgileri</h6>
                                    @if($return->return_address)
                                        <p class="mb-2"><strong>İade adresi:</strong><br><span class="text-muted" style="white-space:pre-line;">{{ $return->return_address }}</span></p>
                                    @endif
                                    <p class="mb-2"><strong>Kargo ücreti:</strong> {{ \App\Models\ReturnRequest::shippingPayerLabel($return->return_shipping_payer) }}</p>
                                    @if($return->return_carrier_name)
                                        <p class="mb-2"><strong>Önerilen kargo:</strong> {{ $return->return_carrier_name }}</p>
                                    @endif
                                    @if($return->return_cargo_code)
                                        <p class="mb-2"><strong>İade kodu:</strong> <code>{{ $return->return_cargo_code }}</code></p>
                                    @endif
                                    @if($return->return_shipping_instructions)
                                        <p class="mb-2"><strong>Talimat:</strong><br><span class="text-muted" style="white-space:pre-line;">{{ $return->return_shipping_instructions }}</span></p>
                                    @endif
                                    @if($return->buyer_return_tracking_number)
                                        <div class="alert alert-info mb-0 mt-2">
                                            <strong>Alıcı kargo bildirimi</strong><br>
                                            Firma: {{ $return->buyer_return_carrier ?: '-' }}<br>
                                            Takip No: {{ $return->buyer_return_tracking_number }}
                                            @if($return->buyer_return_tracking_url)
                                                — <a href="{{ $return->buyer_return_tracking_url }}" target="_blank" rel="noopener">Takip et</a>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-muted mb-0 mt-2"><em>Alıcı henüz takip numarası girmedi.</em></p>
                                    @endif
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
                            @if ($status === 1 || $status === 5)
                                <div class="alert alert-light border">
                                    @if($status === 1)
                                        Satıcı talebi onayladı. Siz nihai onayı / red kararını verin. Yazacağınız yönetici notu müşteriye gösterilir.
                                    @else
                                        Satıcı talebi reddetti. Gerekçeyi inceleyip yönetici olarak onaylayabilir veya reddi kesinleştirebilirsiniz.
                                    @endif
                                </div>
                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST" class="mb-4">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="2">
                                    <div class="form-group">
                                        <label>İade Tutarı</label>
                                        <input type="number" step="0.01" min="0" name="refund_amount" class="form-control" value="{{ old('refund_amount', $return->refund_amount) }}" required>
                                        @if(!empty($suggestedRefund))
                                            <small class="form-text text-muted d-block">
                                                Önerilen tutar: {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['refund_amount'], 2) }}
                                                (ürün {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['line_gross'], 2) }}
                                                − kupon payı {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['coupon_share'], 2) }}
                                                @if(!empty($suggestedRefund['bank_discount_share']) && (float) $suggestedRefund['bank_discount_share'] > 0)
                                                    − havale indirimi {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['bank_discount_share'], 2) }}
                                                @endif
                                                @if(!empty($suggestedRefund['shipping_included']))
                                                    + kargo {{ $setting->currency_icon }}{{ number_format((float) $suggestedRefund['shipping'], 2) }}
                                                @endif
                                                ).
                                            </small>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label>İade Yöntemi</label>
                                        @php
                                            $defaultRefundMethod = old(
                                                'refund_method',
                                                $return->refund_method
                                                    ?: ($suggestedRefund['refund_method_hint'] ?? 'original_gateway')
                                            );
                                        @endphp
                                        <select name="refund_method" class="form-control" required>
                                            <option value="original_gateway" {{ $defaultRefundMethod === 'original_gateway' ? 'selected' : '' }}>Ödeme yöntemine iade (kart)</option>
                                            <option value="bank_transfer" {{ $defaultRefundMethod === 'bank_transfer' ? 'selected' : '' }}>Havale / EFT</option>
                                            <option value="manual" {{ $defaultRefundMethod === 'manual' ? 'selected' : '' }}>Manuel iade</option>
                                        </select>
                                        <div class="alert alert-info mt-2 mb-0 py-2 small">
                                            <strong>Havale ile iade nasıl işler?</strong><br>
                                            Sipariş havale/EFT ile ödendiyse alışverişte uygulanan indirim (genelde %3) iade tutarından düşülür; alıcıya yalnızca ödediği tutar kadar para iadesi yapılır.
                                            İade yöntemi “Havale / EFT” seçildiğinde tutar, müşterinin kayıtlı banka hesabına manuel EFT ile gönderilir (kart otomatik iadesi yoktur).
                                        </div>
                                    </div>
                                    @php
                                        $adminReturnAddress = old(
                                            'return_address',
                                            $return->return_address ?: \App\Models\ReturnRequest::buildSellerReturnAddress($return->seller)
                                        );
        $adminPayer = old(
                                            'return_shipping_payer',
                                            in_array($return->return_shipping_payer, ['seller', 'buyer'], true)
                                                ? $return->return_shipping_payer
                                                : \App\Models\ReturnRequest::defaultShippingPayerForReason($return->reason)
                                        );
                                    @endphp
                                    <div class="form-group">
                                        <label>İade adresi <span class="text-danger">*</span></label>
                                        <textarea name="return_address" class="form-control" rows="3" required>{{ $adminReturnAddress }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>İade kargo ücretini kim karşılar? <span class="text-danger">*</span></label>
                                        <select name="return_shipping_payer" class="form-control" required>
                                            <option value="seller" {{ $adminPayer === 'seller' ? 'selected' : '' }}>Satıcı karşılar</option>
                                            <option value="buyer" {{ $adminPayer === 'buyer' ? 'selected' : '' }}>Alıcı karşılar</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Kargo firması (opsiyonel)</label>
                                        <input type="text" name="return_carrier_name" class="form-control" value="{{ old('return_carrier_name', $return->return_carrier_name) }}">
                                    </div>
                                    <div class="form-group">
                                        <label>İade / anlaşmalı kod (opsiyonel)</label>
                                        <input type="text" name="return_cargo_code" class="form-control" value="{{ old('return_cargo_code', $return->return_cargo_code) }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Kargo talimatı (opsiyonel)</label>
                                        <textarea name="return_shipping_instructions" class="form-control" rows="2">{{ old('return_shipping_instructions', $return->return_shipping_instructions) }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Yönetici Notu <span class="text-danger">*</span></label>
                                        <textarea name="admin_note" class="form-control" rows="3" required placeholder="Müşterinin göreceği kısa açıklama">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block shadow-sm">Talebi Onayla</button>
                                </form>

                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="6">
                                    <div class="form-group">
                                        <label>Red Gerekçesi <span class="text-danger">*</span></label>
                                        <textarea name="rejected_reason" class="form-control" rows="3" required placeholder="Müşteriye gösterilecek red gerekçesi">{{ old('rejected_reason', $return->rejected_reason) }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Yönetici Notu <span class="text-danger">*</span></label>
                                        <textarea name="admin_note" class="form-control" rows="3" required placeholder="Kararınızı kısaca yazın">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-block shadow-sm">Talebi Reddet</button>
                                </form>
                            @elseif ($status === 2)
                                @if(!$return->buyer_return_tracking_number)
                                    <div class="alert alert-warning">
                                        Alıcı henüz takip numarası girmedi. Mümkünse önce kargoyu bekleyin; yine de acil durumda iadeyi tamamlayabilirsiniz.
                                    </div>
                                @endif
                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST" class="mb-3">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="3">
                                    <div class="form-group">
                                        <label>Yönetici Notu</label>
                                        <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-block shadow-sm">Ürün Teslim Alındı</button>
                                </form>

                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="4">
                                    <div class="form-group">
                                        <label>Yönetici Notu</label>
                                        <textarea name="admin_note" class="form-control" rows="3" placeholder="Örn: Ödeme yöntemine iade başlatıldı">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <p class="text-muted small">Önerilen sıra: önce “Ürün Teslim Alındı”, sonra para iadesi.</p>
                                    <button type="submit" class="btn btn-outline-success btn-block shadow-sm">İadeyi Tamamla (Para iadesi) — atlayarak</button>
                                </form>
                            @elseif ($status === 3)
                                <form action="{{ route('admin.return-requests.update-status', $return->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="4">
                                    <div class="form-group">
                                        <label>Yönetici Notu</label>
                                        <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $return->admin_note) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block shadow-sm">İadeyi Tamamla (Para iadesi)</button>
                                </form>
                            @else
                                <div class="alert alert-light border mb-0">
                                    Bu talep nihai durumda: <strong>{{ $statusLabel }}</strong>. Ek yönetici işlemi yapılamaz.
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
                            <strong>Son satıcı notu:</strong><br>
                            <p class="mt-2 text-muted">{{ $return->seller_note ?: 'Henüz yanıt yok.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </section>
      </div>
@endsection
