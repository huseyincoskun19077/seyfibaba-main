export function normalizeProductSlug(slug) {
  const value = String(slug || "")
    .trim()
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/ı/g, "i")
    .replace(/İ/g, "I")
    .replace(/ş/g, "s")
    .replace(/Ş/g, "S")
    .replace(/ğ/g, "g")
    .replace(/Ğ/g, "G")
    .replace(/ü/g, "u")
    .replace(/Ü/g, "U")
    .replace(/ö/g, "o")
    .replace(/Ö/g, "O")
    .replace(/ç/g, "c")
    .replace(/Ç/g, "C")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .replace(/-{2,}/g, "-");

  return value;
}

export function buildProductPath(slug) {
  const normalizedSlug = normalizeProductSlug(slug);

  if (!normalizedSlug) {
    return "/products";
  }

  return `/urun/${encodeURIComponent(normalizedSlug)}`;
}

export function buildProductUrl(baseUrl, slug) {
  const normalizedBaseUrl = String(baseUrl || "").replace(/\/$/, "");
  return `${normalizedBaseUrl}${buildProductPath(slug)}`;
}
