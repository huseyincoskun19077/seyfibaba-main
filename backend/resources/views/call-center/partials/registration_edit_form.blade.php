@php
    $onboarding = $onboarding ?? \App\Services\CallCenter\QuickSellerOnboardingStatus::for($vendor);
    $canEditPhone = (bool) ($onboarding['can_edit_phone'] ?? false);
    $currentPhone = $currentPhone ?? ($vendor->user?->phone ?? $vendor->phone);
@endphp

@if($onboarding['can_edit_registration'] ?? false)
    <div class="{{ $wrapperClass ?? 'mt-3' }} border rounded p-3 bg-light">
        <h6 class="mb-3"><i class="fas fa-edit"></i> Kayıt Bilgilerini Düzenle</h6>
        <form method="POST" action="{{ $updateRoute }}" onsubmit="return confirm('Kayıt bilgileri güncellensin mi?');">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="registration-shop-name">Firma / Dükkan Adı</label>
                    <input type="text"
                           id="registration-shop-name"
                           name="shop_name"
                           class="form-control"
                           value="{{ old('shop_name', $vendor->shop_name) }}"
                           required>
                </div>
                <div class="form-group col-md-6">
                    <label for="registration-contact-name">Yetkili Adı Soyadı</label>
                    <input type="text"
                           id="registration-contact-name"
                           name="contact_name"
                           class="form-control"
                           value="{{ old('contact_name', $vendor->user?->name) }}"
                           required>
                </div>
            </div>

            <div class="form-group mb-2">
                <label for="registration-phone">Telefon</label>
                <input type="tel"
                       id="registration-phone"
                       name="phone"
                       class="form-control"
                       value="{{ old('phone', $currentPhone) }}"
                       {{ $canEditPhone ? 'required' : 'readonly' }}>
                @if($canEditPhone)
                    <small class="text-muted">Yanlış numara girildiyse düzeltip aşağıdan SMS gönderebilirsiniz.</small>
                @else
                    <small class="text-muted">Satıcı yeni şifresini oluşturduğu için telefon numarası değiştirilemez.</small>
                @endif
            </div>

            @if($canEditPhone)
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="send_sms" id="registration-send-sms" value="1">
                    <label class="form-check-label" for="registration-send-sms">
                        Telefon değiştiyse hoş geldin SMS gönder (aynı giriş şifresi)
                    </label>
                </div>
            @endif

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-save"></i> Kaydı Güncelle
            </button>
        </form>
    </div>
@endif
