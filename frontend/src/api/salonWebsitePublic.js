import appConfig from "@/appConfig";

function websiteApiBase() {
  if (typeof window !== "undefined") {
    return "/api";
  }
  const fromEnv = String(process.env.NEXT_SERVER_BASE_URL || "").trim();
  if (fromEnv) {
    return fromEnv.replace(/\/+$/, "").replace(/\/api$/i, "") + "/api";
  }
  try {
    const host = new URL(appConfig.BASE_URL).hostname.replace(/^www\./, "");
    if (host.endsWith("seyfibaba.com")) {
      return "https://admin.seyfibaba.com/api";
    }
  } catch {
    /* fallthrough */
  }
  return String(appConfig.BASE_URL || "").replace(/\/+$/, "") + "/api";
}

export async function fetchSalonWebsite(province, district, slug) {
  const p = String(province || "").trim().toLowerCase();
  const d = String(district || "").trim().toLowerCase();
  const s = String(slug || "").trim().toLowerCase();
  if (!/^[a-z0-9\-]+$/.test(p) || !/^[a-z0-9\-]+$/.test(d) || !/^[a-z0-9\-]+$/.test(s)) {
    return null;
  }
  try {
    const res = await fetch(
      `${websiteApiBase()}/user/salon-crm/website/${encodeURIComponent(p)}/${encodeURIComponent(d)}/${encodeURIComponent(s)}`,
      {
        cache: "no-store",
        headers: { Accept: "application/json" },
      }
    );
    if (!res.ok && res.status !== 404) return null;
    return await res.json();
  } catch {
    return null;
  }
}
