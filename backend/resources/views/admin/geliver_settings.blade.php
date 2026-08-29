@extends('admin.master_layout')
@section('title')
<title>Geliver Kargo Ayarları - {{ __('admin.Admin Panel') }}</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Geliver Kargo Ayarları</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('admin.Dashboard') }}</a></div>
                <div class="breadcrumb-item">Yapılandırma</div>
                <div class="breadcrumb-item">Geliver</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-truck mr-2"></i>Entegrasyon Bilgileri</h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">Bu ekran Geliver kargo entegrasyonu için gerekli temel ayarları yönetir.</p>
                            <div class="alert alert-info mb-0">
                                Webhook URL:
                                <code>{{ $webhookUrl }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.update-geliver-settings') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="geliver_api_token">Geliver API Token</label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="geliver_api_token"
                                        name="geliver_api_token"
                                        value="{{ old('geliver_api_token', $setting->geliver_api_token) }}"
                                        placeholder="Geliver API token"
                                        autocomplete="new-password"
                                    >
                                    <small class="text-muted">Token almak için <a href="https://app.geliver.io/apitokens" target="_blank" rel="noopener noreferrer">app.geliver.io/apitokens</a> adresini kullanın.</small>
                                </div>

                                <div class="form-group">
                                    <label class="d-block" for="geliver_test_mode">Test Modu</label>
                                    <label class="custom-switch mt-2">
                                        <input type="checkbox" name="geliver_test_mode" id="geliver_test_mode" value="on" class="custom-switch-input" {{ old('geliver_test_mode', $setting->geliver_test_mode) === 'on' ? 'checked' : '' }}>
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">Gerçek kargo oluşturmadan test modunda çalış</span>
                                    </label>
                                    <p class="text-muted mb-0 mt-2">
                                        {{ ($setting->geliver_test_mode ?? '') === 'on' ? 'Test modunda, gerçek kargo oluşturulmaz.' : 'Canlı modda, gerçek Geliver kargoları oluşturulur.' }}
                                    </p>
                                </div>

                                <div class="form-group">
                                    <label for="geliver_sender_address_id">Varsayılan Gönderici Adres ID</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="geliver_sender_address_id"
                                        name="geliver_sender_address_id"
                                        value="{{ old('geliver_sender_address_id', $setting->geliver_sender_address_id) }}"
                                        placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                    >
                                    <small class="text-muted">Geliver tarafındaki gönderici adres kaydının UUID değeri.</small>
                                </div>

                                <div class="form-group">
                                    <label for="geliver_webhook_header_name">Webhook Header Adı</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="geliver_webhook_header_name"
                                        name="geliver_webhook_header_name"
                                        value="{{ old('geliver_webhook_header_name', $setting->geliver_webhook_header_name) }}"
                                        placeholder="X-Geliver-Secret"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="geliver_webhook_header_secret">Webhook Header Secret</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="geliver_webhook_header_secret"
                                        name="geliver_webhook_header_secret"
                                        value="{{ old('geliver_webhook_header_secret', $setting->geliver_webhook_header_secret) }}"
                                        placeholder="Webhook doğrulama secret"
                                    >
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Ayarları Kaydet
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4>Notlar</h4>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0 pl-3">
                                    <li>Test modu açıkken canlı gönderi oluşturulmaz.</li>
                                    <li>Gönderici adres ID alanı Geliver UUID formatında olmalıdır.</li>
                                    <li>Kaydetme sonrası temel env değerleri de güncellenir.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
