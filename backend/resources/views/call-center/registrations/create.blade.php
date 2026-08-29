@extends('call-center.layout.master')

@section('title')
<title>Hızlı Satıcı Kaydı</title>
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Hızlı Satıcı Kaydı</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('call-center.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item active">Hızlı Kayıt</div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Temel Bilgiler</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('call-center.registrations.store') }}">
                            @csrf

                            <div class="form-group">
                                <label>Firma Adı <span class="text-danger">*</span></label>
                                <input type="text" name="shop_name" class="form-control" value="{{ old('shop_name') }}" required>
                            </div>

                            <div class="form-group">
                                <label>Yetkili Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name') }}" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Telefon <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" placeholder="5XXXXXXXXX" value="{{ old('phone') }}" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>E-posta <small class="text-muted">(opsiyonel)</small></label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="İsteğe bağlı">
                                    <small class="text-muted">Boş bırakılabilir; satıcı sonra panelden ekleyebilir.</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>İl</label>
                                    <select name="state_id" id="state_id" class="form-control">
                                        <option value="">Seçiniz</option>
                                        @foreach($states as $state)
                                            <option value="{{ $state->id }}" @selected(old('state_id') == $state->id)>{{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>İlçe</label>
                                    <select name="city_id" id="city_id" class="form-control">
                                        <option value="">Önce il seçin</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Ürün Kategorisi (opsiyonel)</label>
                                <select name="category_id" class="form-control">
                                    <option value="">Seçiniz</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Not (opsiyonel)</label>
                                <textarea name="note" rows="3" class="form-control" placeholder="Görüşme notu">{{ old('note') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check"></i> Kaydı Oluştur
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h4>Bilgi</h4></div>
                    <div class="card-body">
                        <p class="mb-2">Kayıt oluşturulunca satıcıya <strong>tek kullanımlık şifre SMS</strong> ile gider. E-posta girildiyse giriş bilgisi mail ile de gönderilir.</p>
                        <p class="mb-2">E-posta zorunlu değildir; satıcı sonra profil/mağaza ayarlarından ekleyebilir.</p>
                        <p class="mb-2">Satıcı ilk girişte yeni şifre oluşturmak zorundadır; değiştirmeden panelde ilerleyemez.</p>
                        <p class="mb-2">Satıcı hesabı <strong>hemen aktif</strong> olur; admin onayı gerekmez.</p>
                        <p class="mb-0">Ürün yüklemek için satıcının panelden KYC doğrulaması yapması gerekir.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('script')
<script>
(function ($) {
    'use strict';

    function loadCities(stateId, selectedCityId) {
        $('#city_id').html('<option value="">Yükleniyor...</option>');
        if (!stateId) {
            $('#city_id').html('<option value="">Önce il seçin</option>');
            return;
        }

        $.get('{{ url('/call-center/cities') }}/' + stateId, function (response) {
            let options = '<option value="">Seçiniz</option>';
            (response.cities || []).forEach(function (city) {
                const selected = String(selectedCityId) === String(city.id) ? 'selected' : '';
                options += '<option value="' + city.id + '" ' + selected + '>' + city.name + '</option>';
            });
            $('#city_id').html(options);
        });
    }

    $(document).ready(function () {
        $('#state_id').on('change', function () {
            loadCities($(this).val(), null);
        });

        @if(old('state_id'))
            loadCities('{{ old('state_id') }}', '{{ old('city_id') }}');
        @endif
    });
})(jQuery);
</script>
@endsection
