/**
 * Varyant = aynı ürünün rengi/boyutu (açıklama aynı).
 * Fiyat asla "ürün fiyatı + varyant" olarak toplanmaz.
 * Renk/Boyut vb. seçenek fiyatı varsa o, ürünün satış fiyatıdır.
 */

export const ABSOLUTE_VARIANT_GROUPS =
  /renk|color|boyut|beden|ölçü|olcu|ebat|ölcu|genişlik|genislik|yükseklik|yukseklik/i;

export const parseAmount = (value) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : 0;
};

export const isAbsoluteVariantGroup = (name = "") =>
  ABSOLUTE_VARIANT_GROUPS.test(String(name || ""));

/**
 * @param {object} product
 * @param {Array} selectedVariantItems - active_variant_items benzeri
 * @param {Array} variants - active_variants (id, name, ...)
 * @returns {{ price: number, offerPrice: number|null }}
 */
export function resolveProductUnitPrice(
  product,
  selectedVariantItems = [],
  variants = []
) {
  const basePrice = parseAmount(product?.price);
  const offerRaw = parseAmount(product?.offer_price);
  const baseOffer =
    offerRaw > 0 && (basePrice <= 0 || offerRaw < basePrice) ? offerRaw : null;

  let absolutePrice = null;

  (selectedVariantItems || []).forEach((item) => {
    if (!item) return;
    const parent = (variants || []).find(
      (v) => Number(v?.id) === Number(item?.product_variant_id)
    );
    const groupName = String(
      parent?.name || item?.product_variant_name || ""
    );
    const amount = parseAmount(item?.price);
    if (amount <= 0) return;

    if (isAbsoluteVariantGroup(groupName)) {
      // Renk varsa onu esas al; yoksa ilk mutlak grup fiyatı
      if (/renk|color/i.test(groupName) || absolutePrice === null) {
        absolutePrice = amount;
      }
    }
  });

  if (absolutePrice !== null) {
    return { price: absolutePrice, offerPrice: null };
  }

  return {
    price: basePrice,
    offerPrice: baseOffer,
  };
}

/**
 * Sepet satırı birim fiyatı
 */
export function resolveCartLineUnitPrice(item) {
  if (!item?.product) return 0;

  const selected = [];
  const variantParents = [];

  (item.variants || []).forEach((row) => {
    const vi = row?.variant_item;
    if (!vi) return;
    const parentId = Number(row.variant_id || vi.product_variant_id || 0);
    selected.push({
      product_variant_id: parentId,
      product_variant_name: vi.product_variant_name,
      name: vi.name,
      price: vi.price,
    });
    variantParents.push({
      id: parentId,
      name: vi.product_variant_name || "",
    });
  });

  const { price, offerPrice } = resolveProductUnitPrice(
    item.product,
    selected,
    variantParents
  );
  return offerPrice != null ? offerPrice : price;
}
