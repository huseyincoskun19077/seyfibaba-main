@extends('admin.master_layout')
@section('title')
<title>SMS Yapılandırması - Netgsm</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>SMS Yapılandırması</h1>
          </div>
          <div class="section-body">
            <div class="row mt-4">
                <div class="col-md-8">
                  <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info" role="alert">
                          <p>Üye kayıt ve şifre sıfırlama işlemlerinde SMS ile OTP doğrulama yapılabilmesi için Netgsm hesap bilgilerinizi girin. Telefon numarası zorunluluğu <b><a target="_blank" href="{{ route('admin.general-setting') }}">Genel Ayarlar</a></b> sayfasından aktif edilmelidir.</p>
                        </div>

                        <h5 class="card_title">Netgsm Yapılandırması</h5>
                        <hr>

                        <form action="{{ route('admin.update-netgsm-configuration') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="netgsm_usercode">Kullanıcı Kodu</label>
                                        <input type="text" name="netgsm_usercode" value="{{ $setting->netgsm_usercode ?? '' }}" class="form-control" placeholder="Netgsm kullanıcı kodunuz">
                                        <small class="text-muted">Netgsm panelinden aldığınız kullanıcı kodu</small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="netgsm_password">Şifre</label>
                                        <input type="password" name="netgsm_password" value="{{ $setting->netgsm_password ?? '' }}" class="form-control" placeholder="Netgsm şifreniz">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="netgsm_msgheader">Mesaj Başlığı (Sender ID)</label>
                                        <input type="text" name="netgsm_msgheader" value="{{ $setting->netgsm_msgheader ?? 'SEYFIBABA' }}" class="form-control" placeholder="SEYFIBABA">
                                        <small class="text-muted">Netgsm panelinde tanımlı olan başlık adı</small>
                                    </div>
                                </div>

                                <div class="form-group col-12">
                                    <label class="custom-switch">
                                      <input {{ ($setting->netgsm_enabled ?? false) ? 'checked' : '' }} type="checkbox" name="netgsm_enabled" class="custom-switch-input">
                                      <span class="custom-switch-indicator"></span>
                                      <span class="custom-switch-description">SMS Gönderimini Aktif Et</span>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">Ayarları Kaydet</button>
                        </form>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Notlar</h4>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>Netgsm hesabınız yoksa <a href="https://www.netgsm.com.tr" target="_blank">netgsm.com.tr</a> adresinden oluşturabilirsiniz.</li>
                                <li>Mesaj başlığı (Sender ID) Netgsm panelinde onaylanmış olmalıdır.</li>
                                <li>OTP SMS'leri kayıt ve şifre sıfırlama işlemlerinde gönderilir.</li>
                                <li>SMS kredisi bittiğinde OTP gönderimi başarısız olur.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </section>
      </div>
@endsection
