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
        <strong>Netsipp:</strong> Ses kayıtları klasik API ile gelmez. Netgsm’in dediği gibi
        <strong>Netsipp Webhook → CDR</strong> senaryosu kullanılır; çağrı bitince <code>seskaydi</code> linki buraya düşer ve dosya indirilir.
      </div>

      <div class="card">
        <div class="card-header"><h4><i class="fas fa-key mr-2"></i>API / Webhook Ayarları</h4></div>
        <div class="card-body">
          @php
            $whSecret = $setting->netsipp_webhook_secret ?? '';
            $webhookUrl = $whSecret !== ''
              ? url('/api/webhooks/netsipp') . '?token=' . urlencode($whSecret)
              : null;
          @endphp
          @if($webhookUrl)
            <div class="alert alert-success">
              <strong>Netsipp’e yapıştırılacak Webhook URL:</strong>
              <div class="input-group mt-2">
                <input type="text" class="form-control" readonly value="{{ $webhookUrl }}" id="netsipp-webhook-url" onclick="this.select()">
              </div>
              <small class="d-block mt-2">
                Netsipp → Ürün Mağazası → Webhook → senaryo: <code>cdr</code> → bu URL.
                Doküman: <a href="https://docs.netsipp.com/api/webhook/cdr" target="_blank" rel="noopener">docs.netsipp.com/api/webhook/cdr</a>
              </small>
            </div>
          @else
            <div class="alert alert-warning">Webhook URL için aşağıdan bir kez <strong>Kaydet</strong> basın (gizli anahtar üretilecek).</div>
          @endif

          <form method="POST" action="{{ route('admin.call-recordings.settings') }}">
            @csrf
            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="netsantral_enabled" name="netsantral_enabled" value="1" {{ ($setting->netsantral_enabled ?? false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="netsantral_enabled">Senkron / ayarları aktif et</label>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Netgsm kullanıcı kodu (opsiyonel)</label>
                  <input type="text" name="netsantral_usercode" class="form-control" value="{{ old('netsantral_usercode', $setting->netsantral_usercode ?? '') }}" placeholder="usercode" autocomplete="off">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Netgsm şifre (opsiyonel)</label>
                  @php
                    $pw = $setting->netsantral_password ?? '';
                    $pwMask = $pw !== '' ? (substr($pw, 0, 2) . '****' . substr($pw, -2)) : '';
                  @endphp
                  <input type="password" name="netsantral_password" class="form-control" value="{{ $pwMask }}" placeholder="Şifre" autocomplete="new-password">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Santral no</label>
                  <input type="text" name="netsantral_pbxnum" class="form-control" value="{{ old('netsantral_pbxnum', $setting->netsantral_pbxnum ?? '') }}" placeholder="850xxxxxxx" autocomplete="off">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Netsipp API key (liste için)</label>
                  @php
                    $ak = $setting->netsipp_api_key ?? '';
                    $akMask = $ak !== '' ? (substr($ak, 0, 4) . '****' . substr($ak, -4)) : '';
                  @endphp
                  <input type="password" name="netsipp_api_key" class="form-control" value="{{ $akMask }}" placeholder="Bearer API key" autocomplete="new-password">
                </div>
              </div>
            </div>
            <div class="form-group">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="regenerate_webhook_secret" name="regenerate_webhook_secret" value="1">
                <label class="custom-control-label" for="regenerate_webhook_secret">Webhook anahtarını yenile (URL değişir)</label>
              </div>
            </div>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save mr-1"></i> Kaydet
            </button>
            <p class="text-muted mt-3 mb-0 small">
              <strong>Ses:</strong> Webhook CDR. <strong>Eski çağrı listesi:</strong> Çek butonu (Netsipp call-details). Geçmiş sesler için Netgsm panelinden indirip satıra yükleyin.
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
          <form method="POST" action="{{ route('admin.call-recordings.import-inbox') }}" class="d-inline ml-2">
            @csrf
            <button type="submit" class="btn btn-outline-secondary mb-2" title="storage/app/netsipp-audio klasöründeki sesleri uniqueid ile eşleştir">
              <i class="fas fa-folder-open mr-1"></i> Klasörden ses bağla
            </button>
          </form>
          <small class="text-muted d-block">Tek seferde en fazla 31 gün. Netsipp ses URL vermez (331); dinlemek için satırdan mp3 yükleyin veya Netgsm FTP → <code>storage/app/netsipp-audio</code>.</small>
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
                    <td style="min-width:280px;">
                      @if($row->hasLocalAudio())
                        <audio controls preload="none" style="max-width:200px;height:32px;">
                          <source src="{{ route('admin.call-recordings.play', $row->id) }}" type="audio/mpeg">
                        </audio>
                      @else
                        <form method="POST" action="{{ route('admin.call-recordings.fetch-audio', $row->id) }}" class="d-inline mb-1">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-warning mb-1">Ses çek</button>
                        </form>
                        <form method="POST" action="{{ route('admin.call-recordings.upload', $row->id) }}" enctype="multipart/form-data" class="mb-0">
                          @csrf
                          <label class="small d-block mb-1">1) mp3 seç → 2) Yükle</label>
                          <input type="file" name="audio" accept=".mp3,.wav,.ogg,.m4a,audio/*" class="form-control-file form-control-sm" required>
                          <button type="submit" class="btn btn-sm btn-success mt-1">Yükle</button>
                        </form>
                      @endif
                    </td>
                    <td class="text-nowrap">
                      @if($row->hasLocalAudio())
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
