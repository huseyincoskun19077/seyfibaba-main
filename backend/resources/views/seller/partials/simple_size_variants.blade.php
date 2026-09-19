@php
  $sizeRows = $sizeRows ?? [];
@endphp
<div class="spf-step" id="simpleSizesCard">
  <div class="spf-step-head">
    <span class="spf-step-num">3</span>
    <div>
      <h4>Boyut / ek seçenek <span class="spf-opt">opsiyonel</span></h4>
      <p>Fiyat farkı varsa yazın; müşteri yanında +ek ücret görür. Yoksa boş bırakın.</p>
    </div>
  </div>
  <div class="spf-step-body">
    <div id="simpleSizeRows">
      @forelse ($sizeRows as $i => $row)
        <div class="size-row">
          <div class="row">
            <div class="form-group col-12 col-md-6 mb-2">
              <label>Seçenek adı</label>
              <input type="text" name="sizes[{{ $i }}][name]" class="form-control" value="{{ $row['name'] ?? '' }}" placeholder="Örn: Büyük / XL / 5 lt">
            </div>
            <div class="form-group col-12 col-md-4 mb-2">
              <label>Ek fiyat (₺)</label>
              <input type="number" step="0.01" min="0" name="sizes[{{ $i }}][price]" class="form-control" value="{{ $row['price'] ?? '' }}" placeholder="0 = ek yok">
            </div>
            <div class="form-group col-12 col-md-2 mb-2 d-flex align-items-end">
              <button type="button" class="btn btn-sm btn-outline-danger size-remove-btn btn-block">Sil</button>
            </div>
          </div>
        </div>
      @empty
      @endforelse
    </div>
    <button type="button" class="btn btn-outline-secondary btn-block" id="addSizeRowBtn">
      <i class="fas fa-plus mr-1"></i> Boyut / seçenek ekle
    </button>
  </div>
</div>

<template id="simpleSizeRowTpl">
  <div class="size-row">
    <div class="row">
      <div class="form-group col-12 col-md-6 mb-2">
        <label>Seçenek adı</label>
        <input type="text" name="sizes[__i__][name]" class="form-control" placeholder="Örn: Büyük / XL / 5 lt">
      </div>
      <div class="form-group col-12 col-md-4 mb-2">
        <label>Ek fiyat (₺)</label>
        <input type="number" step="0.01" min="0" name="sizes[__i__][price]" class="form-control" placeholder="0 = ek yok">
      </div>
      <div class="form-group col-12 col-md-2 mb-2 d-flex align-items-end">
        <button type="button" class="btn btn-sm btn-outline-danger size-remove-btn btn-block">Sil</button>
      </div>
    </div>
  </div>
</template>

<script>
(function () {
  var wrap = document.getElementById('simpleSizeRows');
  var btn = document.getElementById('addSizeRowBtn');
  var tpl = document.getElementById('simpleSizeRowTpl');
  if (!wrap || !btn || !tpl) return;
  var index = wrap.querySelectorAll('.size-row').length;

  function bindRow(row) {
    var removeBtn = row.querySelector('.size-remove-btn');
    if (removeBtn) {
      removeBtn.addEventListener('click', function () { row.remove(); });
    }
  }

  wrap.querySelectorAll('.size-row').forEach(bindRow);

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
