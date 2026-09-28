const SEND_TO = "AW-18452604369/W7n7CLmU3YkdENHL8d5E";
const STORAGE_KEY = "kt_seller_ads_conversion_ids";
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

function hasSeen(applicationId) {
  const id = String(applicationId);
  try {
    if (window.sessionStorage.getItem(`${STORAGE_KEY}:${id}`) === "1") return true;
  } catch {
    // ignore
  }
  return readSeenIds().includes(id);
}

function markSeen(applicationId) {
  const id = String(applicationId);
  try {
    window.sessionStorage.setItem(`${STORAGE_KEY}:${id}`, "1");
  } catch {
    // ignore
  }
  try {
    const next = [...readSeenIds().filter((item) => item !== id), id].slice(-MAX_STORED_IDS);
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  } catch {
    // ignore
  }
}

/**
 * Mevcut AW etiketi üzerinden satıcı başvurusu dönüşümü.
 * Yalnız 201 + application_id ve pazarlama izni varken çağrılmalı.
 * @param {{ applicationId: string|number, marketingGranted?: boolean }} args
 */
export function trackSellerAdsConversion({ applicationId, marketingGranted = false } = {}) {
  if (typeof window === "undefined") {
    return { fired: false, reason: "ssr" };
  }
  if (!marketingGranted) {
    return { fired: false, reason: "marketing_denied" };
  }
  if (applicationId == null || applicationId === "") {
    return { fired: false, reason: "missing_id" };
  }
  const transactionId = String(applicationId);
  if (hasSeen(transactionId)) {
    return { fired: false, reason: "deduped" };
  }
  if (typeof window.gtag !== "function") {
    return { fired: false, reason: "no_gtag" };
  }

  markSeen(transactionId);
  window.gtag("event", "conversion", {
    send_to: SEND_TO,
    transaction_id: transactionId,
  });
  return { fired: true, reason: "gtag" };
}

export function __resetSellerAdsConversionForTests() {
  if (typeof window === "undefined") return;
  try {
    readSeenIds().forEach((id) => {
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
