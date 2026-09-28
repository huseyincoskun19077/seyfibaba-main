/**
 * Kuaför Tedarik Google etiket kimlikleri.
 * Öncelik: Admin GA4 alanı → window (layout) → NEXT_PUBLIC_ → kod fallback.
 */

/** Production GA4 web data stream (mülk adı: seyfibabapp) — admin boşsa */
export const GA4_MEASUREMENT_ID_DEFAULT = "G-2ZL87131XC";

export function normalizeGa4Id(value) {
  const id = String(value || "").trim();
  return /^G-[A-Z0-9]+$/i.test(id) ? id.toUpperCase() : null;
}

/**
 * @param {string|null|undefined} override Admin / setup'tan gelen G-
 */
export function getGa4MeasurementId(override) {
  const fromOverride = normalizeGa4Id(override);
  if (fromOverride) return fromOverride;

  if (typeof window !== "undefined") {
    const fromWindow = normalizeGa4Id(window.__KT_GA4_MEASUREMENT_ID);
    if (fromWindow) return fromWindow;
  }

  const fromEnv = normalizeGa4Id(process.env.NEXT_PUBLIC_GA4_MEASUREMENT_ID);
  if (fromEnv) return fromEnv;

  return normalizeGa4Id(GA4_MEASUREMENT_ID_DEFAULT);
}

export function isGoogleAdsId(id) {
  return /^AW-[0-9]+$/i.test(String(id || "").trim());
}

export function isGa4Id(id) {
  return /^G-[A-Z0-9]+$/i.test(String(id || "").trim());
}

export function isGtmId(id) {
  return /^GTM-[A-Z0-9]+$/i.test(String(id || "").trim());
}
