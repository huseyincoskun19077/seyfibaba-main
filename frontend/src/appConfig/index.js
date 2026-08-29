const normalizeBaseUrl = (value, fallback) => {
  const raw = (value || fallback || "").trim();
  if (!raw) return "";
  return raw.replace(/\/+$/, "") + "/";
};

const normalizeAppUrl = (value, fallback) => {
  const raw = (value || fallback || "").trim();
  if (!raw) return "";
  return raw.replace(/\/+$/, "");
};

const publicBaseEnv = (() => {
  const raw = process.env.NEXT_PUBLIC_BASE_URL;
  if (
    process.env.NODE_ENV === "production" &&
    /127\.0\.0\.1|localhost/i.test(String(raw || ""))
  ) {
    return "";
  }
  return raw;
})();

const appConfig = {
  // Backend base (Laravel) — always trailing slash
  BASE_URL: normalizeBaseUrl(publicBaseEnv, "https://admin.seyfibaba.com/"),
  PWA_STATUS: process.env.NEXT_PWA_STATUS || "0",
  // Frontend origin — no trailing slash
  APPLICATION_URL: normalizeAppUrl(process.env.NEXT_APPLICATION_URL, "https://seyfibaba.com"),
};

export default appConfig;
