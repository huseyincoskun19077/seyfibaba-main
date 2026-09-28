/**
 * Satıcı başvurusu ölçümü — yalnızca sunucu 2xx sonrası çağrılmalı.
 *
 * Kanallar:
 * - GTM yüklüyse: yalnızca dataLayer Custom Event (GTM'de GA4 Event etiketi şart)
 * - GTM yok + gtag varsa: gtag event + send_to yalnız GA4 (G-) — AW'ye gitmez
 * - İkisi de yoksa: dataLayer kuyruğu
 *
 * Dedupe: application_id → sessionStorage + localStorage
 * PII gönderme.
 */

import { getGa4MeasurementId } from "@/config/googleTags";

const STORAGE_KEY = "kt_seller_application_submitted_ids";
const MAX_STORED_IDS = 40;

function readSeenIds() {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed.map(String) : [];
  } catch {
    return [];
  }
}

function markSeen(applicationId) {
  if (typeof window === "undefined") return;
  const id = String(applicationId);
  try {
    window.sessionStorage.setItem(`${STORAGE_KEY}:${id}`, "1");
  } catch {
    // ignore
  }
  try {
    const next = [...readSeenIds().filter((x) => x !== id), id].slice(
      -MAX_STORED_IDS
    );
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  } catch {
    // ignore
  }
}

export function hasTrackedSellerApplication(applicationId) {
  if (applicationId == null || applicationId === "") return false;
  const id = String(applicationId);
  if (typeof window === "undefined") return false;
  try {
    if (window.sessionStorage.getItem(`${STORAGE_KEY}:${id}`) === "1") {
      return true;
    }
  } catch {
    // ignore
  }
  return readSeenIds().includes(id);
}

function hasGtmContainer() {
  return (
    typeof window !== "undefined" &&
    typeof window.google_tag_manager === "object" &&
    window.google_tag_manager !== null
  );
}

/**
 * RTK Query unwrap gövdesinden application_id çıkar.
 * Laravel: { message, data: { application_id, ... } }
 * @param {unknown} response
 * @returns {string|number|null}
 */
export function resolveSellerApplicationId(response) {
  if (!response || typeof response !== "object") return null;
  const nested = response.data;
  if (nested && typeof nested === "object" && nested.application_id != null) {
    return nested.application_id;
  }
  if (response.application_id != null) return response.application_id;
  return null;
}

/**
 * @param {{ applicationId: string|number }} args
 * @returns {{ fired: boolean, channel: string|null, reason?: string }}
 */
export function trackSellerApplicationSubmitted({ applicationId } = {}) {
  if (typeof window === "undefined") {
    return { fired: false, channel: null, reason: "ssr" };
  }
  if (applicationId == null || applicationId === "") {
    return { fired: false, channel: null, reason: "missing_id" };
  }

  const id = String(applicationId);
  if (hasTrackedSellerApplication(id)) {
    return { fired: false, channel: null, reason: "deduped" };
  }

  // Önce işaretle — Strict Mode / çift çağrıda ikinci fire olmasın
  markSeen(id);

  const ga4Id = getGa4MeasurementId();
  const params = { application_id: id };

  try {
    window.dataLayer = window.dataLayer || [];

    // GTM container: Custom Event → GTM içinde tek GA4 Event (seyfibabapp)
    if (hasGtmContainer()) {
      window.dataLayer.push({
        event: "seller_application_submitted",
        ...params,
      });
      return { fired: true, channel: "dataLayer_gtm" };
    }

    if (typeof window.gtag === "function" && ga4Id) {
      // Yalnız doğru G- hedefine — AW- config'e düşmesin
      window.gtag("event", "seller_application_submitted", {
        ...params,
        send_to: ga4Id,
      });
      return { fired: true, channel: "gtag_ga4" };
    }

    // GTM henüz hydrate olmamış olabilir — kuyruk
    window.dataLayer.push({
      event: "seller_application_submitted",
      ...params,
    });
    return { fired: true, channel: "dataLayer_queue" };
  } catch {
    return { fired: false, channel: null, reason: "exception" };
  }
}

/** Test helper — production UI kullanmaz */
export function __resetSellerApplicationTrackingForTests() {
  if (typeof window === "undefined") return;
  try {
    const ids = readSeenIds();
    ids.forEach((id) => {
      try {
        window.sessionStorage.removeItem(`${STORAGE_KEY}:${id}`);
      } catch {
        // ignore
      }
    });
    window.localStorage.removeItem(STORAGE_KEY);
  } catch {
    // ignore
  }
}
