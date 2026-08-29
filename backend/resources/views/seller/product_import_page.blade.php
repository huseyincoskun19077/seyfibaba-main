@extends('seller.master_layout')
@section('title')
<title>Toplu Ürün Yükleme</title>
@endsection

@section('seller-content')
<style>
  .bi-wrap { max-width: 640px; margin: 0 auto; }
  .bi-card {
    background: #fff; border-radius: 20px;
    box-shadow: 0 8px 40px rgba(15,23,42,.08); overflow: hidden;
  }
  .bi-header {
    background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
    color: #fff; padding: 28px 24px; text-align: center;
  }
  .bi-header h2 { font-size: 1.45rem; font-weight: 700; margin: 0 0 6px; }
  .bi-header p { opacity: .92; margin: 0; font-size: .95rem; }
  .bi-body { padding: 24px; }
  .bi-upload {
    border: 2px dashed #cbd5e1; border-radius: 16px; padding: 36px 20px;
    text-align: center; cursor: pointer; background: #f8fafc; transition: .2s;
  }
  .bi-upload:hover, .bi-upload.dragover { border-color: #10b981; background: #ecfdf5; }
  .bi-upload i { font-size: 2.5rem; color: #10b981; display: block; margin-bottom: 10px; }
  .bi-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border: none; border-radius: 14px; padding: 14px 22px; font-weight: 600;
    font-size: 1rem; cursor: pointer; text-decoration: none !important;
  }
  .bi-btn-primary { background: linear-gradient(135deg, #059669, #10b981); color: #fff !important; }
  .bi-btn-outline { background: #f1f5f9; color: #334155 !important; }
  .bi-rules { background: #f8fafc; border-radius: 14px; padding: 16px 18px; font-size: .9rem; }
  .bi-rules li { margin-bottom: 6px; }
  .bi-file-name { margin-top: 12px; font-weight: 600; color: #059669; }
  .bi-history { margin-top: 24px; }
  .bi-loading-overlay {
    display: none;
    position: fixed; inset: 0; z-index: 99999;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(4px);
    align-items: center; justify-content: center; padding: 20px;
  }
  .bi-loading-overlay.active { display: flex; }
  .bi-loading-box {
    background: #fff; border-radius: 24px; padding: 36px 32px;
    max-width: 420px; width: 100%; text-align: center;
    box-shadow: 0 24px 80px rgba(0,0,0,.25);
  }
  .bi-loading-spinner {
    width: 64px; height: 64px; margin: 0 auto 20px;
    border: 5px solid #d1fae5; border-top-color: #10b981;
    border-radius: 50%; animation: biSpin 0.9s linear infinite;
  }
  @keyframes biSpin { to { transform: rotate(360deg); } }
  .bi-loading-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0 0 8px; }
  .bi-loading-msg { font-size: 1rem; color: #059669; font-weight: 600; margin: 0 0 6px; min-height: 1.5em; }
  .bi-loading-sub { font-size: .88rem; color: #64748b; margin: 0; }
  .bi-loading-progress {
    margin-top: 20px; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden;
  }
  .bi-loading-progress-bar {
    height: 100%; width: 30%; background: linear-gradient(90deg, #059669, #34d399);
    border-radius: 99px; animation: biProgress 2s ease-in-out infinite;
  }
  @keyframes biProgress {
    0% { width: 15%; margin-left: 0; }
    50% { width: 55%; margin-left: 25%; }
    100% { width: 15%; margin-left: 85%; }
  }
  @media (max-width: 576px) {
    .bi-body { padding: 18px; }
    .bi-btn { width: 100%; margin-bottom: 8px; }
  }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header d-none d-md-flex">
      <h1>Toplu Ürün Yükleme</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
        <div class="breadcrumb-item">Excel / CSV Yükle</div>
      </div>
    </div>

    <div class="section-body bi-wrap">
      <div class="bi-card">
        <div class="bi-header">
          <h2><i class="fas fa-file-excel mr-1"></i> Toplu Ürün Yükle</h2>
          <p>Excel veya CSV dosyanızı yükleyin — telefon veya bilgisayardan</p>
        </div>

        <div class="bi-body">
          <div class="bi-rules mb-4">
            <strong><i class="fas fa-info-circle text-success"></i> Kurallar</strong>
            <ul class="mb-0 mt-2 pl-3">
              <li><strong>Esnek başlıklar:</strong> Ürün Adı, name, başlık, Birim Fiyat, Stok, Marka, Resim Url gibi farklı sütun adları otomatik tanınır</li>
              <li>Örnek şablonu indirebilir veya kendi Excel listenizi doğrudan yükleyebilirsiniz</li>
              <li><strong>Kategori</strong> boş bırakılabilir — ürün adına göre AI doğru kategoriye yerleştirir</li>
              <li><strong>Marka</strong> sistemde yoksa otomatik oluşturulur ve «Markalarım» bölümünde görünür</li>
              <li><strong>Resim Url / image_url:</strong> Trendyol CDN (dsmcdn.com) gibi linkler <strong>indirilmeden</strong> doğrudan kullanılır — hızlı yükleme, ürün yayına alınır</li>
              <li>Görsel linki yok veya indirilemez → ürün <strong>taslak</strong> kalır (pasif)</li>
              <li>Desteklenen formatlar: <strong>.xlsx, .xls, .csv</strong></li>
            </ul>
          </div>

          <div class="text-center mb-4 d-flex flex-column flex-sm-row justify-content-center" style="gap:10px;">
            <a href="{{ route('seller.product-bulk-import-sample') }}" class="bi-btn bi-btn-primary">
              <i class="fas fa-file-excel"></i> Örnek Excel İndir
            </a>
            <a href="{{ route('seller.product-bulk-import-template') }}" class="bi-btn bi-btn-outline">
              <i class="fas fa-download"></i> Boş Şablon (CSV)
            </a>
          </div>
          <p class="text-center text-muted small mb-4">
            <strong>Örnek Excel</strong> dosyasında berber koltuğu, kuaför ekipmanı ve makas örnekleri ile <em>Nasıl Kullanılır</em> sekmesi var.<br>
            Kendi ürünlerinizi aynı formatta doldurup yükleyin.
          </p>

          <form action="{{ route('seller.product-import') }}" method="POST" enctype="multipart/form-data" id="biForm">
            @csrf
            <label class="bi-upload" id="biDropzone" for="biFile">
              <i class="fas fa-cloud-upload-alt"></i>
              <strong>Dosya seçin</strong>
              <br><small class="text-muted">Excel (.xlsx) veya CSV — max 10 MB</small>
              <div class="bi-file-name" id="biFileName" style="display:none;"></div>
            </label>
            <input type="file" name="import_file" id="biFile" accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required style="display:none;">

            <div class="text-center mt-4">
              <button type="submit" class="bi-btn bi-btn-primary" id="biSubmit" disabled style="opacity:.5;">
                <i class="fas fa-upload"></i> Excel'den Yükle
              </button>
            </div>
          </form>

          <div class="bi-loading-overlay" id="biLoadingOverlay" aria-live="polite" aria-busy="true">
            <div class="bi-loading-box">
              <div class="bi-loading-spinner"></div>
              <h3 class="bi-loading-title">Yükleniyor</h3>
              <p class="bi-loading-msg" id="biLoadingMsg">Excel dosyanız yükleniyor…</p>
              <p class="bi-loading-sub">Dosya sunucuya yüklendi. İşlem arka planda devam ediyor — sayfayı kapatabilirsiniz; «Son Yüklemeler» tablosundan takip edin.</p>
              <div class="bi-loading-progress"><div class="bi-loading-progress-bar"></div></div>
            </div>
          </div>
        </div>
      </div>

      <div class="text-center mt-3">
        <a href="{{ route('seller.product.index') }}" class="text-muted"><i class="fas fa-list"></i> Ürün listesi</a>
        &nbsp;·&nbsp;
        <a href="{{ route('seller.product.quick-create') }}" class="text-muted"><i class="fas fa-bolt"></i> Hızlı tek ürün ekle</a>
      </div>

      @if(isset($imports) && $imports->count())
        <div class="bi-history">
          <div class="card">
            <div class="card-header"><h4>Son Yüklemeler</h4></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Tarih</th>
                      <th>Dosya</th>
                      <th>Başarılı</th>
                      <th>Hata</th>
                      <th>Durum</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($imports as $imp)
                      <tr>
                        <td>{{ $imp->created_at?->format('d.m.Y H:i') }}</td>
                        <td>{{ Str::limit($imp->original_name, 30) }}</td>
                        <td>{{ $imp->success_count }}</td>
                        <td>{{ $imp->error_count }}</td>
                        <td>
                          @if($imp->status === 'completed')
                            <span class="badge badge-success">Tamamlandı</span>
                          @elseif($imp->status === 'failed')
                            <span class="badge badge-danger">Başarısız</span>
                          @elseif($imp->status === 'processing' || $imp->status === 'pending')
                            <span class="badge badge-warning">İşleniyor</span>
                          @else
                            <span class="badge badge-secondary">{{ $imp->status }}</span>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      @endif
    </div>
  </section>
</div>

<script>
(function () {
  var fileInput = document.getElementById('biFile');
  var fileName = document.getElementById('biFileName');
  var submitBtn = document.getElementById('biSubmit');
  var dropzone = document.getElementById('biDropzone');
  var form = document.getElementById('biForm');
  var overlay = document.getElementById('biLoadingOverlay');
  var loadingMsg = document.getElementById('biLoadingMsg');
  var loadingTimer = null;

  var loadingSteps = [
    'Excel dosyanız yükleniyor…',
    'Sütunlar analiz ediliyor…',
    'Ürünler yükleniyor…',
    'Kategoriler eşleştiriliyor…',
    'Markalar kontrol ediliyor…',
    'Ürünler kaydediliyor, lütfen bekleyin…'
  ];

  if (!fileInput) return;

  function startLoadingMessages() {
    var step = 0;
    loadingMsg.textContent = loadingSteps[0];
    loadingTimer = setInterval(function () {
      step = (step + 1) % loadingSteps.length;
      loadingMsg.textContent = loadingSteps[step];
    }, 2800);
  }

  fileInput.addEventListener('change', function () {
    if (this.files && this.files[0]) {
      fileName.textContent = this.files[0].name;
      fileName.style.display = 'block';
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      submitBtn.innerHTML = '<i class="fas fa-upload"></i> Excel\'den Yükle';
    }
  });

  form.addEventListener('submit', function () {
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.7';
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor…';
    if (overlay) {
      overlay.classList.add('active');
    }
    startLoadingMessages();
  });

  ['dragenter','dragover'].forEach(function (e) {
    dropzone.addEventListener(e, function (ev) { ev.preventDefault(); dropzone.classList.add('dragover'); });
  });
  ['dragleave','drop'].forEach(function (e) {
    dropzone.addEventListener(e, function (ev) { ev.preventDefault(); dropzone.classList.remove('dragover'); });
  });
  dropzone.addEventListener('drop', function (e) {
    var files = e.dataTransfer.files;
    if (files.length) {
      fileInput.files = files;
      fileInput.dispatchEvent(new Event('change'));
    }
  });
})();
</script>
@endsection
