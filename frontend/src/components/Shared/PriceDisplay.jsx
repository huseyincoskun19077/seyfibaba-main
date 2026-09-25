"use client";

import { buildPriceBlock, formatMoneyTR } from "@/utils/priceFormat";

/**
 * Tek tip fiyat görünümü:
 *   -₺62,69     (indirim tutarı, kırmızı)
 *   ₺1.518,00   (eski fiyat, üstü çizili gri)
 *   ₺1.455,31   (güncel fiyat, koyu kalın)
 *
 * size: "sm" | "md" | "lg"
 * layout: "stack" (dikey, varsayılan) | "inline"
 */
export default function PriceDisplay({
  price,
  offerPrice = null,
  size = "md",
  layout = "stack",
  showSavings = true,
  className = "",
}) {
  const block = buildPriceBlock(price, offerPrice);

  const sizeClass =
    size === "sm" ? "sb-price--sm" : size === "lg" ? "sb-price--lg" : "sb-price--md";
  const layoutClass = layout === "inline" ? "sb-price--inline" : "sb-price--stack";

  return (
    <span
      className={`sb-price ${sizeClass} ${layoutClass} ${className}`.trim()}
      suppressHydrationWarning
    >
      {block.hasDiscount && showSavings && block.savingsFormatted ? (
        <span className="sb-price__savings notranslate">{block.savingsFormatted}</span>
      ) : null}
      {block.hasDiscount && block.listFormatted ? (
        <span className="sb-price__list notranslate">{block.listFormatted}</span>
      ) : null}
      <span className="sb-price__current notranslate">{block.currentFormatted}</span>
    </span>
  );
}

/** Tek tutar (toplam, ara toplam vb.) — aynı format / tipografi */
export function MoneyText({ value, size = "md", className = "", signed = false }) {
  const sizeClass =
    size === "sm" ? "sb-price--sm" : size === "lg" ? "sb-price--lg" : "sb-price--md";
  return (
    <span
      className={`sb-price sb-price--stack ${sizeClass} ${className}`.trim()}
      suppressHydrationWarning
    >
      <span className="sb-price__current notranslate">
        {formatMoneyTR(value, signed ? { forceMinus: Number(value) > 0 } : {})}
      </span>
    </span>
  );
}
