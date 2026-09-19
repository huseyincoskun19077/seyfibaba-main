@php
  $colorRows = $colorRows ?? [];
@endphp
<p class="text-muted mb-3 mb-md-3">
  Her renk için ad, isteğe bağlı fiyat, stok ve fotoğraf ekleyin. Tek renkse boş bırakın.
</p>
<div id="simpleColorRows">
  @forelse ($colorRows as $i => $row)
    <div class="color-row">
      <div class="row">
        <div class="form-group col-12 col-md-4 mb-2">
          <label>Renk adı</label>
          <input type="text" name="colors[{{ $i }}][name]" class="form-control" value="{{ $row['name'] ?? '' }}" placeholder="Örn: Siyah">
        </div>
        <div class="form-group col-6 col-md-3 mb-2">
          <label>Fiyat (₺) — aynıysa boş</label>
          <input type="number" step="0.01" min="0" name="colors[{{ $i }}][price]" class="form-control" value="{{ !empty($row['price']) ? $row['price'] : '' }}" placeholder="Boş = ürün fiyatı">
        </div>
        <div class="form-group col-6 col-md-2 mb-2">
          <label>Adet</label>
          <input type="number" min="0" name="colors[{{ $i }}][qty]" class="form-control" value="{{ $row['qty'] ?? '' }}" placeholder="10">
        </div>
        <div class="form-group col-12 col-md-3 mb-2">
          <label>Renk fotoğrafı</label>
          <input type="file" name="colors[{{ $i }}][image]" class="form-control-file color-photo-input" accept="image/jpeg,image/png,image/webp">
          @if (!empty($row['image']))
            <input type="hidden" name="colors[{{ $i }}][keep_image]" value="{{ $row['image'] }}">
            <img class="mt-2" src="{{ asset($row['image']) }}" alt="">
          @endif
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-danger color-remove-btn">Rengi sil</button>
    </div>
  @empty
  @endforelse
</div>
<button type="button" class="btn btn-outline-primary btn-block" id="addColorRowBtn">
  <i class="fas fa-plus mr-1"></i> Renk ekle
</button>

<template id="simpleColorRowTpl">
  <div class="color-row">
    <div class="row">
      <div class="form-group col-12 col-md-4 mb-2">
        <label>Renk adı</label>
        <input type="text" name="colors[__i__][name]" class="form-control" placeholder="Örn: Siyah">
      </div>
      <div class="form-group col-6 col-md-3 mb-2">
        <label>Fiyat (₺) — aynıysa boş</label>
        <input type="number" step="0.01" min="0" name="colors[__i__][price]" class="form-control" placeholder="Boş = ürün fiyatı">
      </div>
      <div class="form-group col-6 col-md-2 mb-2">
        <label>Adet</label>
        <input type="number" min="0" name="colors[__i__][qty]" class="form-control" placeholder="10">
      </div>
      <div class="form-group col-12 col-md-3 mb-2">
        <label>Renk fotoğrafı</label>
        <input type="file" name="colors[__i__][image]" class="form-control-file color-photo-input" accept="image/jpeg,image/png,image/webp">
      </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger color-remove-btn">Rengi sil</button>
  </div>
</template>

<script>
(function () {
  var wrap = document.getElementById('simpleColorRows');
  var btn = document.getElementById('addColorRowBtn');
  var tpl = document.getElementById('simpleColorRowTpl');
  if (!wrap || !btn || !tpl) return;
  var index = wrap.querySelectorAll('.color-row').length;

  function bindRow(row) {
    var removeBtn = row.querySelector('.color-remove-btn');
    if (removeBtn) {
      removeBtn.addEventListener('click', function () { row.remove(); });
    }
    var photo = row.querySelector('.color-photo-input');
    if (photo) {
      photo.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;
        var img = row.querySelector('img');
        if (!img) {
          img = document.createElement('img');
          img.className = 'mt-2';
          this.parentNode.appendChild(img);
        }
        img.src = URL.createObjectURL(this.files[0]);
      });
    }
  }

  wrap.querySelectorAll('.color-row').forEach(bindRow);

  btn.addEventListener('click', function () {
    var html = tpl.innerHTML.replace(/__i__/g, String(index++));
    var holder = document.createElement('div');
    holder.innerHTML = html.trim();
    var row = holder.firstElementChild;
    wrap.appendChild(row);
    bindRow(row);
    var nameInput = row.querySelector('input[type="text"]');
    if (nameInput) nameInput.focus();
  });
})();
</script>
