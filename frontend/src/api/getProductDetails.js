import { notFound } from "next/navigation";
import { serverApiGet } from "@/utils/serverApiFetch";

function isNextNotFound(err) {
  return (
    err?.digest === "NEXT_NOT_FOUND" ||
    (typeof err?.message === "string" && err.message.includes("NEXT_HTTP_ERROR_FALLBACK;404"))
  );
}

/**
 * Ürün detayı — geçici API/timeout/429 hatalarında 404 gösterme; birkaç kez dene.
 */
export default async function getProductDetails(slug) {
  let lastError;

  for (let attempt = 0; attempt < 4; attempt++) {
    try {
      const res = await serverApiGet(`product/${encodeURIComponent(slug)}`, {
        timeoutMs: 12000,
      });

      if (res.status === 404) {
        notFound();
      }

      const data = await res.json();
      if (!data?.product?.id) {
        notFound();
      }
      return data;
    } catch (err) {
      if (isNextNotFound(err)) {
        throw err;
      }
      lastError = err;
      if (attempt < 3) {
        const msg = String(err?.message || "");
        const is429 = msg.includes("429");
        await new Promise((r) =>
          setTimeout(r, is429 ? 600 * (attempt + 1) : 250 * (attempt + 1))
        );
      }
    }
  }

  // Ağ/API düşmüşse sahte 404 yerine hata fırlat (error boundary / yeniden dene)
  throw lastError || new Error("Ürün API yanıt vermedi");
}
