import { hasAnalyticsConsent } from "@/components/Helpers/Consent";
import { getGa4MeasurementId } from "@/config/googleTags";

function gaClientId() {
  const match = document.cookie.match(/(?:^|;\s*)_ga=GA\d+\.\d+\.(\d+\.\d+)/);
  if (match) return match[1];
  const rand = Math.floor(Math.random() * 1e10);
  return `${rand}.${Math.floor(Date.now() / 1000)}`;
}

/**
 * Özel GA4 olayı. gtag send_to, G- hedefi kurulamadığı için isteği düşürür.
 * Bu çağrı yalnızca google-analytics collect kullanır; AW'ye gitmez.
 * Analitik izni yoksa hiçbir istek atılmaz.
 */
export function sendGa4Collect(eventName) {
  if (typeof window === "undefined") return false;
  if (!hasAnalyticsConsent()) return false;
  const tid = getGa4MeasurementId();
  const name = String(eventName || "").trim();
  if (!tid || !/^[A-Za-z][A-Za-z0-9_]*$/.test(name)) return false;

  const params = new URLSearchParams({
    v: "2",
    tid,
    cid: gaClientId(),
    en: name,
    dl: window.location.href,
    dt: document.title || "",
    ul: navigator.language || "tr-tr",
  });
  const url = `https://www.google-analytics.com/g/collect?${params.toString()}`;
  fetch(url, { method: "GET", mode: "no-cors", keepalive: true }).catch(() => {});
  return true;
}
