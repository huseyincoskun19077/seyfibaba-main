@extends('call-center.layout.master')

@section('title')
<title>Yeni SMS Gönder</title>
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Yeni SMS Gönder</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('call-center.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item"><a href="{{ route('call-center.sms-campaigns.index') }}">SMS Kampanyaları</a></div>
                <div class="breadcrumb-item active">Yeni SMS</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header"><h4>SMS Bilgileri</h4></div>
                        <div class="card-body">
                            @if($messages->isEmpty())
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Henüz admin tarafından mesaj şablonu oluşturulmamış. Lütfen admin ile iletişime geçin.
                                </div>
                            @else
                            <form action="{{ route('call-center.sms-campaigns.store') }}" method="POST" id="smsForm">
                                @csrf

                                <div class="form-group">
                                    <label>Başlık (dahili not)</label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required placeholder="Örn: Ağustos bilgilendirme SMS">
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

                                {{-- Kullanıcı listesi --}}
                                <div id="usersSection" class="d-none mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="mb-0"><strong id="usersLabel">Kullanıcılar</strong> <span class="badge badge-primary" id="selectedCount">0</span> / <span id="totalCount">0</span> seçili</label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">Tümünü Seç</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">Tümünü Kaldır</button>
                                        </div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="İsim veya telefon ile ara...">
                                    </div>
                                    <div id="usersList" style="max-height: 350px; overflow-y: auto; border: 1px solid #e4e6fc; border-radius: 4px; padding: 8px;">
                                    </div>
                                </div>

                                <div id="usersLoading" class="d-none text-center py-3">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2 text-muted">Kullanıcılar yükleniyor...</p>
                                </div>

                                <div class="form-group">
                                    <label>Mesaj Şablonu Seçin</label>
                                    <select name="message_id" id="messageSelect" class="form-control" required>
                                        <option value="">-- Mesaj Seçiniz --</option>
                                        @foreach($messages as $msg)
                                            <option value="{{ $msg->id }}" data-message="{{ $msg->message }}" data-chars="{{ $msg->char_count }}" {{ old('message_id') == $msg->id ? 'selected' : '' }}>
                                                {{ $msg->title }} ({{ $msg->char_count }} karakter)
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="message" id="hiddenMessage" value="{{ old('message') }}">
                                </div>

                                <div id="messagePreview" class="alert alert-light d-none">
                                    <strong>Mesaj Önizleme:</strong>
                                    <p id="messageText" class="mb-0 mt-2" style="white-space: pre-wrap;"></p>
                                    <small class="text-muted"><span id="charCount">0</span> karakter</small>
                                </div>

                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                    <i class="fas fa-paper-plane"></i> Gönder
                                </button>
                                <a href="{{ route('call-center.sms-campaigns.index') }}" class="btn btn-secondary">İptal</a>
                            </form>
                            @endif
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
    var usersSection = document.getElementById('usersSection');
    var usersLoading = document.getElementById('usersLoading');
    var usersList = document.getElementById('usersList');
    var selectedCountEl = document.getElementById('selectedCount');
    var totalCountEl = document.getElementById('totalCount');
    var usersLabel = document.getElementById('usersLabel');
    var selectAllBtn = document.getElementById('selectAllBtn');
    var deselectAllBtn = document.getElementById('deselectAllBtn');
    var userSearch = document.getElementById('userSearch');
    var submitBtn = document.getElementById('submitBtn');
    var messageSelect = document.getElementById('messageSelect');
    var messagePreview = document.getElementById('messagePreview');
    var messageText = document.getElementById('messageText');
    var charCount = document.getElementById('charCount');
    var hiddenMessage = document.getElementById('hiddenMessage');

    var allUsers = [];

    function updateSelectedCount() {
        var checked = usersList.querySelectorAll('input[type=checkbox]:checked').length;
        selectedCountEl.textContent = checked;
        submitBtn.disabled = checked === 0 || !messageSelect.value;
    }

    function renderUsers(filter) {
        filter = (filter || '').toLowerCase();
        usersList.innerHTML = '';

        allUsers.forEach(function(u) {
            var name = u.name || '';
            var phone = u.phone || '';
            if (filter && name.toLowerCase().indexOf(filter) === -1 && phone.indexOf(filter) === -1) return;

            var div = document.createElement('div');
            div.className = 'd-flex align-items-center py-1 px-2 user-row';
            div.style.borderBottom = '1px solid #f2f2f2';
            div.innerHTML =
                '<label class="custom-switch mb-0 mr-3" style="cursor:pointer">' +
                    '<input type="checkbox" name="selected_phones[]" value="' + phone + '" class="custom-switch-input user-check" checked>' +
                    '<span class="custom-switch-indicator"></span>' +
                '</label>' +
                '<span class="mr-3" style="min-width:180px">' + (name || '<em class="text-muted">İsimsiz</em>') + '</span>' +
                '<span class="text-muted">' + phone + '</span>';
            usersList.appendChild(div);
        });

        updateSelectedCount();
    }

    if (segmentSelect) {
        segmentSelect.addEventListener('change', function() {
            var segment = this.value;
            if (!segment) {
                usersSection.classList.add('d-none');
                usersLoading.classList.add('d-none');
                allUsers = [];
                updateSelectedCount();
                return;
            }

            usersSection.classList.add('d-none');
            usersLoading.classList.remove('d-none');
            userSearch.value = '';

            fetch("{{ route('call-center.sms-campaigns.users') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ segment: segment })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                allUsers = data.users || [];
                totalCountEl.textContent = allUsers.length;
                usersLabel.textContent = data.segment_label;
                usersLoading.classList.add('d-none');

                if (allUsers.length > 0) {
                    renderUsers('');
                    usersSection.classList.remove('d-none');
                } else {
                    usersList.innerHTML = '<p class="text-center text-muted py-3">Bu segmentte kullanıcı bulunamadı.</p>';
                    usersSection.classList.remove('d-none');
                }
            });
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            usersList.querySelectorAll('input[type=checkbox]').forEach(function(cb) { cb.checked = true; });
            updateSelectedCount();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            usersList.querySelectorAll('input[type=checkbox]').forEach(function(cb) { cb.checked = false; });
            updateSelectedCount();
        });
    }

    if (userSearch) {
        userSearch.addEventListener('input', function() {
            renderUsers(this.value);
        });
    }

    usersList.addEventListener('change', function(e) {
        if (e.target.classList.contains('user-check')) updateSelectedCount();
    });

    if (messageSelect) {
        messageSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (this.value) {
                hiddenMessage.value = opt.getAttribute('data-message');
                messageText.textContent = opt.getAttribute('data-message');
                charCount.textContent = opt.getAttribute('data-chars');
                messagePreview.classList.remove('d-none');
            } else {
                messagePreview.classList.add('d-none');
                hiddenMessage.value = '';
            }
            updateSelectedCount();
        });
    }

    var smsForm = document.getElementById('smsForm');
    if (smsForm) {
        smsForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
        });
    }
});
</script>
@endsection
