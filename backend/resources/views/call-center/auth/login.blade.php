@include('admin.header')
<div id="app">
    <section class="section">
        <div class="auth-section-wrapper">
            <div class="login-thumb">
                @if(isset($banner) && $banner?->image)
                    <img class="img" src="{{ asset($banner->image) }}" alt="login-thumb"/>
                @endif
            </div>
            <div class="form-area-wrapper">
                <div class="form-content-wrapper">
                    <div class="logo">
                        <img src="{{ asset($setting->logo ?? '') }}" alt="logo"/>
                    </div>
                    <div class="card card-primary card-wrapper-auth">
                        <div class="card-body">
                            <div class="tex-content">
                                <h1>Çağrı Merkezi</h1>
                                <p class="des">Satıcı hızlı kayıt paneline giriş yapın.</p>
                            </div>
                            <form action="{{ route('call-center.login.post') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="email">E-posta <sup>*</sup></label>
                                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
                                </div>
                                <div class="form-group">
                                    <label for="password">Şifre <sup>*</sup></label>
                                    <input id="password" type="password" class="form-control" name="password" required>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="remember" class="custom-control-input" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="remember">Beni Hatırla</label>
                                    </div>
                                </div>
                                <div class="form-group login-sub-btn">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block">Giriş Yap</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="simple-footer">{{ $setting->copyright ?? '' }}</div>
        </div>
    </section>
</div>
@include('admin.footer')
