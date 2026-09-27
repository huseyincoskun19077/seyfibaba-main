"use client";

import { useEffect } from "react";
import apiRoutes from "@/appConfig/apiRoutes";
import { hasMarketingConsent } from "@/components/Helpers/Consent";

const GUEST_KEY = "kt_guest_view_key";
export const PERSONALIZED_DIRTY_KEY = "kt_size_ozel_dirty";

function getGuestKey() {
  if (typeof window === "undefined") return "";
  try {
    let k = localStorage.getItem(GUEST_KEY);
    if (!k) {
      k = `g_${Math.random().toString(36).slice(2)}_${Date.now()}`;
      localStorage.setItem(GUEST_KEY, k);
    }
    return k;
  } catch {
    return "";
  }
}

/** Ürün görüntüleme kaydı sonrası Size Özel şeridinin yenilenmesi için bayrak. */
export function markPersonalizedDirty() {
  if (typeof window === "undefined") return;
  try {
    sessionStorage.setItem(PERSONALIZED_DIRTY_KEY, String(Date.now()));
  } catch {
    /* ignore */
  }
}

export function consumePersonalizedDirty() {
  if (typeof window === "undefined") return false;
  try {
    const v = sessionStorage.getItem(PERSONALIZED_DIRTY_KEY);
    if (!v) return false;
    sessionStorage.removeItem(PERSONALIZED_DIRTY_KEY);
    return true;
  } catch {
    return false;
  }
}

/** Ürün detayında görüntüleme kaydı (üye veya izinli misafir). */
export default function useProductViewTracker(productId, authToken) {
  useEffect(() => {
    const id = Number(productId);
    if (!id) return;

    (async () => {
      try {
        if (authToken) {
          const res = await fetch(apiRoutes.productView, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${authToken}`,
            },
            body: JSON.stringify({ product_id: id }),
          });
          if (res.ok) markPersonalizedDirty();
          return;
        }
        if (!hasMarketingConsent()) return;
        const guestKey = getGuestKey();
        if (!guestKey) return;
        const res = await fetch(apiRoutes.guestProductView, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            product_id: id,
            guest_key: guestKey,
            consent: true,
          }),
        });
        if (res.ok) markPersonalizedDirty();
      } catch {
        /* ignore */
      }
    })();
  }, [productId, authToken]);
}
