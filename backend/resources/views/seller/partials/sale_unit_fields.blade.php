<div class="alert alert-warning border mb-3" style="font-size:.95rem;">
  <strong><i class="fas fa-truck mr-1"></i> Kargo sizin üzerinizde:</strong>
  Müşteri kargo ücreti ödemez. Kargo bedelini siz ödersiniz; satış fiyatınızı buna göre belirleyin.
  Birden fazla adet satıyorsanız aşağıya paket adedini yazın; sistem birim fiyatı otomatik hesaplar.
</div>

<div class="form-group col-12 col-md-4">
  <label>
    Satıştaki ürün adedi
    <i class="fas fa-info-circle text-info ml-1" title="Bu fiyat kaç adet ürün içindir? Boş bırakılırsa 1 adet sayılır."></i>
  </label>
  <input
    type="number"
    name="sale_unit_qty"
    id="sale_unit_qty"
    class="form-control"
    min="1"
    max="9999"
    inputmode="numeric"
    value="{{ old('sale_unit_qty', $saleUnitQty ?? 1) }}"
    placeholder="1"
  >
  <small class="text-muted d-block mt-1">Örn: 5 adet Morfose için <strong>5</strong> yazın. Tek ürünse boş bırakın.</small>
</div>

<div class="form-group col-12">
  <div id="sale-unit-preview" class="small text-muted" style="display:none;"></div>
</div>

<script>
(function () {
  var qtyInput = document.getElementById('sale_unit_qty');
  var priceInput = document.querySelector('input[name="price"]');
  var offerInput = document.querySelector('input[name="offer_price"]');
  var preview = document.getElementById('sale-unit-preview');
  if (!qtyInput || !priceInput || !preview) return;

  function formatMoney(value) {
    return Number(value).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updatePreview() {
    var units = parseInt(qtyInput.value, 10) || 1;
    var price = parseFloat(String(priceInput.value || '').replace(',', '.')) || 0;
    var offer = parseFloat(String((offerInput && offerInput.value) || '').replace(',', '.')) || 0;

    if (units < 2 && price <= 0) {
      preview.style.display = 'none';
      preview.textContent = '';
      return;
    }

    var parts = [];
    if (units > 1) {
      parts.push('<strong>' + units + ' adet</strong> paket fiyatı gösterilecek.');
    }
    if (price > 0) {
      parts.push('Birim fiyat: <strong>' + formatMoney(price / units) + ' TL/adet</strong>');
    }
    if (offer > 0 && offer < price) {
      parts.push('İndirimli birim: <strong>' + formatMoney(offer / units) + ' TL/adet</strong>');
    }

    preview.innerHTML = parts.join(' · ');
    preview.style.display = parts.length ? 'block' : 'none';
  }

  ['input', 'change'].forEach(function (ev) {
    qtyInput.addEventListener(ev, updatePreview);
    priceInput.addEventListener(ev, updatePreview);
    if (offerInput) offerInput.addEventListener(ev, updatePreview);
  });
  updatePreview();
})();
</script>
