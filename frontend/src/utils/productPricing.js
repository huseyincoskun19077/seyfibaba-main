/**
 * Gecerli indirimli fiyat: sifirdan buyuk ve normal fiyattan dusuk olmali.
 * Admin panelde offer_price=0 veya "0" girildiginde indirim gosterilmez.
 */
export function isEffectiveOfferPrice(offerPrice, regularPrice) {
  const offer = Number(offerPrice);
  const regular = Number(regularPrice);
  if (!Number.isFinite(offer) || !Number.isFinite(regular)) return false;
  return offer > 0 && offer < regular;
}

export function normalizeOfferPrice(offerPrice, regularPrice) {
  return isEffectiveOfferPrice(offerPrice, regularPrice) ? Number(offerPrice) : null;
}

export function getSaleUnitQty(product) {
  const qty = Number(
    product?.sale_unit_qty ?? product?.saleUnitQty ?? 1
  );
  return Number.isFinite(qty) && qty > 1 ? Math.floor(qty) : 1;
}

export function isMultiUnitSale(product) {
  return getSaleUnitQty(product) > 1;
}

export function getUnitPrice(totalPrice, saleUnitQty = 1) {
  const units = Number(saleUnitQty) > 1 ? Number(saleUnitQty) : 1;
  const price = Number(totalPrice);
  if (!Number.isFinite(price) || price <= 0 || units <= 0) return null;
  return price / units;
}

export function formatSaleUnitPackLabel(product) {
  const units = getSaleUnitQty(product);
  return units > 1 ? `${units} adet` : null;
}
