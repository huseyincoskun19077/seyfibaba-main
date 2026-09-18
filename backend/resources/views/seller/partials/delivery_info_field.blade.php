@php
  $deliveryValue = $deliveryInfo ?? old('delivery_info', '');
  $presets = \App\Support\ProductDeliveryInfo::presets();
@endphp
<div class="form-group {{ $wrapperClass ?? 'col-12' }}">
  <label>Kargo / teslimat süresi <small class="text-muted">(Opsiyonel)</small></label>
  <select class="form-control mb-2 js-delivery-preset" data-target="{{ $inputId ?? 'delivery_info' }}">
    <option value="">Hazır seçenek veya kendi metninizi yazın…</option>
    @foreach($presets as $preset)
      <option value="{{ $preset }}" @selected($deliveryValue === $preset)>{{ $preset }}</option>
    @endforeach
  </select>
  <input
    type="text"
    class="form-control"
    name="delivery_info"
    id="{{ $inputId ?? 'delivery_info' }}"
    maxlength="500"
    value="{{ $deliveryValue }}"
    placeholder="Örn: Özel üretim — 7-14 gün içinde siparişe / kargoya verilir"
  >
  <small class="text-muted">
    Zorunlu değil. Müşteri <strong>Teslimat Bilgisi</strong> sekmesinde görür.
    “Özel üretim” içeren seçenekler ileride etiket için kullanılabilir. Boş bırakırsanız standart teslimat metni gösterilir.
  </small>
</div>
@once
<script>
(function () {
  document.addEventListener('change', function (e) {
    var el = e.target;
    if (!el || !el.classList || !el.classList.contains('js-delivery-preset')) return;
    var targetId = el.getAttribute('data-target');
    var input = targetId ? document.getElementById(targetId) : null;
    if (input && el.value) input.value = el.value;
  });
})();
</script>
@endonce
