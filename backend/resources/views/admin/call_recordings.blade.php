@extends('admin.master_layout')
@section('title')
<title>Çağrı Kayıtları - {{ __('admin.Admin Panel') }}</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1><i class="fas fa-phone-volume mr-2"></i>Çağrı Ses Kayıtları</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Çağrı Kayıtları</div>
      </div>
    </div>

    <div class="section-body">
      <div class="alert alert-info">
        Netgsm Netsantral CDR API ile görüşmeleri çeker, ses varsa <code>uploads/call-recordings</code> altına kaydeder.
        AI Transkript kullanılmaz. API bilgilerini aşağıdaki formdan girin.
      </div>

      <div class="card">
        <div class="card-header"><h4><i class="fas fa-key mr-2"></i>API Ayarları (Netsantral / Netsipp)</h4></div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.call-recordings.settings') }}">
            @csrf
            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="netsantral_enabled" name="netsantral_enabled" value="1" {{ ($setting->netsantral_enabled ?? false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="netsantral_enabled">Netsantral CDR senkronunu aktif et</label>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Netgsm kullanıcı kodu (usercode)</label>
                  <input type="text" name="netsantral_usercode" class="form-control" value="{{ old('netsantral_usercode', $setting->netsantral_usercode ?? '') }}" placeholder="Netgsm abone / kullanıcı kodu" autocomplete="off">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Netgsm şifre</label>
                  @php
                    $pw = $setting->netsantral_password ?? '';
                    $pwMask = $pw !== '' ? (substr($pw, 0, 2) . '****' . substr($pw, -2)) : '';
                  @endphp
                  <input type="password" name="netsantral_password" class="form-control" value="{{ $pwMask }}" placeholder="Şifre" autocomplete="new-password">
                  <small class="text-muted">Değiştirmek istemiyorsanız alana dokunmayın.</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Santral no (pbxnum)</label>
                  <input type="text" name="netsantral_pbxnum" class="form-control" value="{{ old('netsantral_pbxnum', $setting->netsantral_pbxnum ?? '') }}" placeholder="850xxxxxxx veya 312xxxxxxx" autocomplete="off">
                  <small class="text-muted">Başında 0 veya 90 olmadan yazın. Sistem önce pbxnum’suz resmi CDR isteğini dener.</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Netsipp API key</label>
                  @php
                    $ak = $setting->netsipp_api_key ?? '';
                    $akMask = $ak !== '' ? (substr($ak, 0, 4) . '****' . substr($ak, -4)) : '';
                  @endphp
                  <input type="password" name="netsipp_api_key" class="form-control" value="{{ $akMask }}" placeholder="Bearer API key" autocomplete="new-password">
                  <small class="text-muted">Netsipp hesabı için asıl kaynak. Çağrı listesi: /v1/reports/call-details</small>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save mr-1"></i> API ayarlarını kaydet
            </button>
            <p class="text-muted mt-3 mb-0 small">
              <strong>Netsipp:</strong> API key + Aktif → çağrı listesini çeker (ses URL vermez).
              <strong>Klasik Netgsm:</strong> usercode/şifre ile <code>netsantral/report</code> → ses dosyası (çoğu Netsipp hesabında 331).
              Ses dinlemek için Netgsm’den klasik CDR izni gerekir.
            </p>
          </form>
        </div>
      </div>

      @if(session('messege'))
        <div class="alert alert-{{ session('alert-type') === 'error' ? 'danger' : (session('alert-type') === 'warning' ? 'warning' : 'success') }}">
          {{ session('messege') }}
        </div>
      @endif

      @if(!$credsOk)
        <div class="alert alert-warning">
          Senkron için yukarıdan kullanıcı kodu + şifre girip <strong>aktif</strong> edin (veya SMS Netgsm bilgisi tanımlı olsun).
          Şu an API hazır değil; “Çek”e basınca hata mesajı göreceksiniz.
        </div>
      @endif

      <div class="card">
        <div class="card-header"><h4>Senkronize et</h4></div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.call-recordings.sync') }}" class="form-inline flex-wrap" id="call-sync-form">
            @csrf
            <div class="form-group mr-2 mb-2">
              <label class="mr-1">Başlangıç</label>
              <input type="date" name="date_from" class="form-control" value="{{ request('date_from', now()->subDays(2)->toDateString()) }}" required>
            </div>
            <div class="form-group mr-2 mb-2">
              <label class="mr-1">Bitiş</label>
              <input type="date" name="date_to" class="form-control" value="{{ request('date_to', now()->toDateString()) }}" required>
            </div>
            <div class="form-group mr-3 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="download_audio" name="download_audio" value="1" checked>
                <label class="custom-control-label" for="download_audio">Ses dosyalarını indir</label>
              </div>
            </div>
            <button type="submit" class="btn btn-primary mb-2" id="call-sync-btn">
              <i class="fas fa-sync mr-1"></i> Çek
            </button>
          </form>
          <small class="text-muted">Tek seferde en fazla 31 gün. Büyük aralıklarda parçalayın. İşlem 1–2 dk sürebilir.</small>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h4>Kayıtlar</h4></div>
        <div class="card-body">
          <form method="GET" class="form-inline mb-3">
            <input type="text" name="q" class="form-control mr-2 mb-2" placeholder="Numara / uniqueid" value="{{ request('q') }}">
            <select name="status" class="form-control mr-2 mb-2">
              <option value="">Tüm durumlar</option>
              @foreach(['downloaded'=>'İndirildi','pending'=>'Bekliyor','no_recording'=>'Ses yok','failed'=>'Hata'] as $val => $label)
                <option value="{{ $val }}" @selected(request('status')===$val)>{{ $label }}</option>
              @endforeach
            </select>
            <input type="date" name="date_from" class="form-control mr-2 mb-2" value="{{ request('date_from') }}">
            <input type="date" name="date_to" class="form-control mr-2 mb-2" value="{{ request('date_to') }}">
            <button class="btn btn-secondary mb-2" type="submit">Filtrele</button>
          </form>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Tarih</th>
                  <th>Kaynak</th>
                  <th>Hedef</th>
                  <th>Süre</th>
                  <th>Durum</th>
                  <th>Ses</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($recordings as $row)
                  <tr>
                    <td>{{ $row->called_at ? $row->called_at->format('d.m.Y H:i') : '—' }}</td>
                    <td>{{ $row->source ?: '—' }}</td>
                    <td>{{ $row->destination ?: '—' }}</td>
                    <td>{{ $row->duration_sec !== null ? $row->duration_sec.' sn' : '—' }}</td>
                    <td>
                      <span class="badge badge-{{ $row->sync_status === 'downloaded' ? 'success' : ($row->sync_status === 'failed' ? 'danger' : 'secondary') }}">
                        {{ $row->sync_status }}
                      </span>
                      @if($row->sync_error)
                        <br><small class="text-danger">{{ \Illuminate\Support\Str::limit($row->sync_error, 80) }}</small>
                      @endif
                    </td>
                    <td style="min-width:220px;">
                      @if($row->hasLocalAudio())
                        <audio controls preload="none" style="max-width:200px;height:32px;">
                          <source src="{{ route('admin.call-recordings.play', $row->id) }}" type="audio/mpeg">
                        </audio>
                      @elseif($row->remote_recording_url)
                        <span class="text-muted">Uzak URL var, henüz indirilmedi</span>
                      @else
                        <span class="text-muted">Yok</span>
                      @endif
                    </td>
                    <td class="text-nowrap">
                      @if($row->remote_recording_url || $row->hasLocalAudio())
                        <a href="{{ route('admin.call-recordings.download', $row->id) }}" class="btn btn-sm btn-outline-primary">
                          <i class="fas fa-download"></i>
                        </a>
                      @endif
                      <small class="d-block text-muted mt-1" title="{{ $row->uniqueid }}">{{ \Illuminate\Support\Str::limit($row->uniqueid, 18) }}</small>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center text-muted">Kayıt yok. Tarih seçip senkronlayın.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $recordings->links() }}
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@section('script')
<script>
(function ($) {
  $('#call-sync-form').on('submit', function () {
    var $btn = $('#call-sync-btn');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Çekiliyor...');
  });
})(jQuery);
</script>
@endsection
