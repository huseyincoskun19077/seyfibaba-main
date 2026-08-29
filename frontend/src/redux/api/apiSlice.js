import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { getCookie } from "cookies-next";
import { clearAccessTokenCookie, setAccessTokenCookie } from "@/utils/auth";
import appConfig from "@/appConfig";

/** Header / sepet vb. auth okuması için — yenileme sonrası tetiklenir */
export const AUTH_STORAGE_SYNC_EVENT = "seyfibaba-auth-storage";

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
  if (!exp) return false;
  const now = Math.floor(Date.now() / 1000);
  return exp <= now + skewSeconds;
}

/**
 * Laravel JWT (tymon/jwt-auth) önce Authorization: Bearer okur; yalnızca ?token= kullanmak
 * bazı ortamlarda 401 + global logout tetikleyebilir.
 */
function getClientAccessToken() {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem("auth");
  if (raw && raw !== "null" && raw !== "undefined") {
    try {
      const parsed = JSON.parse(raw);
      if (parsed?.access_token && !isJwtExpired(parsed.access_token)) return parsed.access_token;
    } catch {
      /* ignore */
    }
  }
  const fromCookie = getCookie("access_token");
  if (fromCookie && !isJwtExpired(fromCookie)) return fromCookie;
  return null;
}

/** Giriş / kayıt / OTP gibi uçlarda eski Bearer gönderme (sunucu ve çift oturum sorunları) */
const ENDPOINTS_WITHOUT_BEARER = new Set([
  "userLoginApi",
  "userSignupApi",
  "resendRegisterCodeApi",
  "userForgotApi",
  "userResetApi",
  "sendOtpApi",
  "verifyOtpApi",
  "resendOtpApi",
  "googleGetLoginUrlApi",
  "facebookGetLoginUrlApi",
  "googleCallbackApi",
  "facebookCallbackApi",
]);

/** Bu uçlarda 401 = beklenen hata; oturumu silme / refresh deneme */
const ENDPOINTS_401_NO_SESSION_ACTION = new Set([
  "userLoginApi",
  "userSignupApi",
  "userForgotApi",
  "userResetApi",
  "sendOtpApi",
  "verifyOtpApi",
  "resendOtpApi",
]);

const apiBase = (appConfig.BASE_URL || "https://admin.seyfibaba.com/") + "api/";

let refreshInFlight = null;

function persistRefreshedAccessToken(newAccessToken) {
  if (typeof window === "undefined" || !newAccessToken) return;
  const raw = localStorage.getItem("auth");
  try {
    const parsed = raw && raw !== "null" ? JSON.parse(raw) : {};
    const next = typeof parsed === "object" && parsed && !Array.isArray(parsed) ? { ...parsed } : {};
    next.access_token = newAccessToken;
    localStorage.setItem("auth", JSON.stringify(next));
  } catch {
    localStorage.setItem("auth", JSON.stringify({ access_token: newAccessToken }));
  }
  setAccessTokenCookie(newAccessToken);
  window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
}

async function refreshAccessToken() {
  const token = getClientAccessToken();
  if (!token) return null;
  if (refreshInFlight) return refreshInFlight;
  refreshInFlight = (async () => {
    try {
      const res = await fetch(`${apiBase}user/token/refresh`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      });
      if (!res.ok) return null;
      return await res.json();
    } catch {
      return null;
    } finally {
      refreshInFlight = null;
    }
  })();
  return refreshInFlight;
}

function clearSessionAndRedirectToLogin() {
  if (typeof window === "undefined") return;
  localStorage.removeItem("auth");
  clearAccessTokenCookie();
  window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
  // Ani login redirect UX'i bozuyor (kullanıcı sayfayı kaybediyor).
  // Oturum gerçekten bittiyse, UI zaten token yokluğunu görüp login gerektiren alanlarda yönlendirecek.
  try {
    sessionStorage.setItem("seyfibaba_session_expired", String(Date.now()));
  } catch {
    /* ignore */
  }
}

const baseQuery = fetchBaseQuery({
  baseUrl: apiBase,
  headers: {
    "X-Requested-With": "XMLHttpRequest",
    "X-Client-Platform": "web",
  },
  prepareHeaders: (headers, { endpoint }) => {
    const token = getClientAccessToken();
    if (token && !ENDPOINTS_WITHOUT_BEARER.has(endpoint)) {
      headers.set("Authorization", `Bearer ${token}`);
    }
    return headers;
  },
});

/**
 * 401: önce JWT refresh dene (tymon refresh_ttl içinde), olmazsa oturumu kapat.
 */
const baseQueryWithReauth = async (args, api, extraOptions) => {
  const endpoint = api.endpoint;
  const hadToken = !!getClientAccessToken();
  const alreadyRetried = Boolean(extraOptions?._authRefreshRetried);

  let result = await baseQuery(args, api, extraOptions);

  if (!result.error || result.error.status !== 401 || !hadToken) {
    return result;
  }

  if (ENDPOINTS_401_NO_SESSION_ACTION.has(endpoint)) {
    return result;
  }

  if (!alreadyRetried) {
    const refreshed = await refreshAccessToken();
    if (refreshed?.access_token) {
      persistRefreshedAccessToken(refreshed.access_token);
      result = await baseQuery(args, api, { ...extraOptions, _authRefreshRetried: true });
      if (!result.error) {
        return result;
      }
    }
  }

  if (result.error?.status === 401 && hadToken && !ENDPOINTS_401_NO_SESSION_ACTION.has(endpoint)) {
    // Debug: hangi endpoint 401 ile oturumu düşürüyor?
    try {
      const url = typeof args === "string" ? args : (args?.url || "");
      sessionStorage.setItem(
        "seyfibaba_last_401",
        JSON.stringify({ t: Date.now(), endpoint, url })
      );
    } catch {
      /* ignore */
    }
    clearSessionAndRedirectToLogin();
  }
  return result;
};

// Create our base api slice
export const apiSlice = createApi({
  reducerPath: "api",
  baseQuery: baseQueryWithReauth,
  tagTypes: ["SecondHandVerification", "SecondHandListings", "SecondHandInbox", "SecondHandConversation"],
  endpoints: () => ({}),
});
