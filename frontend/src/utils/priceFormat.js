/**
 * Kuaför Tedarik tek fiyat formatı (TR).
 * Örnek: ₺1.518,00  |  indirim: -₺62,69
 *
 * Web / mobil / admin / e-posta aynı kuralı kullanmalı.
 */

export const PRICE_SYMBOL = "₺";

/**
 * @param {number|string|null|undefined} value
 * @returns {number}
 */
export function toMoneyNumber(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return 0;
  return Math.round(n * 100) / 100;
}

/**
 * @param {number|string|null|undefined} value
 * @param {{ signed?: boolean }} [opts]
 * @returns {string} e.g. "₺1.518,00" or "-₺62,69"
 */
export function formatMoneyTR(value, opts = {}) {
  const n = toMoneyNumber(value);
  const abs = Math.abs(n);
  const formatted = abs.toLocaleString("tr-TR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  // İndirim tutarı: -₺62,69
  if (opts.forceMinus) {
    return `-${PRICE_SYMBOL}${formatted}`;
  }
  if (n < 0) {
    return `-${PRICE_SYMBOL}${formatted}`;
  }
  if (opts.signed && n > 0) {
    return `+${PRICE_SYMBOL}${formatted}`;
  }
  return `${PRICE_SYMBOL}${formatted}`;
}

/**
 * Liste + satış fiyatından gösterilecek blok.
 * @returns {{
 *   current: number,
 *   list: number|null,
 *   savings: number|null,
 *   hasDiscount: boolean,
 *   currentFormatted: string,
 *   listFormatted: string|null,
 *   savingsFormatted: string|null,
 * }}
 */
export function buildPriceBlock(listPrice, salePrice) {
  const list = toMoneyNumber(listPrice);
  const sale = salePrice != null && salePrice !== "" ? toMoneyNumber(salePrice) : null;
  const hasDiscount =
    sale != null && sale > 0 && list > 0 && sale < list;
  const current = hasDiscount ? sale : list;
  const savings = hasDiscount ? toMoneyNumber(list - current) : null;

  return {
    current,
    list: hasDiscount ? list : null,
    savings,
    hasDiscount,
    currentFormatted: formatMoneyTR(current),
    listFormatted: hasDiscount ? formatMoneyTR(list) : null,
    savingsFormatted:
      hasDiscount && savings > 0
        ? formatMoneyTR(savings, { forceMinus: true })
        : null,
  };
}

export default {
  PRICE_SYMBOL,
  toMoneyNumber,
  formatMoneyTR,
  buildPriceBlock,
};
