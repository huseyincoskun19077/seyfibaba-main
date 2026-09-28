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
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header"><h4>SMS Bilgileri</h4></div>
                        <div class="card-body">
                            <div class="alert alert-secondary">
                                <strong>Gönderici başlığı (msgheader):</strong> {{ $msgheader }}
                                <br>
                                <small class="text-muted">
                                    “seyfibaba” görünüyorsa Admin → SMS Ayarları / Netgsm mesaj başlığını
                                    <strong>KUAFÖR TEDARİK</strong> (veya Netgsm’de onaylı başlığınız) yapın.
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
                                        İşaretlenirse yalnızca şifre değiştirmemiş satıcılara OTP + tanıtım gider.
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

                                <div id="customSearchBox" class="form-group d-none">
                                    <label>Dükkan / kullanıcı adı / telefon ara</label>
                                    <div class="input-group">
                                        <input type="text" id="customSearchInput" class="form-control" placeholder="Örn: Ahmet Kuaför, 0532..., dükkan adı" autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" id="customSearchBtn">Ara</button>
                                        </div>
                                    </div>
                                    <small class="text-muted">En az 2 karakter. Sonuçlardan istediğinizi işaretleyin.</small>
                                    <div id="searchResults" class="mt-2" style="max-height: 280px; overflow-y: auto; border: 1px solid #e4e6fc; border-radius: 4px; display:none;"></div>
                                </div>

                                <div id="usersSection" class="d-none mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="mb-0">
                                            <strong id="usersLabel">Alıcılar</strong>
                                            <span class="badge badge-primary" id="selectedCount">0</span> / <span id="totalCount">0</span> seçili
                                        </label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">Tümünü Seç</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">Tümünü Kaldır</button>
                                        </div>
                                    </div>
                                    <div class="form-group mb-2" id="listFilterWrap">
                                        <input type="text" id="listFilter" class="form-control form-control-sm" placeholder="Listede filtrele (ad / dükkan / telefon)...">
                                    </div>
                                    <div id="usersList" style="max-height: 380px; overflow-y: auto; border: 1px solid #e4e6fc; border-radius: 4px; padding: 8px;"></div>
                                </div>

                                <div id="usersLoading" class="d-none text-center py-3">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2 text-muted">Kullanıcılar yükleniyor...</p>
                                </div>

                                <div id="previewBox" class="alert alert-info d-none">
                                    <i class="fas fa-users"></i> <strong id="previewLabel"></strong>: <span id="previewCount">0</span> kişi listelendi
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
                                        <small class="text-muted">Netgsm karakter limiti: Türkçe 70 / Latin 160 karakter ≈ 1 SMS</small>
                                        <small><span id="charCount" class="font-weight-bold">0</span> / 600 karakter</small>
                                    </div>
                                    <textarea name="message" class="form-control mt-1" rows="5" maxlength="600" id="messageBox">{{ old('message') }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
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
    var usersSection = document.getElementById('usersSection');
    var usersLoading = document.getElementById('usersLoading');
    var usersList = document.getElementById('usersList');
    var selectedCountEl = document.getElementById('selectedCount');
    var totalCountEl = document.getElementById('totalCount');
    var usersLabel = document.getElementById('usersLabel');
    var selectAllBtn = document.getElementById('selectAllBtn');
    var deselectAllBtn = document.getElementById('deselectAllBtn');
    var listFilter = document.getElementById('listFilter');
    var listFilterWrap = document.getElementById('listFilterWrap');
    var customSearchBox = document.getElementById('customSearchBox');
    var customSearchInput = document.getElementById('customSearchInput');
    var customSearchBtn = document.getElementById('customSearchBtn');
    var searchResults = document.getElementById('searchResults');
    var submitBtn = document.getElementById('submitBtn');

    var allUsers = [];
    var selectedMap = {};

    function updateCharCount() {
        charCount.textContent = messageBox.value.length;
    }
    updateCharCount();
    messageBox.addEventListener('input', updateCharCount);

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function syncSubmit() {
        var n = Object.keys(selectedMap).length;
        selectedCountEl.textContent = n;
        var msgOk = includeOtp.checked || messageBox.value.trim().length > 0;
        submitBtn.disabled = n === 0 || !segmentSelect.value || !msgOk;
    }

    function renderUsers(filter) {
        filter = (filter || '').toLowerCase();
        usersList.innerHTML = '';
        var shown = 0;

        allUsers.forEach(function(u) {
            var name = u.name || '';
            var phone = u.phone || '';
            var shop = u.shop_name || '';
            var hay = (name + ' ' + phone + ' ' + shop).toLowerCase();
            if (filter && hay.indexOf(filter) === -1) return;

            shown++;
            var checked = selectedMap[u.id] ? 'checked' : '';
            var shopHtml = shop ? '<span class="badge badge-light mr-2">' + esc(shop) + '</span>' : '';
            var otpBadge = u.must_change_password ? '<span class="badge badge-warning ml-1">OTP adayı</span>' : '';

            var div = document.createElement('div');
            div.className = 'd-flex align-items-center py-1 px-2';
            div.style.borderBottom = '1px solid #f2f2f2';
            div.innerHTML =
                '<label class="custom-switch mb-0 mr-3" style="cursor:pointer">' +
                    '<input type="checkbox" class="custom-switch-input user-check" data-id="' + u.id + '" ' + checked + '>' +
                    '<span class="custom-switch-indicator"></span>' +
                '</label>' +
                '<div class="flex-grow-1">' +
                    '<div>' + shopHtml + '<strong>' + esc(name || 'İsimsiz') + '</strong>' + otpBadge + '</div>' +
                    '<small class="text-muted">' + esc(phone) + '</small>' +
                '</div>';
            usersList.appendChild(div);
        });

        if (shown === 0) {
            usersList.innerHTML = '<p class="text-center text-muted py-3 mb-0">Kayıt yok / filtreye uymuyor.</p>';
        }

        totalCountEl.textContent = allUsers.length;
        syncSubmit();
        syncHiddenInputs();
    }

    function syncHiddenInputs() {
        usersList.querySelectorAll('input[name="selected_user_ids[]"]').forEach(function(el) {
            el.remove();
        });
        // Put hidden inputs on form (even if filtered out of visible list)
        var form = document.getElementById('smsForm');
        form.querySelectorAll('input[name="selected_user_ids[]"]').forEach(function(el) { el.remove(); });
        Object.keys(selectedMap).forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_user_ids[]';
            input.value = id;
            form.appendChild(input);
        });
    }

    function setSelected(user, on) {
        if (on) selectedMap[user.id] = user;
        else delete selectedMap[user.id];
        syncSubmit();
        syncHiddenInputs();
    }

    usersList.addEventListener('change', function(e) {
        if (!e.target.classList.contains('user-check')) return;
        var id = parseInt(e.target.getAttribute('data-id'), 10);
        var user = allUsers.find(function(u) { return u.id === id; });
        if (!user) return;
        setSelected(user, e.target.checked);
        selectedCountEl.textContent = Object.keys(selectedMap).length;
    });

    selectAllBtn.addEventListener('click', function() {
        allUsers.forEach(function(u) { selectedMap[u.id] = u; });
        renderUsers(listFilter.value);
    });

    deselectAllBtn.addEventListener('click', function() {
        selectedMap = {};
        renderUsers(listFilter.value);
    });

    listFilter.addEventListener('input', function() {
        renderUsers(this.value);
    });

    function syncOtpUi() {
        var on = includeOtp.checked;
        otpHint.classList.toggle('d-none', !on);
        messageLabel.textContent = on ? 'Tanıtım metni (OTP altına eklenir)' : 'Mesaj';
        messageBox.required = !on;
        if (on && segmentSelect.value && segmentSelect.value !== 'custom' &&
            ['all', 'logged_in', 'has_products', 'logged_in_no_products'].indexOf(segmentSelect.value) !== -1) {
            segmentSelect.value = 'sellers_awaiting_first_login';
            loadSegmentUsers();
        }
        syncSubmit();
    }

    includeOtp.addEventListener('change', function() {
        syncOtpUi();
        if (segmentSelect.value && segmentSelect.value !== 'custom') {
            loadSegmentUsers();
        }
    });
    syncOtpUi();

    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            if (this.value) {
                messageBox.value = this.value;
                updateCharCount();
                syncSubmit();
            }
        });
    }
    messageBox.addEventListener('input', syncSubmit);

    function loadSegmentUsers() {
        var segment = segmentSelect.value;
        customSearchBox.classList.toggle('d-none', segment !== 'custom');
        listFilterWrap.classList.toggle('d-none', segment === 'custom');
        searchResults.style.display = 'none';
        searchResults.innerHTML = '';

        if (!segment) {
            usersSection.classList.add('d-none');
            usersLoading.classList.add('d-none');
            previewBox.classList.add('d-none');
            allUsers = [];
            selectedMap = {};
            syncSubmit();
            return;
        }

        if (segment === 'custom') {
            usersLoading.classList.add('d-none');
            allUsers = Object.keys(selectedMap).map(function(k) { return selectedMap[k]; });
            usersLabel.textContent = 'Seçilen alıcılar';
            renderUsers('');
            usersSection.classList.toggle('d-none', allUsers.length === 0);
            previewBox.classList.add('d-none');
            return;
        }

        usersSection.classList.add('d-none');
        usersLoading.classList.remove('d-none');
        selectedMap = {};
        listFilter.value = '';

        fetch("{{ route('admin.sms-campaigns.users') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                segment: segment,
                include_first_login_otp: includeOtp.checked ? 1 : 0
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allUsers = data.users || [];
            allUsers.forEach(function(u) { selectedMap[u.id] = u; });
            usersLabel.textContent = data.segment_label || 'Alıcılar';
            usersLoading.classList.add('d-none');
            renderUsers('');
            usersSection.classList.remove('d-none');
            previewCount.textContent = allUsers.length;
            previewLabel.textContent = data.segment_label || segment;
            previewBox.classList.remove('d-none');
        })
        .catch(function() {
            usersLoading.classList.add('d-none');
        });
    }

    segmentSelect.addEventListener('change', loadSegmentUsers);

    function runCustomSearch() {
        var q = customSearchInput.value.trim();
        if (q.length < 2) {
            searchResults.style.display = 'block';
            searchResults.innerHTML = '<p class="text-muted p-2 mb-0">En az 2 karakter yazın.</p>';
            return;
        }

        searchResults.style.display = 'block';
        searchResults.innerHTML = '<p class="text-muted p-2 mb-0"><i class="fas fa-spinner fa-spin"></i> Aranıyor...</p>';

        fetch("{{ route('admin.sms-campaigns.search-users') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ q: q })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var list = data.users || [];
            if (!list.length) {
                searchResults.innerHTML = '<p class="text-muted p-2 mb-0">Sonuç yok.</p>';
                return;
            }
            searchResults.innerHTML = '';
            list.forEach(function(u) {
                var already = !!selectedMap[u.id];
                var shop = u.shop_name ? '<span class="badge badge-light mr-1">' + esc(u.shop_name) + '</span>' : '';
                var row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between py-2 px-2';
                row.style.borderBottom = '1px solid #f2f2f2';
                row.innerHTML =
                    '<div>' + shop + '<strong>' + esc(u.name || 'İsimsiz') + '</strong><br><small class="text-muted">' + esc(u.phone) + '</small></div>' +
                    '<button type="button" class="btn btn-sm ' + (already ? 'btn-success' : 'btn-outline-primary') + ' search-add-btn" data-id="' + u.id + '">' +
                    (already ? 'Seçili' : 'Ekle') + '</button>';
                row.querySelector('button')._user = u;
                searchResults.appendChild(row);
            });
        });
    }

    customSearchBtn.addEventListener('click', runCustomSearch);
    customSearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            runCustomSearch();
        }
    });

    searchResults.addEventListener('click', function(e) {
        var btn = e.target.closest('.search-add-btn');
        if (!btn) return;
        var u = btn._user;
        if (!u) return;
        selectedMap[u.id] = u;
        // merge into allUsers for list
        if (!allUsers.find(function(x) { return x.id === u.id; })) {
            allUsers.push(u);
        }
        btn.className = 'btn btn-sm btn-success search-add-btn';
        btn.textContent = 'Seçili';
        usersLabel.textContent = 'Seçilen alıcılar';
        usersSection.classList.remove('d-none');
        renderUsers(listFilter.value);
    });

    document.getElementById('smsForm').addEventListener('submit', function(e) {
        syncHiddenInputs();
        if (Object.keys(selectedMap).length === 0) {
            e.preventDefault();
            alert('En az bir alıcı seçin.');
            submitBtn.disabled = false;
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
    });

    if (segmentSelect.value) {
        loadSegmentUsers();
    }
});
</script>
@endsection
