"use client";
import { MoneyText } from "./PriceDisplay";

/**
 * Satıcı bazlı ücretsiz kargo teşviki (sepetteki satıcı gruplarından).
 */
export default function FreeShippingBar({ sellerGroups = [] }) {
  const pending = (sellerGroups || []).filter(
    (g) => !g.is_free_shipping && g.amount_until_free != null && g.amount_until_free > 0
  );

  if (!pending.length) {
    const anyPaid = (sellerGroups || []).some(
      (g) => !g.is_free_shipping && Number(g.shipping_fee) > 0
    );
    if (!anyPaid && sellerGroups?.length) {
      return (
        <div className="p-3 bg-green-50 border border-green-200 rounded-lg">
          <span className="text-sm font-medium text-green-800">
            Sepetinizdeki satıcılar için kargo ücretsiz veya dahil!
          </span>
        </div>
      );
    }
    return null;
  }

  // En yakın ücretsiz kargo eşiği
  const nearest = pending.reduce((a, b) =>
    Number(a.amount_until_free) <= Number(b.amount_until_free) ? a : b
  );

  return (
    <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
      <div className="flex items-start gap-2">
        <svg
          className="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
          />
        </svg>
        <div className="text-sm text-emerald-900">
          <p className="font-700">
            <span className="font-800">{nearest.shop_name}</span> satıcısında
            kargonuzun bedava olması için{" "}
            <MoneyText
              value={nearest.amount_until_free}
              size="sm"
              className="inline font-800"
            />{" "}
            ürün daha ekleyin.
          </p>
          {pending.length > 1 ? (
            <p className="mt-1 text-[12px] text-emerald-800/80">
              Diğer satıcılar için de ayrı kargo kademeleri geçerlidir.
            </p>
          ) : null}
        </div>
      </div>
    </div>
  );
}
