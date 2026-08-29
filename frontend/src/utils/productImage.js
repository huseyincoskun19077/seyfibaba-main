import appConfig from "@/appConfig";

const ABSOLUTE_URL_REGEX = /^https?:\/\//i;

/**
 * Ürün görseli — yerel yol (uploads/...) veya harici CDN (Trendyol dsmcdn vb.).
 */
export function resolveProductImageUrl(value) {
  const raw = String(value || "").trim();
  if (!raw) return "";

  if (ABSOLUTE_URL_REGEX.test(raw)) {
    try {
      const parsed = new URL(raw);
      if (parsed.pathname.startsWith("/uploads/")) {
        const host = parsed.hostname.replace(/^www\./, "").toLowerCase();
        if (
          host === "admin.seyfibaba.com" ||
          host === "seyfibaba.com" ||
          host === "127.0.0.1" ||
          host === "localhost"
        ) {
          return `${parsed.pathname}${parsed.search}`;
        }
      }
    } catch {
      /* keep absolute */
    }
    return raw;
  }

  if (raw.startsWith("//")) {
    return `https:${raw}`;
  }

  return `${appConfig.BASE_URL}${raw.replace(/^\/+/, "")}`;
}

export function isExternalProductImage(value) {
  const raw = String(value || "").trim();
  return ABSOLUTE_URL_REGEX.test(raw) || raw.startsWith("//");
}

/**
 * next/image için — harici CDN linklerinde optimizer devre dışı (domain whitelist gerekmez).
 */
export function getProductImageProps(value, fallback = "/assets/images/server-error.png") {
  const src = resolveProductImageUrl(value) || fallback;

  return {
    src,
    unoptimized: true,
  };
}
