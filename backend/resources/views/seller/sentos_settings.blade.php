@extends('seller.master_layout')
@section('title')
<title>Sentos Entegrasyonu</title>
@endsection

@section('seller-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Sentos Entegrasyonu</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item"><a href="{{ route('seller.integrations.index') }}">Eklentiler</a></div>
                <div class="breadcrumb-item">Sentos</div>
            </div>
        </div>

        <div class="section-body">
            <div class="alert alert-info">
                <strong>Sentos eklentisi:</strong> API bilgilerini kaydedin, bağlantıyı test edin, ardından ürünleri çekin.
                Senkron yalnızca bu satıcı hesabına yazar; diğer satıcılar ve ödeme akışı etkilenmez.
                Kategori eşleşmezse o ürün atlanır (yanlış kategoriye basılmaz).
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-plug mr-2"></i>API Bilgileri</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('seller.sentos.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label for="api_base_url">Sentos firma adresi <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control @error('api_base_url') is-invalid @enderror"
                                        id="api_base_url"
                                        name="api_base_url"
                                        value="{{ old('api_base_url', $settings->api_base_url ?? '') }}"
                                        placeholder="firma-adi veya https://firma-adi.sentos.com.tr"
                                        required
                                    >
                                    <small class="text-muted">Örnek: <code>firma-adi</code> → <code>https://firma-adi.sentos.com.tr/api</code></small>
                                    @error('api_base_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="api_key">API Key / Kullanıcı <span class="text-danger">*</span></label>
                                    <input
                                        type="password"
                                        class="form-control @error('api_key') is-invalid @enderror"
                                        id="api_key"
                                        name="api_key"
                                        value=""
                                        autocomplete="new-password"
                                        placeholder="{{ $settings ? 'Değiştirmek için yeni değer yazın' : 'Sentos API key' }}"
                                        {{ $settings ? '' : 'required' }}
                                    >
                                    @error('api_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="api_secret">API Secret / Şifre <span class="text-danger">*</span></label>
                                    <input
                                        type="password"
                                        class="form-control @error('api_secret') is-invalid @enderror"
                                        id="api_secret"
                                        name="api_secret"
                                        value=""
                                        autocomplete="new-password"
                                        placeholder="{{ $settings ? 'Değiştirmek için yeni değer yazın' : 'Sentos API secret' }}"
                                        {{ $settings ? '' : 'required' }}
                                    >
                                    @error('api_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="custom-switch mt-2">
                                        <input
                                            type="checkbox"
                                            name="is_enabled"
                                            value="1"
                                            class="custom-switch-input"
                                            {{ old('is_enabled', $settings->is_enabled ?? false) ? 'checked' : '' }}
                                        >
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">Sentos entegrasyonunu bu mağaza için aç</span>
                                    </label>
                                    <p class="text-muted mb-0 mt-2">Kapalıyken sonraki senkron fazları bu satıcı için çalışmaz.</p>
                                </div>

                                <button type="submit" class="btn btn-primary">Kaydet</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Durum</h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Entegrasyon:</strong>
                                @if($settings?->is_enabled)
                                    <span class="badge badge-success">Açık</span>
                                @else
                                    <span class="badge badge-secondary">Kapalı</span>
                                @endif
                            </p>
                            <p class="mb-2">
                                <strong>Son test:</strong>
                                @if($settings?->last_tested_at)
                                    {{ $settings->last_tested_at->format('d.m.Y H:i') }}
                                    —
                                    @if($settings->last_test_status === 'success')
                                        <span class="text-success">Başarılı</span>
                                    @else
                                        <span class="text-danger">Başarısız</span>
                                    @endif
                                @else
                                    Henüz test edilmedi
                                @endif
                            </p>
                            @if($settings?->last_test_message)
                                <p class="small text-muted mb-3">{{ $settings->last_test_message }}</p>
                            @endif

                            <hr>
                            <p class="mb-2">
                                <strong>Son ürün senkronu:</strong>
                                @if($settings?->last_sync_at)
                                    {{ $settings->last_sync_at->format('d.m.Y H:i') }}
                                    —
                                    @if($settings->last_sync_status === 'success')
                                        <span class="text-success">Başarılı</span>
                                    @elseif($settings->last_sync_status === 'processing')
                                        <span class="text-warning">Çalışıyor</span>
                                    @else
                                        <span class="text-danger">Başarısız / kısmi</span>
                                    @endif
                                @else
                                    Henüz çalıştırılmadı
                                @endif
                            </p>
                            @if($settings?->last_sync_message)
                                <p class="small text-muted mb-3">{{ $settings->last_sync_message }}</p>
                            @endif

                            @if($settings)
                                <form action="{{ route('seller.sentos.test') }}" method="POST" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fas fa-vial mr-1"></i> Bağlantıyı Test Et
                                    </button>
                                </form>
                                <form action="{{ route('seller.sentos.sync-products') }}" method="POST" class="mb-2" onsubmit="return confirm('Sentos ürünleri bu mağazaya çekilsin / güncellensin mi?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-block" {{ ($settings->last_sync_status === 'processing' || ! $settings->is_enabled) ? 'disabled' : '' }}>
                                        <i class="fas fa-sync mr-1"></i> Ürünleri Çek / Güncelle
                                    </button>
                                </form>
                                <form action="{{ route('seller.sentos.disable') }}" method="POST" onsubmit="return confirm('Sentos bu mağaza için kapatılsın mı? Ürünleriniz silinmez.');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-block">
                                        Entegrasyonu Kapat
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body small text-muted">
                            API bilgileriniz şifreli saklanır. Bilgileri Sentos panelinden (Hesabım) alabilirsiniz.
                            Dokümantasyon: <a href="https://api.sentos.com.tr/docs" target="_blank" rel="noopener">api.sentos.com.tr/docs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
