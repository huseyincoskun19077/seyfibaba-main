@extends('seller.master_layout')
@section('title')
<title>Barkod ile Ürün Ekle</title>
@endsection

@section('seller-content')
<style>
  .bc-wrap { max-width: 640px; margin: 0 auto; }
  .bc-card { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.05); padding:20px; }
  .bc-preview { display:none; margin-top:16px; }
  .bc-preview.show { display:block; }
  .bc-preview img { width:120px; height:120px; object-fit:cover; border-radius:12px; }
  .bc-video { width:100%; max-height:280px; background:#0f172a; border-radius:12px; display:none; }
  .bc-video.show { display:block; }
  .seller-product-form .form-control { min-height:46px; font-size:16px; }
</style>

<div class="main-content seller-product-form">
  <section class="section">
    <div class="section-header">
      <h1><i class="fas fa-barcode mr-1 text-primary"></i> Barkod ile Ürün Ekle</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
        <div class="breadcrumb-item">Barkod ile Ekle</div>
      </div>
    </div>

    <div class="section-body bc-wrap">
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif

      @if (session('barcode_product_success'))
        <div class="bc-card text-center py-5 mb-4">
          <div style="width:72px;height:72px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;color:#16a34a;"><i class="fas fa-check"></i></div>
          <h3 style="font-weight:700;">Ürün eklendi</h3>
          <p class="text-muted">{{ session('barcode_product_name') }}</p>
          <a href="{{ route('seller.product.barcode-create') }}" class="btn btn-primary mr-2">Bir barkod daha</a>
          <a href="{{ route('seller.product.index') }}" class="btn btn-outline-secondary">Ürünlerim</a>
          @if (session('barcode_product_id'))
            <div class="mt-3"><a href="{{ route('seller.product.edit', session('barcode_product_id')) }}">Detaylı düzenle →</a></div>
          @endif
        </div>
      @endif

      <div class="bc-card">
        <p class="text-muted mb-3">
          Barkodu okutun veya yazın. Katalogdaki isim, açıklama, kategori ve görsel otomatik gelir.
          Siz sadece <strong>fiyat</strong>, <strong>stok</strong> ve isteğe bağlı <strong>indirimli fiyat</strong> girersiniz.
          Katalog kalıcıdır; ürünü silseniz bile barkod kaydı sistemde kalır.
        </p>

        <div class="form-group">
          <label>Barkod</label>
          <div class="input-group">
            <input type="text" id="bcInput" class="form-control" inputmode="numeric" autocomplete="off" placeholder="Örn. 4045787020083" value="{{ old('barcode') }}">
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-primary" id="bcLookupBtn">Bul</button>
              <button type="button" class="btn btn-outline-secondary" id="bcScanBtn" title="Kamera"><i class="fas fa-camera"></i></button>
            </div>
          </div>
          <small id="bcMsg" class="form-text text-muted"></small>
        </div>

        <video id="bcVideo" class="bc-video mb-3" playsinline></video>

        <div id="bcPreview" class="bc-preview">
          <div class="d-flex align-items-start mb-3">
            <img id="bcThumb" src="" alt="">
            <div class="ml-3">
              <h5 id="bcName" class="mb-1" style="font-weight:700;"></h5>
              <div class="text-muted small" id="bcMeta"></div>
              <div class="text-muted small mt-1" id="bcOwned"></div>
            </div>
          </div>

          <form method="post" action="{{ route('seller.product.barcode-store') }}" id="bcForm">
            @csrf
            <input type="hidden" name="barcode" id="bcHidden" value="">
            <div class="form-row">
              <div class="form-group col-md-4">
                <label>Satış fiyatı (₺) *</label>
                <input type="number" step="0.01" min="0.01" name="price" class="form-control" required value="{{ old('price') }}">
              </div>
              <div class="form-group col-md-4">
                <label>Stok *</label>
                <input type="number" min="0" name="quantity" class="form-control" required value="{{ old('quantity', 1) }}">
              </div>
              <div class="form-group col-md-4">
                <label>İndirimli fiyat</label>
                <input type="number" step="0.01" min="0" name="offer_price" class="form-control" value="{{ old('offer_price') }}" placeholder="Opsiyonel">
              </div>
            </div>
            <p class="small text-muted">Komisyon oranı ≈ %{{ number_format((float)($commissionRate ?? 10), 1) }}</p>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Ürünü yayınla</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
(function () {
  const lookupUrl = @json(route('seller.product.barcode-lookup'));
  const input = document.getElementById('bcInput');
  const msg = document.getElementById('bcMsg');
  const preview = document.getElementById('bcPreview');
  const video = document.getElementById('bcVideo');
  let stream = null;
  let scanning = false;

  function setMsg(text, isError) {
    msg.textContent = text || '';
    msg.className = 'form-text ' + (isError ? 'text-danger' : 'text-muted');
  }

  async function lookup() {
    const code = (input.value || '').replace(/\s+/g, '');
    if (!code) { setMsg('Barkod girin.', true); return; }
    setMsg('Aranıyor…');
    preview.classList.remove('show');
    try {
      const res = await fetch(lookupUrl + '?barcode=' + encodeURIComponent(code), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (!data.ok) {
        setMsg(data.message || 'Bulunamadı', true);
        return;
      }
      const c = data.catalog;
      document.getElementById('bcHidden').value = c.barcode;
      document.getElementById('bcName').textContent = c.name || c.barcode;
      document.getElementById('bcMeta').textContent = [c.category, c.sub_category, c.child_category].filter(Boolean).join(' › ') || 'Kategori yok';
      const thumb = document.getElementById('bcThumb');
      thumb.src = c.thumb_url || '';
      thumb.style.display = c.has_image ? 'block' : 'none';
      document.getElementById('bcOwned').textContent = data.already_owned
        ? ('Bu barkod ürünlerinizde zaten var: ' + data.already_owned.name + ' (yine de ekleyebilirsiniz)')
        : '';
      preview.classList.add('show');
      setMsg('Katalog bulundu. Fiyat ve stok girin.');
      stopScan();
    } catch (e) {
      setMsg('Arama başarısız.', true);
    }
  }

  document.getElementById('bcLookupBtn').addEventListener('click', lookup);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); lookup(); }
  });

  async function stopScan() {
    scanning = false;
    video.classList.remove('show');
    if (stream) {
      stream.getTracks().forEach(t => t.stop());
      stream = null;
    }
  }

  document.getElementById('bcScanBtn').addEventListener('click', async function () {
    if (scanning) { stopScan(); return; }
    if (!('BarcodeDetector' in window)) {
      setMsg('Bu tarayıcı kamera barkod okumayı desteklemiyor. Barkodu yazın.', true);
      input.focus();
      return;
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
      video.srcObject = stream;
      video.classList.add('show');
      await video.play();
      scanning = true;
      setMsg('Barkodu kameraya gösterin…');
      const detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128'] });
      const tick = async () => {
        if (!scanning) return;
        try {
          const codes = await detector.detect(video);
          if (codes && codes[0] && codes[0].rawValue) {
            input.value = codes[0].rawValue;
            await lookup();
            return;
          }
        } catch (_) {}
        requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    } catch (e) {
      setMsg('Kameraya izin verilmedi veya açılamadı.', true);
    }
  });
})();
</script>
@endsection
