"use client";

import { useCallback, useEffect, useMemo, useRef } from "react";
import { useDispatch } from "react-redux";
import { toast } from "react-toastify";
import { updateAllItems } from "@/redux/features/cart/cartSlice";
import { refreshCartPrices } from "@/utils/cartPriceRefresh";

function cartFingerprint(cartProducts = []) {
  return (cartProducts || [])
    .map((item) => {
      const id = item.product_id ?? item.product?.id ?? "";
      const qty = item.qty || 1;
      const variants = (item.variants || [])
        .map((v) => v.variant_item_id ?? v.variant_item?.id)
        .filter(Boolean)
        .sort()
        .join(",");
      return `${id}:${qty}:${variants}`;
    })
    .join("|");
}

/**
 * Sepet açıldığında güncel fiyatları sunucudan çeker.
 * Toast yalnızca daha önce bilinen birim fiyat gerçekten değiştiyse gösterilir.
 */
export default function useRefreshCartPrices(
  cartProducts,
  { enabled = true, notify = true } = {}
) {
  const dispatch = useDispatch();
  const seqRef = useRef(0);
  const lastFpRef = useRef("");

  const fingerprint = useMemo(
    () => cartFingerprint(cartProducts),
    [cartProducts]
  );

  const runRefresh = useCallback(async () => {
    if (!enabled || !cartProducts?.length) {
      seqRef.current += 1;
      return;
    }

    const seq = ++seqRef.current;
    // İlk yüklemede unit_price yoksa sessiz senkron; toast için önceki birim fiyat gerekir
    const hadKnownUnitPrice = cartProducts.some(
      (item) => item.unit_price != null && Number.isFinite(Number(item.unit_price))
    );

    try {
      const result = await refreshCartPrices(cartProducts);

      if (seq !== seqRef.current) {
        return;
      }

      const next = result.cartProducts || [];
      if (!next.length) return;

      const meaningfullyChanged = next.some((row) => row.price_changed === true);

      dispatch(updateAllItems(next));

      if (notify && hadKnownUnitPrice && meaningfullyChanged) {
        toast.info("Sepetinizdeki bazı ürünlerin fiyatı güncellendi.");
      }
    } catch {
      // Sessiz: checkout sunucuda yine güncel fiyatla hesaplanır
    }
  }, [cartProducts, dispatch, enabled, notify]);

  useEffect(() => {
    if (!enabled) return;
    if (fingerprint === lastFpRef.current && lastFpRef.current !== "") {
      // Aynı sepet imzası: fiyat refresh'ini tekrar tetikleme (toast döngüsü engeli)
      return;
    }
    lastFpRef.current = fingerprint;
    runRefresh();
  }, [enabled, fingerprint, runRefresh]);

  return { refreshCartPrices: runRefresh };
}
