@extends('admin.master_layout')

@section('title')
<title>Yeni SMS Gönder</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Yeni SMS Gönder</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
                <div class="breadcrumb-item"><a href="{{ route('admin.sms-campaigns.index') }}">SMS Kampanyaları</a></div>
                <div class="breadcrumb-item active">Yeni SMS</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h4>SMS Bilgileri</h4></div>
                        <div class="card-body">
                            <div class="alert alert-secondary">
                                <strong>Gönderici başlığı (msgheader):</strong> {{ $msgheader }}
                                <br>
                                <small class="text-muted">
                                    “seyfibaba” görünüyorsa Admin → SMS Ayarları / Netgsm mesaj başlığını
                                    <strong>KUAFÖR TEDARİK</strong> (veya Netgsm’de onaylı başlığınız) yapın.
                                    OTP SMS’leri de aynı başlıkla gider.
                                </small>
                            </div>

                            <form action="{{ route('admin.sms-campaigns.store') }}" method="POST" id="smsForm">
                                @csrf

                                <div class="form-group">
                                    <label>Başlık (dahili not)</label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="includeOtp"
                                               name="include_first_login_otp"
                                               value="1"
                                               {{ old('include_first_login_otp') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="includeOtp">
                                            <strong>Tek kullanımlık giriş şifresi + tanıtım</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        İşaretlenirse her satıcıya çağrı merkezi kaydıyla aynı formatta
                                        kullanıcı adı + tek kullanımlık şifre gönderilir; altındaki metin tanıtım olarak eklenir.
                                        Yalnızca şifre değiştirmemiş satıcılara gider.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>Hedef Segment</label>
                                    <select name="segment" id="segmentSelect" class="form-control" required>
                                        <option value="">Seçiniz...</option>
                                        @foreach($segments as $key => $label)
                                            <option value="{{ $key }}" {{ old('segment') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="previewBox" class="alert alert-info d-none">
                                    <i class="fas fa-users"></i> <strong id="previewLabel"></strong>: <span id="previewCount">0</span> kişi
                                </div>

                                <div id="otpHint" class="alert alert-warning d-none">
                                    Örnek SMS içeriği (şifre kişiye özel üretilir):
                                    <pre class="mb-0 mt-2" style="white-space:pre-wrap;font-size:12px;">Hosgeldiniz!
Tum islemleriniz icin gecerli Kullanici Adiniz: 5XXXXXXXXX Sifreniz:123456
{{ \App\Support\SellerLoginUrl::publicDisplay() }}
Sifrenizi kimseyle paylasmayiniz.

[sizin tanıtım metniniz]</pre>
                                </div>

                                @if($messages->count() > 0)
                                <div class="form-group">
                                    <label>Hazır Mesaj Şablonu <small class="text-muted">(seçin veya aşağıya kendiniz yazın)</small></label>
                                    <select id="templateSelect" class="form-control">
                                        <option value="">-- Şablon Seç --</option>
                                        @foreach($messages as $msg)
                                            <option value="{{ $msg->message }}" data-chars="{{ $msg->char_count }}">{{ $msg->title }} ({{ $msg->char_count }} karakter)</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <div class="form-group">
                                    <label id="messageLabel">Mesaj / Tanıtım</label>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Netgsm karakter limiti: <strong>Türkçe karakterli SMS = 70 karakter / 1 SMS, 160 karakter / 1 SMS (latin)</strong></small>
                                        <small><span id="charCount" class="font-weight-bold">0</span> / 600 karakter</small>
                                    </div>
                                    <textarea name="message" class="form-control mt-1" rows="5" maxlength="600" id="messageBox">{{ old('message') }}</textarea>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        OTP modunda mesaj opsiyoneldir (sadece tanıtım). OTP bloğu otomatik eklenir.
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-paper-plane"></i> Gönder
                                </button>
                                <a href="{{ route('admin.sms-campaigns.index') }}" class="btn btn-secondary">İptal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var segmentSelect = document.getElementById('segmentSelect');
    var previewBox = document.getElementById('previewBox');
    var previewCount = document.getElementById('previewCount');
    var previewLabel = document.getElementById('previewLabel');
    var messageBox = document.getElementById('messageBox');
    var charCount = document.getElementById('charCount');
    var templateSelect = document.getElementById('templateSelect');
    var includeOtp = document.getElementById('includeOtp');
    var otpHint = document.getElementById('otpHint');
    var messageLabel = document.getElementById('messageLabel');

    function updateCharCount() {
        charCount.textContent = messageBox.value.length;
    }
    updateCharCount();
    messageBox.addEventListener('input', updateCharCount);

    function syncOtpUi() {
        var on = includeOtp.checked;
        otpHint.classList.toggle('d-none', !on);
        messageLabel.textContent = on ? 'Tanıtım metni (OTP altına eklenir)' : 'Mesaj';
        messageBox.required = !on;
        if (on && (!segmentSelect.value || segmentSelect.value === 'all' || segmentSelect.value === 'logged_in' || segmentSelect.value === 'has_products' || segmentSelect.value === 'logged_in_no_products')) {
            segmentSelect.value = 'sellers_awaiting_first_login';
        }
        refreshPreview();
    }

    includeOtp.addEventListener('change', syncOtpUi);
    syncOtpUi();

    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            if (this.value) {
                messageBox.value = this.value;
                updateCharCount();
            }
        });
    }

    function refreshPreview() {
        var segment = segmentSelect.value;
        if (!segment) { previewBox.classList.add('d-none'); return; }

        fetch("{{ route('admin.sms-campaigns.preview') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                segment: segment,
                include_first_login_otp: includeOtp.checked ? 1 : 0
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            previewCount.textContent = data.count;
            previewLabel.textContent = data.segment_label;
            previewBox.classList.remove('d-none');
        });
    }

    segmentSelect.addEventListener('change', refreshPreview);

    document.getElementById('smsForm').addEventListener('submit', function() {
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
    });
});
</script>
@endsection
