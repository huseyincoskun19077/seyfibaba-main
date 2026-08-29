export const SECOND_HAND_HOST = "ikinciel.seyfibaba.com";
export const MARKETPLACE_HOST = "seyfibaba.com";

export const SECOND_HAND_ORIGIN =
  process.env.NEXT_PUBLIC_SECOND_HAND_ORIGIN || `https://${SECOND_HAND_HOST}`;

export const MARKETPLACE_ORIGIN =
  process.env.NEXT_PUBLIC_MARKETPLACE_ORIGIN || `https://${MARKETPLACE_HOST}`;

export function isSecondHandSubdomainEnabled() {
  return process.env.NEXT_PUBLIC_SECOND_HAND_SUBDOMAIN !== "0";
}

export function secondHandPublicOrigin() {
  return isSecondHandSubdomainEnabled() ? SECOND_HAND_ORIGIN : MARKETPLACE_ORIGIN;
}

export function normalizeHost(host) {
  return String(host || "")
    .split(",")[0]
    .trim()
    .toLowerCase()
    .replace(/:\d+$/, "")
    .replace(/^www\./, "");
}

export function isSecondHandHost(host) {
  return normalizeHost(host) === SECOND_HAND_HOST;
}

export function isMarketplaceHost(host) {
  return normalizeHost(host) === MARKETPLACE_HOST;
}

export function isLocalHost(host) {
  const h = normalizeHost(host);
  return h === "localhost" || h === "127.0.0.1";
}

export function isSecondHandPublicPath(pathname) {
  const p = String(pathname || "/");
  return (
    p === "/ikinci-el" ||
    p.startsWith("/ikinci-el/") ||
    p === "/ikinci-el-sozlesmesi" ||
    p === "/ikinci-el-kvkk"
  );
}

export function secondHandListingUrl(seoPath) {
  const path = String(seoPath || "").replace(/^\//, "");
  if (!isSecondHandSubdomainEnabled()) {
    return `/ikinci-el/${path}`;
  }
  return `${SECOND_HAND_ORIGIN}/ikinci-el/${path}`;
}

export function marketplaceUrl(path = "/") {
  const p = path.startsWith("/") ? path : `/${path}`;
  if (!isSecondHandSubdomainEnabled()) {
    return p;
  }
  return `${MARKETPLACE_ORIGIN}${p}`;
}

export function secondHandPageUrl(path = "/ikinci-el") {
  const p = path.startsWith("/") ? path : `/${path}`;
  if (!isSecondHandSubdomainEnabled()) {
    return p;
  }
  return `${SECOND_HAND_ORIGIN}${p}`;
}

export function marketplaceProfileUrl(hash = "second-hand", search = "") {
  const normalized = String(hash || "second-hand").replace(/^#/, "");
  const qs = !search
    ? ""
    : String(search).startsWith("?")
      ? String(search)
      : `?${search}`;
  if (!isSecondHandSubdomainEnabled()) {
    return `/profile${qs}#${normalized}`;
  }
  return `${MARKETPLACE_ORIGIN}/profile${qs}#${normalized}`;
}
