import appConfig from "@/appConfig";

const ABSOLUTE_URL_REGEX = /^https?:\/\//i;

const OWN_UPLOAD_HOSTS = new Set([
  "admin.kuafortedarik.com",
  "kuafortedarik.com",
  "www.kuafortedarik.com",
  "admin.seyfibaba.com",
  "seyfibaba.com",
  "www.seyfibaba.com",
  "127.0.0.1",
  "localhost",
]);

function apiBaseUrl() {
  return String(appConfig.BASE_URL || "").replace(/\/?$/, "/");
}

function joinApiBase(pathWithQuery) {
  const cleaned = String(pathWithQuery || "").replace(/^\/+/, "");
  return `${apiBaseUrl()}${cleaned}`;
}

/**
 * Ürün görseli — yerel yol (uploads/...) veya harici CDN (Trendyol dsmcdn vb.).
 * Idempotent: zaten çözülmüş absolute URL tekrar verilse de bozulmaz.
 * Own-domain /uploads asla frontend origin’ine relative bırakılmaz (kartlarda 404).
 */
export function resolveProductImageUrl(value) {
  const raw = String(value || "").trim();
  if (!raw) return "";

  if (ABSOLUTE_URL_REGEX.test(raw)) {
    try {
      const parsed = new URL(raw);
      const host = parsed.hostname.replace(/^www\./, "").toLowerCase();
      const path = parsed.pathname || "";

      // admin/seyfibaba absolute uploads → her zaman API BASE_URL
      if (
        path.startsWith("/uploads/") &&
        (OWN_UPLOAD_HOSTS.has(host) || OWN_UPLOAD_HOSTS.has(parsed.hostname.toLowerCase()))
      ) {
        return joinApiBase(`${path}${parsed.search || ""}`);
      }
    } catch {
      /* keep absolute */
    }
    return raw;
  }

  if (raw.startsWith("//")) {
    return `https:${raw}`;
  }

  // Önceki hatalı resolve sonucu: "/uploads/..." (frontend relative)
  if (raw.startsWith("/uploads/")) {
    return joinApiBase(raw);
  }

  return joinApiBase(raw);
}

export function isExternalProductImage(value) {
  const raw = String(value || "").trim();
  return ABSOLUTE_URL_REGEX.test(raw) || raw.startsWith("//");
}

/**
 * next/image için — harici CDN linklerinde optimizer domain whitelist gerekmez.
 */
export function getProductImageProps(value, fallback = "/assets/images/server-error.png") {
  const src = resolveProductImageUrl(value) || fallback;

  return {
    src,
    unoptimized: true,
  };
}
