/**
 * Fiyat kuralları:
 * - Renk: mutlak satış fiyatı. price <= 0 ise ürün fiyatı (aynı fiyat).
 * - Diğer varyantlar (boyut vb.): ek ücret; ürün/renk fiyatına EKLENİR.
 */

export const COLOR_VARIANT_GROUP = /renk|color/i;

export const parseAmount = (value) => {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }
  if (value == null || value === "") return 0;
  let s = String(value).trim().replace(/[^\d,.\-]/g, "");
  if (!s) return 0;
  // TR: 1.518,00 → 1518.00 | 1518,00 → 1518.00
  if (s.includes(",") && s.includes(".")) {
    s = s.replace(/\./g, "").replace(",", ".");
  } else if (s.includes(",")) {
    s = s.replace(",", ".");
  }
  const n = Number(s);
  return Number.isFinite(n) ? n : 0;
};

export const isColorVariantGroup = (name = "") =>
  COLOR_VARIANT_GROUP.test(String(name || ""));

/** @deprecated use isColorVariantGroup — kept for older imports */
export const isAbsoluteVariantGroup = (name = "") =>
  isColorVariantGroup(name);

export const ABSOLUTE_VARIANT_GROUPS = COLOR_VARIANT_GROUP;

/**
 * @param {object} product
 * @param {Array} selectedVariantItems
 * @param {Array} variants
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

  let colorAbsolute = null;
  let extras = 0;

  (selectedVariantItems || []).forEach((item) => {
    if (!item) return;
    const parent = (variants || []).find(
      (v) => Number(v?.id) === Number(item?.product_variant_id)
    );
    const groupName = String(
      parent?.name || item?.product_variant_name || ""
    );
    const amount = parseAmount(item?.price);

    if (isColorVariantGroup(groupName)) {
      if (amount > 0) {
        colorAbsolute = amount;
      }
      return;
    }

    if (amount > 0) {
      extras += amount;
    }
  });

  if (colorAbsolute !== null) {
    return { price: colorAbsolute + extras, offerPrice: null };
  }

  if (baseOffer != null) {
    return { price: basePrice, offerPrice: baseOffer + extras };
  }

  return {
    price: basePrice + extras,
    offerPrice: null,
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
