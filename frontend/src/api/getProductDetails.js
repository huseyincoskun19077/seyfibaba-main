import { notFound } from "next/navigation";
import { serverApiGet } from "@/utils/serverApiFetch";

function isNextNotFound(err) {
  return (
    err?.digest === "NEXT_NOT_FOUND" ||
    (typeof err?.message === "string" && err.message.includes("NEXT_HTTP_ERROR_FALLBACK;404"))
  );
}

/**
 * Ürün detayı — geçici API/timeout hatalarında 404 gösterme; birkaç kez dene.
 */
export default async function getProductDetails(slug) {
  let lastError;

  for (let attempt = 0; attempt < 3; attempt++) {
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
      if (attempt < 2) {
        await new Promise((r) => setTimeout(r, 250 * (attempt + 1)));
      }
    }
  }

  // Ağ/API düşmüşse sahte 404 yerine hata fırlat (error boundary / yeniden dene)
  throw lastError || new Error("Ürün API yanıt vermedi");
}
