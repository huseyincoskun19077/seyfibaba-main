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

function isUploadRelativePath(path) {
  const p = String(path || "").replace(/^\/+/, "");
  return p.startsWith("uploads/");
}

/**
 * Yerel uploads görselleri filigranlı proxy üzerinden (indirince de "Kuaför Tedarik" kalır).
 */
function watermarkUrlForUploadPath(pathWithQuery) {
  const raw = String(pathWithQuery || "").trim();
  if (!raw) return "";
  const noHash = raw.split("#")[0];
  const [pathPart] = noHash.split("?");
  const path = pathPart.replace(/^\/+/, "");
  if (!isUploadRelativePath(path)) {
    return joinApiBase(raw.startsWith("/") ? raw : `/${raw}`);
  }
  return joinApiBase(`api/media/wm?path=${encodeURIComponent(path)}`);
}

/**
 * Ürün görseli — yerel yol (uploads/...) veya harici CDN (Trendyol dsmcdn vb.).
 * Idempotent: zaten çözülmüş absolute URL tekrar verilse de bozulmaz.
 * Own-domain /uploads filigranlı /api/media/wm üzerinden sunulur.
 */
export function resolveProductImageUrl(value) {
  const raw = String(value || "").trim();
  if (!raw) return "";

  // Zaten watermark proxy (eski yanlış /media/wm → /api/media/wm)
  if (raw.includes("/media/wm?") || raw.includes("/api/media/wm?")) {
    if (ABSOLUTE_URL_REGEX.test(raw)) {
      return raw.replace(
        /^(https?:\/\/[^/]+)\/media\/wm\?/i,
        "$1/api/media/wm?"
      );
    }
    const cleaned = raw.replace(/^\/+/, "").replace(/^api\//, "");
    return joinApiBase(`api/${cleaned}`);
  }

  if (ABSOLUTE_URL_REGEX.test(raw)) {
    try {
      const parsed = new URL(raw);
      const host = parsed.hostname.replace(/^www\./, "").toLowerCase();
      const path = parsed.pathname || "";

      if (
        path.startsWith("/uploads/") &&
        (OWN_UPLOAD_HOSTS.has(host) ||
          OWN_UPLOAD_HOSTS.has(parsed.hostname.toLowerCase()))
      ) {
        return watermarkUrlForUploadPath(path);
      }
    } catch {
      /* keep absolute */
    }
    return raw;
  }

  if (raw.startsWith("//")) {
    return `https:${raw}`;
  }

  if (raw.startsWith("/uploads/") || isUploadRelativePath(raw)) {
    return watermarkUrlForUploadPath(raw);
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
