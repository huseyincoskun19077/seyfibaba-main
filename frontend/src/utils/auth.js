import { deleteCookie, getCookie, setCookie } from "cookies-next";

function parseJwtExp(token) {
  try {
    const parts = String(token || "").split(".");
    if (parts.length < 2) return null;
    const payload = parts[1];
    const base = payload.replace(/-/g, "+").replace(/_/g, "/");
    const json = decodeURIComponent(
      atob(base)
        .split("")
        .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
        .join("")
    );
    const obj = JSON.parse(json);
    return typeof obj?.exp === "number" ? obj.exp : null;
  } catch {
    return null;
  }
}

function isJwtExpired(token, skewSeconds = 30) {
  const exp = parseJwtExp(token);
  if (!exp) return false; // exp yoksa expired sayma
  const now = Math.floor(Date.now() / 1000);
  return exp <= now + skewSeconds;
}

/**
 * Geçerli oturum = JWT (access_token) localStorage veya cookie'de olmalı.
 * Sadece user objesi kalmış, token eksik kalmış eski kayıtlar için cookie ile birleştirilir.
 */
export default function auth() {
  if (typeof window === "undefined") return false;

  const cookieToken = getCookie("access_token");
  const raw = localStorage.getItem("auth");

  if (raw && raw !== "null" && raw !== "undefined") {
    try {
      const parsed = JSON.parse(raw);
      if (
        parsed &&
        typeof parsed === "object" &&
        !Array.isArray(parsed)
      ) {
        const token = parsed.access_token || cookieToken;
        if (token && !isJwtExpired(token)) {
          return { ...parsed, access_token: token };
        }
      }
    } catch {
      /* ignore */
    }
  }

  if (cookieToken && !isJwtExpired(cookieToken)) {
    return { access_token: cookieToken };
  }

  return false;
}

function cookieDomain() {
  if (typeof window === "undefined") {
    return process.env.NEXT_PUBLIC_COOKIE_DOMAIN || ".seyfibaba.com";
  }
  const host = window.location.hostname.replace(/^www\./, "");
  if (host === "localhost" || host === "127.0.0.1") return undefined;
  if (host.endsWith("seyfibaba.com")) return ".seyfibaba.com";
  return undefined;
}

export function authCookieOptions() {
  const domain = cookieDomain();
  const secure =
    typeof window !== "undefined"
      ? window.location.protocol === "https:"
      : process.env.NODE_ENV === "production";
  return {
    path: "/",
    sameSite: "lax",
    secure,
    ...(domain ? { domain } : {}),
  };
}

export function setAccessTokenCookie(token) {
  if (!token) return;
  deleteCookie("access_token", { path: "/" });
  deleteCookie("access_token", { path: "/", domain: ".seyfibaba.com" });
  setCookie("access_token", token, {
    ...authCookieOptions(),
    maxAge: 60 * 60 * 24 * 30,
  });
}

export function clearAccessTokenCookie() {
  deleteCookie("access_token", { path: "/" });
  deleteCookie("access_token", { path: "/", domain: ".seyfibaba.com" });
}

export function safePostLoginRedirect(raw) {
  const value = String(raw || "").trim();
  if (!value) return "";
  try {
    const base =
      typeof window !== "undefined"
        ? window.location.origin
        : "https://seyfibaba.com";
    const url = new URL(value, base);
    const host = url.hostname.replace(/^www\./, "");
    if (host === "localhost" || host === "127.0.0.1") {
      if (url.protocol !== "https:" && url.protocol !== "http:") return "";
      return url.toString();
    }
    if (host !== "seyfibaba.com" && host !== "ikinciel.seyfibaba.com") {
      return "";
    }
    if (url.protocol !== "https:" && url.protocol !== "http:") return "";
    return url.toString();
  } catch {
    return "";
  }
}

export function marketplaceLoginHref() {
  const next =
    typeof window !== "undefined" ? window.location.href : "";
  const safe = safePostLoginRedirect(next);
  const login = "/login";
  if (!safe) return login;
  return `${login}?next=${encodeURIComponent(safe)}`;
}
