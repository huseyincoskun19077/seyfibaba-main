import appConfig from "@/appConfig";
import auth from "@/utils/auth";

export default async function fetchOrderDetailsClient(orderId) {
  const token = auth()?.access_token;
  if (!token) {
    throw new Error("AUTH_REQUIRED");
  }

  const response = await fetch(
    `${appConfig.BASE_URL}api/user/order-show/${encodeURIComponent(orderId)}?token=${encodeURIComponent(token)}`,
    {
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      cache: "no-store",
    }
  );

  if (response.status === 401 || response.status === 403) {
    throw new Error("AUTH_REQUIRED");
  }

  if (!response.ok) {
    let message = "ORDER_NOT_FOUND";
    try {
      const payload = await response.json();
      if (payload?.message) {
        message = payload.message;
      }
    } catch {
      /* ignore */
    }
    throw new Error(message);
  }

  return response.json();
}
