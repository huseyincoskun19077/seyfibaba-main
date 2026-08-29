import apiRoutes from "@/appConfig/apiRoutes";

const variantUnitPrice = (variants = []) =>
  variants.reduce(
    (sum, variant) =>
      sum + Number(variant?.variant_item?.price || variant?.price || 0),
    0
  );

export const getStoredUnitPrice = (item) => {
  if (!item?.product) return 0;
  const base = Number(item.product.offer_price || item.product.price || 0);
  return base + variantUnitPrice(item.variants);
};

const buildRefreshPayload = (cartProducts = []) =>
  cartProducts.map((item) => ({
    product_id: item.product_id ?? item.product?.id,
    qty: item.qty || 1,
    variant_item_ids: (item.variants || [])
      .map((v) => v.variant_item_id ?? v.variant_item?.id)
      .filter(Boolean),
    previous_unit_price: getStoredUnitPrice(item),
  }));

/**
 * Sepetteki ürün fiyatlarını sunucudan günceller (canlı fiyat).
 */
export async function refreshCartPrices(cartProducts = []) {
  if (!cartProducts?.length) {
    return {
      items: [],
      subtotal: 0,
      has_price_changes: false,
      cartProducts: [],
    };
  }

  const response = await fetch(apiRoutes.cartRefreshPrices, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ items: buildRefreshPayload(cartProducts) }),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data?.message || "Sepet fiyatları güncellenemedi");
  }

  const refreshed = (data.items || [])
    .filter((row) => row.available !== false)
    .map((row) => ({
      product_id: row.product_id,
      qty: row.qty,
      product: row.product,
      variants: row.variants || [],
      totalPrice: row.line_total,
      unit_price: row.unit_price,
      price_changed: row.price_changed,
    }));

  return {
    ...data,
    cartProducts: refreshed,
  };
}
