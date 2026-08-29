@extends('seller.master_layout')
@section('title')
<title>Hızlı Ürün Ekle</title>
@endsection

@section('seller-content')
<style>
  .qp-info { color: #6366f1; cursor: pointer; margin-left: 4px; font-size: .85rem; }
  .qp-info-text { font-size: .82rem; color: #64748b; margin-top: 4px; }
  .qp-ai-btn {
    background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none;
    border-radius: 12px; padding: 10px 20px; font-weight: 600; cursor: pointer;
    font-size: .9rem; transition: all .2s; display: inline-flex; align-items: center; gap: 6px;
  }
  .qp-ai-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,.35); color: #fff; }
  .qp-ai-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }
  .qp-loading-overlay {
    display: none; position: fixed; inset: 0; background: rgba(15,23,42,.75);
    z-index: 9999; align-items: center; justify-content: center; flex-direction: column;
  }
  .qp-loading-overlay.show { display: flex; }
  .qp-loading-box { background: #fff; border-radius: 20px; padding: 36px 32px; text-align: center; max-width: 340px; margin: 16px; }
  .qp-spinner { width: 48px; height: 48px; border: 4px solid #e2e8f0; border-top-color: #6366f1; border-radius: 50%; animation: qpSpin .8s linear infinite; margin: 0 auto 16px; }
  @keyframes qpSpin { to { transform: rotate(360deg); } }
  .qp-wizard { max-width: 720px; margin: 0 auto; padding-bottom: 96px; }
  .qp-progress { display: flex; gap: 4px; margin-bottom: 18px; }
  .qp-progress span { flex: 1; height: 6px; border-radius: 99px; background: #e2e8f0; }
  .qp-progress span.done { background: #6366f1; }
  .qp-step { display: none; }
  .qp-step.active { display: block; }
  .qp-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 20px; }
  .qp-card h4 { font-weight: 700; margin-bottom: 6px; }
  .qp-hint { color: #64748b; font-size: .9rem; margin-bottom: 16px; }
  .qp-field { display: none; margin-bottom: 16px; }
  .qp-field.show { display: block; }
  .qp-nav {
    position: sticky; bottom: 0; background: #fff; border-top: 1px solid #e2e8f0;
    padding: 12px 0; margin-top: 16px; display: flex; gap: 10px; z-index: 20;
  }
  .qp-nav .btn { min-height: 48px; flex: 1; font-weight: 600; }
  .qp-preview-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: .92rem; }
  .qp-preview-row span:first-child { color: #64748b; }
  .qp-preview-img { width: 100%; max-height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 12px; }
  .qp-upload { border: 2px dashed #cbd5e1; border-radius: 16px; padding: 32px 20px; cursor: pointer; display: block; background: #f8fafc; text-align: center; }
  .seller-product-form .form-control, .seller-product-form .form-control-file { min-height: 46px; font-size: 16px; }
  .seller-product-form textarea.form-control { min-height: 96px; }
  @media (max-width: 767px) {
    .section-header { margin-bottom: 12px; }
    .qp-card { padding: 16px; }
    .qp-nav { position: fixed; left: 0; right: 0; bottom: 0; padding: 10px 16px calc(10px + env(safe-area-inset-bottom)); box-shadow: 0 -6px 20px rgba(0,0,0,.08); }
  }
</style>

<div class="main-content seller-product-form">
  <section class="section">
    <div class="section-header">
      <h1><i class="fas fa-bolt mr-1 text-primary"></i> Hızlı Ürün Ekle</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
        <div class="breadcrumb-item">Hızlı Ürün Ekle</div>
      </div>
    </div>

    <div class="section-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif

      @if (session('quick_product_success'))
        <div class="card" style="max-width:520px;margin:0 auto;">
          <div class="card-body text-center py-5">
            <div style="width:72px;height:72px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;color:#16a34a;"><i class="fas fa-check"></i></div>
            <h3 style="font-weight:700;margin-bottom:8px;">Ürün yayında!</h3>
            <p class="text-muted mb-4">{{ session('quick_product_name') }}</p>
            <a href="{{ route('seller.product.quick-create') }}" class="btn btn-primary btn-lg mr-2"><i class="fas fa-plus mr-1"></i> Bir ürün daha ekle</a>
            <a href="{{ route('seller.product.index') }}" class="btn btn-outline-secondary btn-lg">Ürünlerime git</a>
            @if (session('quick_product_id'))
              <div class="mt-3"><a href="{{ route('seller.product.edit', session('quick_product_id')) }}" class="text-muted">Detaylı düzenle →</a></div>
            @endif
          </div>
        </div>
      @else

      <form id="qpForm" class="qp-wizard" action="{{ route('seller.product.quick-store') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-3">
          <a href="{{ route('seller.product.create') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-expand-arrows-alt mr-1"></i> Detaylı form</a>
          <span class="text-muted small" id="qpStepLabel">1 / 9</span>
        </div>
        <div class="qp-progress" id="qpProgress"></div>

        {{-- 0 Ad --}}
        <div class="qp-step active" data-step="0">
          <div class="qp-card">
            <h4>Ürün adı</h4>
            <p class="qp-hint">Adı yazın, sonraki alanlar sırayla açılır.</p>
            <div class="form-group mb-0">
              <label>Ürün Adı <span class="text-danger">*</span></label>
              <input type="text" name="name" id="qpName" class="form-control" value="{{ old('name') }}" placeholder="Profesyonel Erkek Berber Koltuğu — Hidrolik" maxlength="500" autocomplete="off">
              <div class="qp-info-text">Arama sonuçlarında ve ürün sayfasında görünür.</div>
            </div>
          </div>
        </div>

        {{-- 1 Fotoğraf --}}
        <div class="qp-step" data-step="1">
          <div class="qp-card">
            <h4>Kapak fotoğrafı</h4>
            <p class="qp-hint">Bu görsel ürün kartında ve AI açıklamada kullanılır.</p>
            <label class="qp-upload" id="qpDropzone" for="qpPhoto">
              <i class="fas fa-camera" style="font-size:2.5rem;color:#6366f1;display:block;margin-bottom:12px;"></i>
              <strong>Fotoğraf seç</strong> veya sürükleyip bırakın
              <br><small class="text-muted">JPG, PNG, WebP — max 8 MB</small>
            </label>
            <input type="file" name="thumb_image" id="qpPhoto" accept="image/jpeg,image/png,image/webp" style="display:none">
            <div id="qpPreview" style="display:none;margin-top:16px;border-radius:14px;overflow:hidden;">
              <img id="qpPreviewImg" src="" alt="Önizleme" style="width:100%;height:auto;display:block;">
            </div>
          </div>
        </div>

        {{-- 2 Açıklama --}}
        <div class="qp-step" data-step="2">
          <div class="qp-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h4 class="mb-1">Açıklama</h4>
                <p class="qp-hint mb-0">Kısa yazıyı girin, detay alanı ardından açılır. Boş bırakırsanız AI tamamlar.</p>
              </div>
              <button type="button" class="qp-ai-btn" id="qpAiBtn"><i class="fas fa-magic"></i> AI</button>
            </div>
            <div class="form-group qp-field show" id="qpShortWrap">
              <label>Kısa açıklama</label>
              <textarea name="short_description" id="qpShortDesc" rows="3" class="form-control" placeholder="Ürünü bir cümleyle tanıtın">{{ old('short_description') }}</textarea>
            </div>
            <div class="form-group qp-field {{ old('long_description') ? 'show' : '' }}" id="qpLongWrap">
              <label>Detaylı açıklama</label>
              <textarea name="long_description" id="qpLongDesc" rows="6" class="form-control" placeholder="Özellikler, kullanım, içerik">{{ old('long_description') }}</textarea>
            </div>
            <div class="form-group qp-field {{ old('tags') || old('short_name') ? 'show' : '' }}" id="qpTagsWrap">
              <label>Etiketler</label>
              <input type="text" name="tags" id="qpTags" class="form-control" value="{{ old('tags') }}" placeholder="berber, makas, kuaför">
              <label class="mt-3">Kısa ad</label>
              <input type="text" name="short_name" id="qpShortName" class="form-control" value="{{ old('short_name') }}" placeholder="Mobilde görünen kısa ad">
            </div>
          </div>
        </div>

        {{-- 3 Fiyat --}}
        <div class="qp-step" data-step="3">
          <div class="qp-card">
            <h4>Fiyat</h4>
            <div class="alert alert-warning mb-3" style="font-size:.95rem;">
              <strong>Kargo sizin üzerinizde:</strong> Müşteri kargo ücreti ödemez. Kargo bedelini siz ödersiniz; fiyatınızı buna göre yazın.
            </div>
            <p class="qp-hint">Paket kaç adet? Birim fiyat yazınca toplam, toplam yazınca birim otomatik görünür.</p>
            <div class="form-group">
              <label>Paketteki ürün adedi</label>
              <input type="number" name="sale_unit_qty" id="sale_unit_qty" class="form-control" min="1" max="9999" inputmode="numeric" value="{{ old('sale_unit_qty', 1) }}">
              <small class="text-muted">Tek ürünse 1 bırakın. 5’li set ise 5 yazın.</small>
            </div>
            <div class="form-group">
              <label>Birim fiyat (₺ / adet)</label>
              <input type="number" id="qpUnitPrice" class="form-control" step="0.01" min="0" inputmode="decimal" placeholder="Örn: 120">
              <small class="text-muted">Bir adetin fiyatı. Toplam buna göre hesaplanır.</small>
            </div>
            <div class="form-group">
              <label>Toplam satış fiyatı (₺) <span class="text-danger">*</span></label>
              <input type="number" name="price" id="qpPrice" class="form-control" value="{{ old('price') }}" step="0.01" min="0.01" inputmode="decimal">
              <small class="text-muted">Paketin müşteriye görünen fiyatı.</small>
            </div>
            <div class="form-group">
              <label>İndirimli fiyat (₺)</label>
              <input type="number" name="offer_price" id="qpOffer" class="form-control" value="{{ old('offer_price') }}" step="0.01" min="0" inputmode="decimal">
              <small class="text-muted">Varsa toplam fiyattan düşük yazın. Yoksa boş bırakın.</small>
            </div>
            <div class="form-group mb-3">
              <label>Stok (kaç paket) <span class="text-danger">*</span></label>
              <input type="number" name="quantity" id="qpQty" class="form-control" value="{{ old('quantity', 1) }}" min="1" inputmode="numeric">
            </div>
            <div id="qpPriceLinkHint" class="alert alert-light border small mb-3" style="display:none;"></div>
            @include('seller.partials.seller_earnings_preview', ['commissionRate' => $commissionRate ?? 10])
          </div>
        </div>

        {{-- 4 Kategori --}}
        <div class="qp-step" data-step="4">
          <div class="qp-card">
            <h4>Kategori</h4>
            <p class="qp-hint">Ana kategoriyi seçin; alt kategoriler sırayla gelir.</p>
            <div class="form-group">
              <label>Ana kategori <span class="text-danger">*</span></label>
              <select name="category_id" id="qpCategory" class="form-control">
                <option value="">— Kategori seçin —</option>
                @foreach ($categories as $category)
                  <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group qp-field" id="qpSubWrap">
              <label>Alt kategori</label>
              <select name="sub_category_id" id="qpSubCategory" class="form-control">
                <option value="">— Alt kategori seçin —</option>
              </select>
            </div>
            <div class="form-group qp-field" id="qpChildWrap">
              <label>Alt alt kategori</label>
              <select name="child_category_id" id="qpChildCategory" class="form-control">
                <option value="">— Alt alt kategori seçin —</option>
              </select>
            </div>
          </div>
        </div>

        {{-- 5 Marka --}}
        <div class="qp-step" data-step="5">
          <div class="qp-card">
            <h4>Marka</h4>
            <p class="qp-hint">Listeden seçin veya yoksa yeni marka yazın. Atlayabilirsiniz.</p>
            <div class="form-group">
              <label>Marka</label>
              <select name="brand_id" id="qpBrandSelect" class="form-control">
                <option value="">— Marka seçin —</option>
                @foreach ($brands as $brand)
                  <option value="{{ $brand->id }}" {{ (string) old('brand_id') === (string) $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group mb-0">
              <label>Yeni marka adı</label>
              <input type="text" name="brand_name" id="qpBrandName" class="form-control" value="{{ old('brand_name') }}" placeholder="Örn: Wahl, Andis, Moser" maxlength="255">
            </div>
          </div>
        </div>

        {{-- 6 İndirim --}}
        <div class="qp-step" data-step="6">
          <div class="qp-card">
            <h4>Ek bilgiler</h4>
            <p class="qp-hint">SKU, ağırlık ve SEO alanları isteğe bağlıdır.</p>
            <div class="form-group">
              <label>SKU</label>
              <input type="text" name="sku" class="form-control" value="{{ old('sku') }}" placeholder="Stok kodu">
            </div>
            <div class="form-group mb-0">
              <label>Ağırlık (g)</label>
              <input type="number" name="weight" class="form-control" value="{{ old('weight') }}" step="1" min="0" inputmode="numeric">
            </div>
            <div class="form-group mt-3">
              <label>SEO başlığı</label>
              <input type="text" name="seo_title" id="qpSeoTitle" class="form-control" value="{{ old('seo_title') }}">
            </div>
            <div class="form-group mb-0">
              <label>SEO açıklaması</label>
              <textarea name="seo_description" id="qpSeoDesc" rows="2" class="form-control">{{ old('seo_description') }}</textarea>
            </div>
          </div>
        </div>

        {{-- 7 Renk / galeri --}}
        <div class="qp-step" data-step="7">
          @include('seller.partials.simple_color_variants', ['colorRows' => old('colors', [])])
          <div class="qp-card mt-3">
            <h4>Ek görseller</h4>
            <p class="qp-hint">İsterseniz farklı açılardan fotoğraf ekleyin.</p>
            <input type="file" name="gallery_images[]" id="qpGallery" accept="image/jpeg,image/png,image/webp" multiple class="form-control-file">
            <div id="qpGalleryPreview" class="d-flex flex-wrap mt-3" style="gap:8px;"></div>
          </div>
        </div>

        {{-- 8 Önizleme --}}
        <div class="qp-step" data-step="8">
          <div class="qp-card">
            <h4>Önizleme</h4>
            <p class="qp-hint">Yayınlamadan önce ürününüz böyle görünecek. Geri dönüp düzeltebilirsiniz.</p>
            <img id="qpSummaryImg" class="qp-preview-img" alt="" style="display:none;">
            <div id="qpSummary"></div>
          </div>
        </div>

        <div class="qp-nav">
          <button type="button" class="btn btn-outline-secondary" id="qpBack" style="display:none;">Geri</button>
          <button type="button" class="btn btn-outline-secondary" id="qpSkip" style="display:none;">Atla</button>
          <button type="button" class="btn btn-primary" id="qpNext">Devam</button>
          <button type="submit" class="btn btn-primary" id="qpSubmit" style="display:none;"><i class="fas fa-check mr-1"></i> Ürünü Yayınla</button>
        </div>
      </form>
      @endif
    </div>
  </section>
</div>

<div class="qp-loading-overlay" id="qpLoading">
  <div class="qp-loading-box">
    <div class="qp-spinner"></div>
    <h4 style="font-weight:700;margin-bottom:8px;">İşleniyor…</h4>
    <p class="text-muted mb-0" id="qpLoadingText" style="font-size:.9rem;">Ürün kaydediliyor.</p>
  </div>
</div>

<script>
(function() {
  var form = document.getElementById('qpForm');
  if (!form) return;

  var steps = Array.prototype.slice.call(document.querySelectorAll('.qp-step'));
  var total = steps.length;
  var current = 0;
  var skippable = { 2: true, 5: true, 6: true, 7: true };
  var progress = document.getElementById('qpProgress');
  var label = document.getElementById('qpStepLabel');
  var backBtn = document.getElementById('qpBack');
  var nextBtn = document.getElementById('qpNext');
  var skipBtn = document.getElementById('qpSkip');
  var submitBtn = document.getElementById('qpSubmit');

  for (var i = 0; i < total; i++) {
    var bar = document.createElement('span');
    progress.appendChild(bar);
  }

  function money(n) {
    return Number(n || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function text(el) { return (el && el.value ? el.value : '').trim(); }
  function selectedText(sel) {
    if (!sel || !sel.selectedOptions || !sel.selectedOptions[0]) return '';
    return sel.selectedOptions[0].text.trim();
  }

  function go(n) {
    current = Math.max(0, Math.min(total - 1, n));
    steps.forEach(function(s, idx) { s.classList.toggle('active', idx === current); });
    Array.prototype.forEach.call(progress.children, function(bar, idx) {
      bar.classList.toggle('done', idx <= current);
    });
    label.textContent = (current + 1) + ' / ' + total;
    backBtn.style.display = current === 0 ? 'none' : '';
    var last = current === total - 1;
    nextBtn.style.display = last ? 'none' : '';
    submitBtn.style.display = last ? '' : 'none';
    skipBtn.style.display = (!last && skippable[current]) ? '' : 'none';
    if (last) fillPreview();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function validate(step) {
    if (step === 0 && text(document.getElementById('qpName')).length < 2) {
      alert('Ürün adı en az 2 karakter olmalı.'); return false;
    }
    if (step === 1 && !(document.getElementById('qpPhoto').files && document.getElementById('qpPhoto').files[0])) {
      alert('Kapak fotoğrafı gerekli.'); return false;
    }
    if (step === 3) {
      var price = parseFloat(String(document.getElementById('qpPrice').value || '').replace(',', '.')) || 0;
      var qty = parseInt(document.getElementById('qpQty').value, 10) || 0;
      if (price < 0.01) { alert('Toplam satış fiyatı girin.'); return false; }
      if (qty < 1) { alert('Stok adedi girin.'); return false; }
    }
    if (step === 4 && !document.getElementById('qpCategory').value) {
      alert('Ana kategori seçin.'); return false;
    }
    return true;
  }

  function fillPreview() {
    var img = document.getElementById('qpPreviewImg');
    var sumImg = document.getElementById('qpSummaryImg');
    if (img && img.src) { sumImg.src = img.src; sumImg.style.display = 'block'; }
    var units = parseInt(document.getElementById('sale_unit_qty').value, 10) || 1;
    var totalPrice = parseFloat(String(document.getElementById('qpPrice').value || '').replace(',', '.')) || 0;
    var unit = units > 0 ? totalPrice / units : totalPrice;
    var offer = parseFloat(String(document.getElementById('qpOffer').value || '').replace(',', '.')) || 0;
    var rows = [
      ['Ad', text(document.getElementById('qpName'))],
      ['Kısa açıklama', text(document.getElementById('qpShortDesc')) || 'AI tamamlayacak'],
      ['Paket', units + ' adet'],
      ['Birim fiyat', money(unit) + ' ₺'],
      ['Toplam fiyat', money(totalPrice) + ' ₺'],
      ['Stok', (document.getElementById('qpQty').value || '1') + ' paket'],
      ['Kategori', selectedText(document.getElementById('qpCategory'))],
      ['Alt kategori', selectedText(document.getElementById('qpSubCategory'))],
      ['Alt alt kategori', selectedText(document.getElementById('qpChildCategory'))],
      ['Marka', selectedText(document.getElementById('qpBrandSelect')) || text(document.getElementById('qpBrandName')) || '—'],
      ['İndirimli fiyat', offer > 0 ? money(offer) + ' ₺' : 'Yok']
    ];
    document.getElementById('qpSummary').innerHTML = rows.map(function(r) {
      if (!r[1] || r[1].indexOf('—') === 0) return '';
      return '<div class="qp-preview-row"><span>' + r[0] + '</span><strong>' + r[1] + '</strong></div>';
    }).join('');
  }

  nextBtn.addEventListener('click', function() {
    if (!validate(current)) return;
    go(current + 1);
  });
  backBtn.addEventListener('click', function() { go(current - 1); });
  skipBtn.addEventListener('click', function() { go(current + 1); });

  document.getElementById('qpName').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); nextBtn.click(); }
  });

  var shortDesc = document.getElementById('qpShortDesc');
  var longWrap = document.getElementById('qpLongWrap');
  var tagsWrap = document.getElementById('qpTagsWrap');
  shortDesc.addEventListener('input', function() {
    if (this.value.trim().length > 0) longWrap.classList.add('show');
  });
  document.getElementById('qpLongDesc').addEventListener('input', function() {
    if (this.value.trim().length > 0) tagsWrap.classList.add('show');
  });

  var unitEl = document.getElementById('qpUnitPrice');
  var priceEl = document.getElementById('qpPrice');
  var packEl = document.getElementById('sale_unit_qty');
  var hintEl = document.getElementById('qpPriceLinkHint');
  var syncing = false;
  var lastField = 'total';

  function packQty() { return Math.max(1, parseInt(packEl.value, 10) || 1); }
  function parseMoney(v) { return parseFloat(String(v || '').replace(',', '.')) || 0; }
  function updatePriceHint() {
    var units = packQty();
    var totalP = parseMoney(priceEl.value);
    var unitP = parseMoney(unitEl.value);
    if (totalP <= 0 && unitP <= 0) { hintEl.style.display = 'none'; return; }
    hintEl.style.display = 'block';
    hintEl.innerHTML = '<strong>' + units + ' adet</strong> paket · birim <strong>' + money(unitP || (totalP / units)) + ' ₺</strong> · toplam <strong>' + money(totalP || (unitP * units)) + ' ₺</strong>';
  }
  function syncFromUnit() {
    if (syncing) return;
    lastField = 'unit';
    syncing = true;
    var u = parseMoney(unitEl.value);
    priceEl.value = u > 0 ? (u * packQty()).toFixed(2) : '';
    priceEl.dispatchEvent(new Event('input'));
    syncing = false;
    updatePriceHint();
  }
  function syncFromTotal() {
    if (syncing) return;
    lastField = 'total';
    syncing = true;
    var t = parseMoney(priceEl.value);
    var n = packQty();
    unitEl.value = t > 0 ? (t / n).toFixed(2) : '';
    syncing = false;
    updatePriceHint();
  }
  unitEl.addEventListener('input', syncFromUnit);
  priceEl.addEventListener('input', function() { if (!syncing) syncFromTotal(); else updatePriceHint(); });
  packEl.addEventListener('input', function() {
    if (lastField === 'unit') syncFromUnit(); else syncFromTotal();
  });
  if (priceEl.value) syncFromTotal();

  var catSelect = document.getElementById('qpCategory');
  var subSelect = document.getElementById('qpSubCategory');
  var childSelect = document.getElementById('qpChildCategory');
  var subWrap = document.getElementById('qpSubWrap');
  var childWrap = document.getElementById('qpChildWrap');

  function hasOptions(sel) {
    return Array.prototype.some.call(sel.options, function(o) { return o.value; });
  }
  function loadSub(categoryId, selectedId) {
    subWrap.classList.remove('show');
    childWrap.classList.remove('show');
    subSelect.innerHTML = '<option value="">Yükleniyor…</option>';
    childSelect.innerHTML = '<option value="">— Alt alt kategori seçin —</option>';
    if (!categoryId) return;
    fetch("{{ url('/seller/subcategory-by-category') }}/" + categoryId)
      .then(function(r) { return r.json(); })
      .then(function(d) {
        subSelect.innerHTML = d.subCategories || '<option value="">— Alt kategori yok —</option>';
        if (selectedId) subSelect.value = String(selectedId);
        if (hasOptions(subSelect)) subWrap.classList.add('show');
      })
      .catch(function() { subSelect.innerHTML = '<option value="">Yüklenemedi</option>'; });
  }
  function loadChild(subCategoryId, selectedId) {
    childWrap.classList.remove('show');
    childSelect.innerHTML = '<option value="">Yükleniyor…</option>';
    if (!subCategoryId) { childSelect.innerHTML = '<option value="">— Alt alt kategori seçin —</option>'; return; }
    fetch("{{ url('/seller/childcategory-by-subcategory') }}/" + subCategoryId)
      .then(function(r) { return r.json(); })
      .then(function(d) {
        childSelect.innerHTML = d.childCategories || '<option value="">— Alt alt kategori yok —</option>';
        if (selectedId) childSelect.value = String(selectedId);
        if (hasOptions(childSelect)) childWrap.classList.add('show');
      })
      .catch(function() { childSelect.innerHTML = '<option value="">Yüklenemedi</option>'; });
  }
  catSelect.addEventListener('change', function() { loadSub(this.value, ''); });
  subSelect.addEventListener('change', function() { loadChild(this.value, ''); });
  @if (old('category_id'))
    loadSub("{{ old('category_id') }}", "{{ old('sub_category_id') }}");
    @if (old('sub_category_id'))
      setTimeout(function() { loadChild("{{ old('sub_category_id') }}", "{{ old('child_category_id') }}"); }, 400);
    @endif
  @endif

  var brandSelect = document.getElementById('qpBrandSelect');
  var brandName = document.getElementById('qpBrandName');
  if (brandSelect) brandSelect.addEventListener('change', function() { if (this.value) brandName.value = ''; });
  if (brandName) brandName.addEventListener('input', function() { if (this.value.trim()) brandSelect.value = ''; });

  var photoInput = document.getElementById('qpPhoto');
  var preview = document.getElementById('qpPreview');
  var previewImg = document.getElementById('qpPreviewImg');
  var dropzone = document.getElementById('qpDropzone');
  photoInput.addEventListener('change', function() {
    if (this.files && this.files[0]) { previewImg.src = URL.createObjectURL(this.files[0]); preview.style.display = 'block'; }
  });
  ['dragenter','dragover'].forEach(function(ev) { dropzone.addEventListener(ev, function(e) { e.preventDefault(); dropzone.style.borderColor = '#6366f1'; }); });
  ['dragleave','drop'].forEach(function(ev) { dropzone.addEventListener(ev, function(e) { e.preventDefault(); dropzone.style.borderColor = '#cbd5e1'; }); });
  dropzone.addEventListener('drop', function(e) { if (e.dataTransfer.files.length) { photoInput.files = e.dataTransfer.files; photoInput.dispatchEvent(new Event('change')); } });

  document.getElementById('qpGallery').addEventListener('change', function() {
    var container = document.getElementById('qpGalleryPreview');
    container.innerHTML = '';
    Array.from(this.files).forEach(function(f) {
      var img = document.createElement('img');
      img.src = URL.createObjectURL(f);
      img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:8px;';
      container.appendChild(img);
    });
  });

  form.addEventListener('submit', function(e) {
    if (!validate(0) || !validate(1) || !validate(3) || !validate(4)) {
      e.preventDefault();
      if (!validate(0)) go(0);
      else if (!validate(1)) go(1);
      else if (!validate(3)) go(3);
      else go(4);
      return;
    }
    document.getElementById('qpLoadingText').textContent = 'Ürün kaydediliyor' + (text(document.getElementById('qpShortDesc')) ? '.' : ' ve AI açıklama üretiyor…');
    document.getElementById('qpLoading').classList.add('show');
    submitBtn.disabled = true;
  });

  document.getElementById('qpAiBtn').addEventListener('click', function() {
    var name = text(document.getElementById('qpName'));
    var photo = document.getElementById('qpPhoto').files[0];
    if (!name) { alert('Lütfen önce ürün adını girin.'); return; }
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    var formData = new FormData();
    formData.append('name', name);
    formData.append('price', document.getElementById('qpPrice').value || '0');
    formData.append('_token', '{{ csrf_token() }}');
    if (photo) formData.append('thumb_image', photo);
    fetch("{{ route('seller.product.quick-ai-fill') }}", { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.error) { throw new Error(data.error); }
        if (data.short_description) { document.getElementById('qpShortDesc').value = data.short_description; longWrap.classList.add('show'); }
        if (data.long_description) { document.getElementById('qpLongDesc').value = data.long_description; tagsWrap.classList.add('show'); }
        if (data.tags) document.getElementById('qpTags').value = data.tags;
        if (data.short_name) document.getElementById('qpShortName').value = data.short_name;
        if (data.seo_title) document.getElementById('qpSeoTitle').value = data.seo_title;
        if (data.seo_description) document.getElementById('qpSeoDesc').value = data.seo_description;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(function() { btn.innerHTML = '<i class="fas fa-magic"></i> AI'; btn.disabled = false; }, 1600);
      })
      .catch(function() {
        alert('Açıklama şu an doldurulamadı. Kendiniz yazabilir veya daha sonra tekrar deneyebilirsiniz.');
        btn.innerHTML = '<i class="fas fa-magic"></i> AI';
        btn.disabled = false;
      });
  });

  go(0);
})();
</script>
@endsection
