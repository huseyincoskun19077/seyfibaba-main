const STORAGE_KEY = "recent_product_searches";
const MAX_ITEMS = 8;

export function loadRecentProductSearches() {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed)
      ? parsed.filter((item) => typeof item === "string" && item.trim())
      : [];
  } catch {
    return [];
  }
}

export function addRecentProductSearch(query) {
  const trimmed = String(query || "").trim();
  if (trimmed.length < 2) return loadRecentProductSearches();
  const next = [
    trimmed,
    ...loadRecentProductSearches().filter(
      (item) => item.toLowerCase() !== trimmed.toLowerCase()
    ),
  ].slice(0, MAX_ITEMS);
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  } catch {
    /* ignore quota */
  }
  return next;
}

export function clearRecentProductSearches() {
  try {
    window.localStorage.removeItem(STORAGE_KEY);
  } catch {
    /* ignore */
  }
  return [];
}
