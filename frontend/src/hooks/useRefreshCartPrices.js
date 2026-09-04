"use client";

import { useCallback, useEffect, useRef } from "react";
import { useDispatch } from "react-redux";
import { toast } from "react-toastify";
import { updateAllItems } from "@/redux/features/cart/cartSlice";
import { refreshCartPrices } from "@/utils/cartPriceRefresh";

/**
 * Sepet açıldığında güncel fiyatları sunucudan çeker (Trendyol: canlı fiyat).
 * Silme/qty değişiminde eski isteklerin sepeti geri yazmasını engeller.
 */
export default function useRefreshCartPrices(cartProducts, { enabled = true, notify = true } = {}) {
  const dispatch = useDispatch();
  const seqRef = useRef(0);

  const runRefresh = useCallback(async () => {
    if (!enabled || !cartProducts?.length) {
      seqRef.current += 1;
      return;
    }

    const seq = ++seqRef.current;
    try {
      const result = await refreshCartPrices(cartProducts);

      if (seq !== seqRef.current) {
        return;
      }

      if (result.cartProducts?.length) {
        dispatch(updateAllItems(result.cartProducts));
      }

      if (notify && result.has_price_changes) {
        toast.info("Sepetinizdeki bazı ürünlerin fiyatı güncellendi.");
      }
    } catch {
      // Sessiz: checkout sunucuda yine güncel fiyatla hesaplanır
    }
  }, [cartProducts, dispatch, enabled, notify]);

  useEffect(() => {
    runRefresh();
  }, [runRefresh]);

  return { refreshCartPrices: runRefresh };
}
