@extends('admin.master_layout')

@section('title')
<title>Mesaj Şablonu Düzenle</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Mesaj Şablonu Düzenle</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
                <div class="breadcrumb-item"><a href="{{ route('admin.sms-campaigns.messages') }}">Mesaj Şablonları</a></div>
                <div class="breadcrumb-item active">Düzenle</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h4>{{ $msg->title }}</h4></div>
                        <div class="card-body">
                            <form action="{{ route('admin.sms-campaigns.messages.update', $msg->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label>Şablon Başlığı</label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title', $msg->title) }}" required>
                                </div>

                                <div class="form-group">
                                    <label>Mesaj İçeriği</label>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Türkçe karakterli: 70 karakter = 1 SMS | Latin: 160 karakter = 1 SMS</small>
                                        <small><span id="charCount" class="font-weight-bold text-primary">0</span> / 600 karakter</small>
                                    </div>
                                    <textarea name="message" class="form-control mt-1" rows="5" maxlength="600" required id="messageBox">{{ old('message', $msg->message) }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label class="custom-switch">
                                        <input type="checkbox" name="is_active" class="custom-switch-input" {{ $msg->is_active ? 'checked' : '' }}>
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">Aktif</span>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Güncelle</button>
                                <a href="{{ route('admin.sms-campaigns.messages') }}" class="btn btn-secondary">İptal</a>
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
    var messageBox = document.getElementById('messageBox');
    var charCount = document.getElementById('charCount');
    function update() { charCount.textContent = messageBox.value.length; }
    update();
    messageBox.addEventListener('input', update);
});
</script>
@endsection
