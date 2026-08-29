/**
 * SSR: artisan :8000 çoğu zaman kapalı. Aynı process :PORT proxy deadlock yapabilir.
 * Sıra: env → :8000 → nginx/admin HTTPS → (son) kendi proxy.
 */
function withTimeout(ms) {
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), ms);
  return { signal: ctrl.signal, cancel: () => clearTimeout(timer) };
}

export function serverApiBases() {
  const bases = [];
  const env = String(process.env.NEXT_SERVER_BASE_URL || "")
    .trim()
    .replace(/\/+$/, "")
    .replace(/\/api$/i, "");
  if (env) bases.push(`${env}/api`);
  bases.push(`http://127.0.0.1:${process.env.BACKEND_PORT || "8000"}/api`);
  bases.push("https://admin.seyfibaba.com/api");
  bases.push(`http://127.0.0.1:${process.env.PORT || "3001"}/api`);
  return [...new Set(bases)];
}

export async function serverApiGet(path, { timeoutMs = 8000 } = {}) {
  const rel = String(path || "").replace(/^\//, "");
  let lastError;
  for (const base of serverApiBases()) {
    const { signal, cancel } = withTimeout(timeoutMs);
    try {
      const res = await fetch(`${base}/${rel}`, {
        signal,
        cache: "no-store",
        headers: { Accept: "application/json" },
      });
      cancel();
      if (res.ok) return res;
      lastError = new Error(`${res.status} ${base}/${rel}`);
    } catch (e) {
      cancel();
      lastError = e;
    }
  }
  throw lastError || new Error("API unreachable");
}
