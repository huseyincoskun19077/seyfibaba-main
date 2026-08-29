import apiRoutes from "@/appConfig/apiRoutes";
import { notFound } from "next/navigation";

const PUBLIC_REVALIDATE = 120;

export const getSecondHandListingIdFromParam = (value) => {
  const raw = String(value ?? "").trim();
  if (!raw) return null;
  const byTail = raw.match(/(\d+)(?:\/)?$/);
  if (byTail?.[1]) return byTail[1];
  if (/^\d+$/.test(raw)) return raw;
  return null;
};

export const getSecondHandListingSeoPath = (listing) => {
  const id = listing?.id != null ? String(listing.id) : "";
  if (!id) return "";
  const title = String(listing?.title || "")
    .toLowerCase()
    .replaceAll("ı", "i")
    .replaceAll("ğ", "g")
    .replaceAll("ü", "u")
    .replaceAll("ş", "s")
    .replaceAll("ö", "o")
    .replaceAll("ç", "c")
    .replace(/[^a-z0-9\s-]/g, " ")
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");
  return title ? `${title}-${id}` : id;
};

const buildQuery = (query = {}) => {
  const params = new URLSearchParams();
  const keys = [
    "category_id",
    "sub_category_id",
    "child_category_id",
    "city_id",
    "province",
    "district",
    "locality",
    "neighborhood",
    "condition",
    "min_price",
    "max_price",
    "q",
    "page",
    "sort",
  ];
  keys.forEach((key) => {
    const v = query[key];
    if (v !== undefined && v !== null && String(v).trim() !== "") {
      params.set(key, String(v));
    }
  });
  const qs = params.toString();
  return qs ? `?${qs}` : "";
};

/**
 * Aktif ikinci el ilanları (sayfalı).
 * @param {Record<string, string|number|undefined>} query
 */
export async function fetchSecondHandListings(query = {}) {
  try {
    const res = await fetch(`${apiRoutes.secondHandListings}${buildQuery(query)}`, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: PUBLIC_REVALIDATE },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}

/**
 * Ürün ana kategorileri (durum=1) — ikinci el liste filtresi için.
 */
export async function fetchProductCategoriesForFilters() {
  try {
    const res = await fetch(apiRoutes.categoryList, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: 600 },
    });
    if (!res.ok) return { categories: [] };
    return await res.json();
  } catch {
    return { categories: [] };
  }
}

/**
 * Tek ilan detayı (yalnızca yayında).
 * @param {string|number} id
 */
export async function fetchSecondHandListing(id) {
  const listingId = getSecondHandListingIdFromParam(id);
  if (!listingId) return null;
  try {
    const res = await fetch(`${apiRoutes.secondHandListingShow}${listingId}`, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: PUBLIC_REVALIDATE },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}

export async function fetchSecondHandAgreements() {
  const FALLBACK = {
    terms_title: "İkinci El Kullanım Koşulları",
    terms_content: "",
    privacy_title: "İkinci El KVKK / Gizlilik Metni",
    privacy_content: "",
    homepage: {
      title: "Kuaför malzemeleri al/sat",
      subtitle:
        "Doğrulanmış satıcılardan ikinci el ekipman. İlanlara herkes bakabilir; teklif ve mesaj için üye girişi gerekir.",
      cta_primary: "İlan ver",
      cta_secondary: "İlanları gör",
      image: null,
      show_categories: true,
      show_featured: true,
      sliders: [],
    },
  };
  try {
    const res = await fetch(apiRoutes.secondHandAgreements, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: PUBLIC_REVALIDATE },
    });
    if (!res.ok) return FALLBACK;
    return await res.json();
  } catch {
    return FALLBACK;
  }
}
