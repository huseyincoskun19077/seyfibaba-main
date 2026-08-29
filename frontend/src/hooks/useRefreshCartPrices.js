"use client";

import { useCallback, useEffect, useRef } from "react";
import { useDispatch } from "react-redux";
import { toast } from "react-toastify";
import { updateAllItems } from "@/redux/features/cart/cartSlice";
import { refreshCartPrices } from "@/utils/cartPriceRefresh";

/**
 * Sepet açıldığında güncel fiyatları sunucudan çeker (Trendyol: canlı fiyat).
 */
export default function useRefreshCartPrices(cartProducts, { enabled = true, notify = true } = {}) {
  const dispatch = useDispatch();
  const inFlightRef = useRef(false);

  const runRefresh = useCallback(async () => {
    if (!enabled || !cartProducts?.length || inFlightRef.current) {
      return;
    }

    inFlightRef.current = true;
    try {
      const result = await refreshCartPrices(cartProducts);

      if (result.cartProducts?.length) {
        dispatch(updateAllItems(result.cartProducts));
      }

      if (notify && result.has_price_changes) {
        toast.info("Sepetinizdeki bazı ürünlerin fiyatı güncellendi.");
      }
    } catch {
      // Sessiz: checkout sunucuda yine güncel fiyatla hesaplanır
    } finally {
      inFlightRef.current = false;
    }
  }, [cartProducts, dispatch, enabled, notify]);

  useEffect(() => {
    runRefresh();
  }, [runRefresh]);

  return { refreshCartPrices: runRefresh };
}
