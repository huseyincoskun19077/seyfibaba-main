@php
  $colorRows = $colorRows ?? [];
  $optionGroups = $optionGroups ?? [];
  $openVariants = !empty($colorRows) || !empty($optionGroups);
@endphp
<div class="spf-step" id="simpleVariantsCard">
  <div class="spf-step-head spf-collapse-toggle" role="button" tabindex="0" data-target="#spfVariantsBody" aria-expanded="{{ $openVariants ? 'true' : 'false' }}">
    <span class="spf-step-num">2</span>
    <div class="flex-grow-1">
      <h4>Varyantlar <span class="spf-opt">opsiyonel</span></h4>
      <p>Renk, hacim, boyut, paket… İsterseniz açın; yoksa atlayın.</p>
    </div>
    <i class="fas fa-chevron-down spf-chevron {{ $openVariants ? 'is-open' : '' }}"></i>
  </div>
  <div class="spf-step-body collapse {{ $openVariants ? 'show' : '' }}" id="spfVariantsBody">
    <div class="spf-chips mb-3" id="spfVariantChips">
      <button type="button" class="spf-chip" data-add-color>Renk</button>
      <button type="button" class="spf-chip" data-add-group="Hacim" data-placeholder="Örn: 250 ml, 1 lt">Hacim</button>
      <button type="button" class="spf-chip" data-add-group="Boyut" data-placeholder="Örn: S, M, L / Küçük">Boyut</button>
      <button type="button" class="spf-chip" data-add-group="Paket" data-placeholder="Örn: 5’li, 10’lu">Paket</button>
      <button type="button" class="spf-chip" data-add-group="Tip" data-placeholder="Örn: Soft, Sert">Tip</button>
      <button type="button" class="spf-chip spf-chip-manual" id="spfAddManualGroup">+ Manuel</button>
    </div>

    <div id="spfColorPanel" class="spf-variant-block {{ empty($colorRows) ? 'd-none' : '' }}">
      <div class="spf-variant-block-head">
        <strong>Renk</strong>
        <span class="spf-hint mb-0">Fiyat boşsa ürün fiyatı. Fotoğraf isteğe bağlı.</span>
        <button type="button" class="btn btn-sm btn-link text-danger spf-hide-color ml-auto p-0">Kaldır</button>
      </div>
      @include('seller.partials.simple_color_variants_inner', ['colorRows' => $colorRows])
    </div>

    <div id="spfOptionGroups"></div>
  </div>
</div>

<template id="spfOptionGroupTpl">
  <div class="spf-variant-block" data-group-block>
    <div class="spf-variant-block-head">
      <input type="text" class="form-control form-control-sm spf-group-name" name="option_groups[__gi__][name]" value="__gname__" placeholder="Grup adı" maxlength="80" required>
      <button type="button" class="btn btn-sm btn-link text-danger spf-remove-group p-0">Kaldır</button>
    </div>
    <div class="spf-option-rows" data-rows></div>
    <button type="button" class="btn btn-sm btn-outline-secondary spf-add-option-row">
      <i class="fas fa-plus"></i> Seçenek ekle
    </button>
  </div>
</template>

<template id="spfOptionRowTpl">
  <div class="spf-option-row">
    <input type="text" class="form-control form-control-sm" name="option_groups[__gi__][items][__ii__][name]" value="__iname__" placeholder="__ph__">
    <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="option_groups[__gi__][items][__ii__][price]" value="__iprice__" placeholder="+₺ ek">
    <button type="button" class="btn btn-sm btn-outline-danger spf-remove-option-row" title="Sil">&times;</button>
  </div>
</template>

<script>
(function () {
  var body = document.getElementById('spfVariantsBody');
  var toggle = document.querySelector('#simpleVariantsCard .spf-collapse-toggle');
  var chevron = toggle ? toggle.querySelector('.spf-chevron') : null;
  var colorPanel = document.getElementById('spfColorPanel');
  var groupsWrap = document.getElementById('spfOptionGroups');
  var groupTpl = document.getElementById('spfOptionGroupTpl');
  var rowTpl = document.getElementById('spfOptionRowTpl');
  if (!body || !groupsWrap || !groupTpl || !rowTpl) return;

  var groupIndex = 0;
  var initialGroups = @json($optionGroups);

  function setOpen(open) {
    if (open) {
      body.classList.add('show');
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
      if (chevron) chevron.classList.add('is-open');
    } else {
      body.classList.remove('show');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
      if (chevron) chevron.classList.remove('is-open');
    }
  }

  if (toggle) {
    toggle.addEventListener('click', function () {
      setOpen(!body.classList.contains('show'));
    });
    toggle.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        setOpen(!body.classList.contains('show'));
      }
    });
  }

  function ensureOpen() { setOpen(true); }

  function showColor() {
    ensureOpen();
    if (colorPanel) colorPanel.classList.remove('d-none');
    var addBtn = document.getElementById('addColorRowBtn');
    if (addBtn && colorPanel && colorPanel.querySelectorAll('.color-row').length === 0) {
      addBtn.click();
    }
  }

  function hideColor() {
    if (!colorPanel) return;
    colorPanel.classList.add('d-none');
    colorPanel.querySelectorAll('.color-row').forEach(function (row) { row.remove(); });
  }

  var addColorChip = document.querySelector('[data-add-color]');
  if (addColorChip) addColorChip.addEventListener('click', showColor);
  var hideColorBtn = document.querySelector('.spf-hide-color');
  if (hideColorBtn) hideColorBtn.addEventListener('click', hideColor);

  function bindOptionRow(row) {
    var btn = row.querySelector('.spf-remove-option-row');
    if (btn) btn.addEventListener('click', function () { row.remove(); });
  }

  function addOptionRow(block, name, price, placeholder) {
    var gi = block.getAttribute('data-gi');
    var rows = block.querySelector('[data-rows]');
    var ii = rows.querySelectorAll('.spf-option-row').length;
    var html = rowTpl.innerHTML
      .replace(/__gi__/g, gi)
      .replace(/__ii__/g, String(ii))
      .replace(/__iname__/g, name || '')
      .replace(/__iprice__/g, price !== undefined && price !== null && price !== '' ? String(price) : '')
      .replace(/__ph__/g, placeholder || 'Seçenek adı');
    var holder = document.createElement('div');
    holder.innerHTML = html.trim();
    var row = holder.firstElementChild;
    rows.appendChild(row);
    bindOptionRow(row);
    return row;
  }

  function addGroup(name, rows, placeholder, nameEditable) {
    ensureOpen();
    var existing = groupsWrap.querySelectorAll('[data-group-block]');
    for (var i = 0; i < existing.length; i++) {
      var n = existing[i].querySelector('.spf-group-name');
      if (n && n.value.trim().toLowerCase() === String(name || '').trim().toLowerCase() && name) {
        n.focus();
        return existing[i];
      }
    }

    var gi = String(groupIndex++);
    var html = groupTpl.innerHTML
      .replace(/__gi__/g, gi)
      .replace(/__gname__/g, name || '');
    var holder = document.createElement('div');
    holder.innerHTML = html.trim();
    var block = holder.firstElementChild;
    block.setAttribute('data-gi', gi);
    if (placeholder) block.setAttribute('data-placeholder', placeholder);

    var nameInput = block.querySelector('.spf-group-name');
    if (nameInput && nameEditable === false) {
      nameInput.readOnly = true;
      nameInput.classList.add('spf-group-name-locked');
    }

    block.querySelector('.spf-remove-group').addEventListener('click', function () {
      block.remove();
    });
    block.querySelector('.spf-add-option-row').addEventListener('click', function () {
      var ph = block.getAttribute('data-placeholder') || 'Seçenek adı';
      var row = addOptionRow(block, '', '', ph);
      var inp = row.querySelector('input[type="text"]');
      if (inp) inp.focus();
    });

    groupsWrap.appendChild(block);

    if (rows && rows.length) {
      rows.forEach(function (r) {
        addOptionRow(block, r.name || '', r.price || '', placeholder || 'Seçenek adı');
      });
    } else {
      addOptionRow(block, '', '', placeholder || 'Seçenek adı');
    }

    if (nameEditable !== false && nameInput && !name) {
      nameInput.focus();
    }
    return block;
  }

  document.querySelectorAll('[data-add-group]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      addGroup(btn.getAttribute('data-add-group'), [], btn.getAttribute('data-placeholder') || '', false);
    });
  });

  var manualBtn = document.getElementById('spfAddManualGroup');
  if (manualBtn) {
    manualBtn.addEventListener('click', function () {
      addGroup('', [], 'Örn: Voltaj, Malzeme…', true);
    });
  }

  if (initialGroups && initialGroups.length) {
    initialGroups.forEach(function (g) {
      var preset = ['Hacim', 'Boyut', 'Paket', 'Tip'].indexOf(g.name) !== -1;
      var ph = 'Seçenek adı';
      if (g.name === 'Hacim') ph = 'Örn: 250 ml, 1 lt';
      if (g.name === 'Boyut') ph = 'Örn: S, M, L';
      if (g.name === 'Paket') ph = 'Örn: 5’li, 10’lu';
      if (g.name === 'Tip') ph = 'Örn: Soft, Sert';
      addGroup(g.name || '', g.rows || [], ph, !preset);
    });
  }
})();
</script>
