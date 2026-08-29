@extends('admin.master_layout')
@section('title')
<title>Satıcı Onay Detayı</title>
@endsection
@section('admin-content')
@php
  $typeLabels = [
    'identity_front' => 'Kimlik Ön Yüz',
    'identity_back' => 'Kimlik Arka Yüz',
    'tax_certificate' => 'Vergi Belgesi',
    'address_proof' => 'Adres Belgesi',
    'bank_statement' => 'Banka Hesap Özeti',
    'iban_document' => 'IBAN Belgesi',
  ];
  $statusLabels = [
    'not_submitted' => 'Belge Yüklenmedi',
    'pending' => 'Onay Bekliyor',
    'approved' => 'Onaylı',
    'rejected' => 'Reddedildi',
  ];
  $statusBadge = [
    'not_submitted' => 'badge-secondary',
    'pending' => 'badge-warning',
    'approved' => 'badge-success',
    'rejected' => 'badge-danger',
  ];
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Satıcı Onay Detayı</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Kontrol Paneli</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.kyc.index') }}">Satıcı Onayları</a></div>
        <div class="breadcrumb-item">Detaylar</div>
      </div>
    </div>

    <div class="section-body">
      <a href="{{ route('admin.kyc.index') }}" class="btn btn-primary mb-4"><i class="fas fa-list"></i> Satıcı Onay Listesi</a>
      <a href="{{ route('admin.product-by-seller', $seller->id) }}" class="btn btn-info mb-4 ml-2"><i class="fas fa-box-open"></i> Ürünleri Gör ({{ $seller->products_total ?? 0 }})</a>

      @if($status['kyc_status'] === 'pending' && $status['pending_count'] > 0)
        <form action="{{ route('admin.kyc.approve-vendor', $seller->id) }}" method="POST" class="d-inline mb-4" onsubmit="return confirm('Tüm bekleyen belgeler onaylansın mı?');">
          @csrf
          @method('PUT')
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check-double mr-1"></i> Tüm Belgeleri Onayla ({{ $status['pending_count'] }})
          </button>
        </form>
      @endif

      <div class="row">
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-box"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam Ürün</h4></div>
              <div class="card-body">{{ $seller->products_total ?? 0 }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-check"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Onaylı Ürün</h4></div>
              <div class="card-body">{{ $seller->products_approved ?? 0 }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Bekleyen Ürün</h4></div>
              <div class="card-body">{{ $seller->products_pending ?? 0 }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-id-card"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>KYC Durumu</h4></div>
              <div class="card-body">
                <span class="badge {{ $statusBadge[$status['kyc_status']] ?? 'badge-secondary' }}">{{ $statusLabels[$status['kyc_status']] ?? $status['kyc_status'] }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-file-alt"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Toplam Belge</h4></div>
              <div class="card-body">{{ $status['document_count'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Bekleyen</h4></div>
              <div class="card-body">{{ $status['pending_count'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-check"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Onaylanan</h4></div>
              <div class="card-body">{{ $status['approved_count'] }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-statistic-1">
            <div class="card-icon bg-danger"><i class="fas fa-times"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Reddedilen</h4></div>
              <div class="card-body">{{ $status['rejected_count'] }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header"><h4>Satıcı Bilgileri</h4></div>
            <div class="card-body">
              <table class="table table-bordered table-sm">
                <tr><td><strong>Ad Soyad</strong></td><td>{{ optional($seller->user)->name ?? '-' }}</td></tr>
                <tr><td><strong>Mağaza</strong></td><td>{{ $seller->shop_name }}</td></tr>
                <tr><td><strong>E-posta</strong></td><td>{{ optional($seller->user)->email ?? '-' }}</td></tr>
                <tr><td><strong>Telefon</strong></td><td>{{ optional($seller->user)->phone ?? $seller->phone ?? '-' }}</td></tr>
                <tr>
                  <td><strong>KYC Durumu</strong></td>
                  <td><span class="badge {{ $statusBadge[$status['kyc_status']] ?? 'badge-secondary' }}">{{ $statusLabels[$status['kyc_status']] ?? $status['kyc_status'] }}</span></td>
                </tr>
                @php
                  $onboarding = app(\App\Services\SellerIyzicoOnboardingService::class);
                  $sellerTypeNorm = $onboarding->normalizeSellerType($seller->seller_type);
                  $sellerTypeAdminLabels = [
                    'sole_proprietorship' => 'Şahıs Şirketi',
                    'limited_company' => 'Ltd / A.Ş.',
                  ];
                @endphp
                <tr><td><strong>Satıcı Tipi</strong></td><td>{{ $sellerTypeAdminLabels[$sellerTypeNorm] ?? ($seller->seller_type ?: '-') }}</td></tr>
                <tr><td><strong>TC Kimlik</strong></td><td>{{ $seller->tc_identity ?: '-' }}</td></tr>
                <tr><td><strong>IBAN</strong></td><td><code>{{ $seller->iban ?: '-' }}</code></td></tr>
                <tr><td><strong>Vergi No</strong></td><td>{{ $seller->tax_number ?: '-' }}</td></tr>
                <tr><td><strong>Vergi Dairesi</strong></td><td>{{ $seller->tax_office ?: '-' }}</td></tr>
                <tr><td><strong>Ticari Unvan</strong></td><td>{{ $seller->legal_company_title ?: '-' }}</td></tr>
                <tr><td><strong>Adres</strong></td><td>{{ $seller->address ?: (optional($seller->user)->address ?: '-') }}</td></tr>
                <tr><td><strong>Gönderim Tarihi</strong></td><td>{{ optional($status['submitted_at'])->format('d.m.Y H:i') ?: '-' }}</td></tr>
                <tr><td><strong>Onay Tarihi</strong></td><td>{{ optional($status['approved_at'])->format('d.m.Y H:i') ?: '-' }}</td></tr>
                <tr><td><strong>Iyzico Anahtarı</strong></td><td><code class="small">{{ $seller->iyzico_sub_merchant_key ?: '-' }}</code></td></tr>
                <tr><td><strong>Iyzico Tip</strong></td><td><code class="small">{{ $seller->iyzico_sub_merchant_type ?: $onboarding->iyzicoTypeFor($sellerTypeNorm) }}</code></td></tr>
              </table>

              @if($status['kyc_status'] === 'approved' && empty($seller->iyzico_sub_merchant_key))
                @php $missingFields = $onboarding->missingFields($seller); @endphp
                @if(!empty($missingFields))
                  <div class="alert alert-warning mt-3">
                    <strong>Alt işyeri oluşturulamıyor.</strong><br>
                    Satıcının şu bilgileri eksik: <b>{{ implode(', ', $missingFields) }}</b><br>
                    <small>Satıcı bu bilgileri KYC formundan doldurmalı, ardından butona tıklayın.</small>
                  </div>
                @endif
                <form action="{{ route('admin.kyc.create-sub-merchant', $seller->id) }}" method="POST" class="mt-3">
                  @csrf
                  <button class="btn btn-dark btn-block" type="submit" {{ !empty($missingFields) ? 'disabled' : '' }}>
                    <i class="fas fa-store mr-1"></i> Iyzico Alt Üye İşyeri Oluştur
                  </button>
                </form>
              @endif
            </div>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card">
            <div class="card-header"><h4>Belgeler</h4></div>
            <div class="card-body">
              @forelse($seller->kycDocuments as $document)
                <div class="border rounded p-3 mb-4">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <h6 class="mb-1">{{ $typeLabels[$document->document_type] ?? $document->document_type }}</h6>
                      <div class="text-muted small">{{ $document->original_name }} | {{ number_format(($document->file_size ?? 0) / 1024, 1) }} KB</div>
                    </div>
                    <span class="badge {{ $statusBadge[$document->status] ?? 'badge-secondary' }}">
                      {{ $statusLabels[$document->status] ?? $document->status }}
                    </span>
                  </div>

                  <div class="mb-3">
                    <a href="{{ route('admin.kyc.download', $document->id) }}" class="btn btn-info btn-sm">
                      <i class="fas fa-download mr-1"></i> İndir
                    </a>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <form action="{{ route('admin.kyc.approve', $document->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                          <label>Yönetici Notu</label>
                          <textarea name="admin_note" class="form-control" rows="3" placeholder="Onay notu (opsiyonel)">{{ $document->admin_note }}</textarea>
                        </div>
                        <button class="btn btn-success btn-block" type="submit">
                          <i class="fas fa-check mr-1"></i> Onayla
                        </button>
                      </form>
                    </div>
                    <div class="col-md-6">
                      <form action="{{ route('admin.kyc.reject', $document->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                          <label>Red Nedeni <span class="text-danger">*</span></label>
                          <textarea name="admin_note" class="form-control" rows="3" required placeholder="Reddetme nedenini yazın">{{ $document->status === 'rejected' ? $document->admin_note : '' }}</textarea>
                        </div>
                        <button class="btn btn-danger btn-block" type="submit">
                          <i class="fas fa-times mr-1"></i> Reddet
                        </button>
                      </form>
                    </div>
                  </div>

                  <div class="mt-3 text-muted small">
                    İncelenme: {{ optional($document->reviewed_at)->format('d.m.Y H:i') ?: 'Henüz incelenmedi' }}
                    @if($document->reviewer)
                      | İnceleyen: {{ $document->reviewer->name }}
                    @endif
                  </div>
                </div>
              @empty
                <div class="alert alert-info mb-0">Bu satıcı henüz KYC belgesi yüklememiş.</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
