@include('admin.header')
<div id="app">
    <section class="section">
        <div class="auth-section-wrapper">
            <div class="login-thumb">
                <img class="img" src="{{ asset('backend/img/login-thumb.png') }}" alt="login-thumb"/>
            </div>
            <div class="form-area-wrapper">
                <div class="form-content-wrapper">
                    <div class="logo">
                        <img src="{{ asset($setting->logo ?? '') }}" alt="logo"/>
                    </div>
                    <div class="card card-primary card-wrapper-auth">
                        <div class="card-body">
                            <div class="tex-content text-center mb-4">
                                <h1>Seyfibaba Giriş</h1>
                                <p class="des mb-0">Devam etmek için rolünüze uygun giriş sayfasını seçin.</p>
                            </div>

                            <a href="{{ \App\Support\SellerLoginUrl::public() }}" class="btn btn-primary btn-lg btn-block mb-3">
                                Satıcı Girişi
                            </a>
                            <p class="text-muted text-center small mb-4">E-posta veya telefon numarası ile giriş yapın.</p>

                            <a href="{{ route('call-center.login') }}" class="btn btn-outline-primary btn-lg btn-block mb-3">
                                Çağrı Merkezi Girişi
                            </a>

                            <a href="{{ route('admin.login') }}" class="btn btn-light btn-lg btn-block">
                                Yönetici Girişi
                            </a>
                            <p class="text-muted text-center small mt-2 mb-0">Site yönetimi ve süper admin paneli.</p>
                        </div>
                    </div>
                    <div class="simple-footer">
                        {{ $setting->copyright ?? '' }}
                    </div>
                </div>
            </div>
            <div class="simple-footer">
                {{ $setting->copyright ?? '' }}
            </div>
        </div>
    </section>
</div>
@include('admin.footer')
