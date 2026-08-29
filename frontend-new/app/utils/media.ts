const ABSOLUTE_URL_REGEX = /^https?:\/\//i;

export function normalizeBaseUrl(value: string) {
  return String(value || "").replace(/\/+$/, "");
}

export function resolveMediaUrl(path: string | null | undefined, baseUrl: string) {
  const raw = String(path || "").trim();
  if (!raw) return "";
  if (ABSOLUTE_URL_REGEX.test(raw) || raw.startsWith("data:") || raw.startsWith("blob:")) {
    return raw;
  }

  return `${normalizeBaseUrl(baseUrl)}/${raw.replace(/^\/+/, "")}`;
}

export function isEnabled(value: number | string | null | undefined) {
  return Number(value || 0) === 1;
}
