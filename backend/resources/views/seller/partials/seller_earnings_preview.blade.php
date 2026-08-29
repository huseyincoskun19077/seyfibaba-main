@php
  $commissionRate = (float) ($commissionRate ?? 10);
  if ($commissionRate <= 0) {
      $commissionRate = 10;
  }
@endphp
<div id="seller-net-preview" class="col-12" data-rate="{{ $commissionRate }}">
  <div class="alert mb-0" style="background:#f8fafc;border:1px solid #e2e8f0;">
    <div class="d-flex flex-wrap justify-content-between" style="gap:8px;">
      <div>
        <div class="text-muted small">Platform komisyonu (%{{ rtrim(rtrim(number_format($commissionRate, 2, ',', '.'), '0'), ',') }})</div>
        <div class="font-weight-bold" id="snpCommission">0,00 TL</div>
      </div>
      <div>
        <div class="text-muted small">Kargo hariç net kalan</div>
        <div class="font-weight-bold text-success" id="snpNet">0,00 TL</div>
      </div>
    </div>
    <small class="d-block mt-2 text-muted">Kargo satıcıya aittir; bu tutardan düşülmez. Iyzico payı da platform komisyonunun içinden karşılanır.</small>
  </div>
</div>
<script>
(function () {
  var box = document.getElementById('seller-net-preview');
  if (!box) return;
  var rate = parseFloat(box.getAttribute('data-rate')) || 10;
  var priceInput = document.querySelector('input[name="price"]');
  var offerInput = document.querySelector('input[name="offer_price"]');
  var commissionEl = document.getElementById('snpCommission');
  var netEl = document.getElementById('snpNet');

  function parseMoney(value) {
    return parseFloat(String(value || '').replace(',', '.')) || 0;
  }
  function formatMoney(value) {
    return Number(value).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TL';
  }
  function effectivePrice() {
    var price = parseMoney(priceInput && priceInput.value);
    var offer = parseMoney(offerInput && offerInput.value);
    if (offer > 0 && offer < price) return offer;
    return price;
  }
  function update() {
    var sale = effectivePrice();
    var commission = sale * (rate / 100);
    var net = sale - commission;
    if (commissionEl) commissionEl.textContent = formatMoney(commission);
    if (netEl) netEl.textContent = formatMoney(net);
  }
  ['input', 'change'].forEach(function (ev) {
    if (priceInput) priceInput.addEventListener(ev, update);
    if (offerInput) offerInput.addEventListener(ev, update);
  });
  update();
})();
</script>
