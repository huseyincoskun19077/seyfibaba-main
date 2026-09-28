/**
 * SSR: artisan :8000 çoğu zaman kapalı. Aynı process :PORT proxy deadlock yapabilir.
 * Sıra: env → :8000 → nginx/admin HTTPS → (son) kendi proxy.
 */
function withTimeout(ms) {
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), ms);
  return { signal: ctrl.signal, cancel: () => clearTimeout(timer) };
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

export function serverApiBases() {
  const bases = [];
  const env = String(process.env.NEXT_SERVER_BASE_URL || "")
    .trim()
    .replace(/\/+$/, "")
    .replace(/\/api$/i, "");
  if (env) bases.push(`${env}/api`);
  bases.push(`http://127.0.0.1:${process.env.BACKEND_PORT || "8000"}/api`);
  bases.push("https://admin.kuafortedarik.com/api");
  bases.push(`http://127.0.0.1:${process.env.PORT || "3001"}/api`);
  return [...new Set(bases)];
}

export async function serverApiGet(path, { timeoutMs = 8000, revalidate } = {}) {
  const rel = String(path || "").replace(/^\//, "");
  let lastError;
  for (const base of serverApiBases()) {
    for (let attempt = 0; attempt < 3; attempt++) {
      const { signal, cancel } = withTimeout(timeoutMs);
      try {
        /** @type {RequestInit & { next?: { revalidate: number } }} */
        const opts = {
          signal,
          // 301→HTTPS aynı kota/IP’ye yığılmasın; başarısız base’i atla
          redirect: "manual",
          headers: {
            Accept: "application/json",
            "User-Agent": "seyfibaba-next-ssr/1.0",
          },
        };
        if (typeof revalidate === "number" && revalidate >= 0) {
          opts.next = { revalidate };
        } else {
          opts.cache = "no-store";
        }
        const res = await fetch(`${base}/${rel}`, opts);
        cancel();

        if (res.status >= 300 && res.status < 400) {
          lastError = new Error(`${res.status} redirect ${base}/${rel}`);
          break; // bu base’i bırak, sonrakine geç
        }

        if (res.status === 429) {
          lastError = new Error(`429 ${base}/${rel}`);
          if (attempt < 2) {
            await sleep(400 * (attempt + 1) * (attempt + 1));
            continue;
          }
          break;
        }

        if (res.ok) return res;
        lastError = new Error(`${res.status} ${base}/${rel}`);
        break;
      } catch (e) {
        cancel();
        lastError = e;
        if (attempt < 2) {
          await sleep(200 * (attempt + 1));
          continue;
        }
      }
    }
  }
  throw lastError || new Error("API unreachable");
}
