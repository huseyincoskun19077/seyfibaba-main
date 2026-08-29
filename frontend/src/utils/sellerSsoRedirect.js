import { toast } from "react-toastify";
import apiRoutes from "@/appConfig/apiRoutes";

/**
 * Satıcı paneline güvenli SSO yönlendirmesi — JWT URL'de taşınmaz.
 */
export async function redirectToSellerPanel(accessToken, next = null) {
  if (!accessToken) {
    toast.error("Oturum alınamadı. Lütfen tekrar giriş yapın.");
    return;
  }

  try {
    const response = await fetch(apiRoutes.sellerSsoTicket, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${accessToken}`,
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(next ? { next, token: accessToken } : { token: accessToken }),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      toast.error(
        data?.message ||
          data?.notification ||
          "Satıcı paneline yönlendirilemedi. Mağaza onayınızı kontrol edin."
      );
      return;
    }

    if (data?.redirect_url) {
      toast.success("Giriş başarılı. Satıcı paneline yönlendiriliyorsunuz.");
      window.location.href = data.redirect_url;
      return;
    }

    toast.error("Satıcı paneli adresi alınamadı. Lütfen tekrar deneyin.");
  } catch {
    toast.error("Satıcı paneline bağlanılamadı. Lütfen tekrar deneyin.");
  }
}

export default redirectToSellerPanel;
