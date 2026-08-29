import appConfig from "@/appConfig";
import { clearCartAction } from "@/redux/features/cart/cartSlice";
import auth from "@/utils/auth";

const CLEARED_ORDERS_KEY = "seyfibaba_cleared_order_carts";

function markOrderCartCleared(orderId) {
  if (typeof window === "undefined" || !orderId) return;
  try {
    const raw = sessionStorage.getItem(CLEARED_ORDERS_KEY);
    const ids = raw ? JSON.parse(raw) : [];
    if (!ids.includes(String(orderId))) {
      ids.push(String(orderId));
      sessionStorage.setItem(CLEARED_ORDERS_KEY, JSON.stringify(ids.slice(-20)));
    }
  } catch {
    /* ignore */
  }
}

export function wasOrderCartCleared(orderId) {
  if (typeof window === "undefined" || !orderId) return false;
  try {
    const raw = sessionStorage.getItem(CLEARED_ORDERS_KEY);
    const ids = raw ? JSON.parse(raw) : [];
    return ids.includes(String(orderId));
  } catch {
    return false;
  }
}

export default async function clearCartAfterOrder(dispatch, orderId = null) {
  if (orderId && wasOrderCartCleared(orderId)) {
    return;
  }

  if (dispatch) {
    dispatch(clearCartAction());
  }

  const token = auth()?.access_token;
  if (token) {
    try {
      await fetch(`${appConfig.BASE_URL}api/cart-clear?token=${encodeURIComponent(token)}`, {
        method: "GET",
        headers: { Accept: "application/json" },
      });
    } catch {
      /* Redux sepeti zaten temizlendi */
    }
  }

  if (orderId) {
    markOrderCartCleared(orderId);
  }
}
