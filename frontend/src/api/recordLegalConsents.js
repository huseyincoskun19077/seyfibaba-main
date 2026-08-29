import appConfig from "@/appConfig";
import apiRoutes from "@/appConfig/apiRoutes";
import auth from "@/utils/auth";

export async function recordLegalConsents({
  consents,
  context,
  orderId,
  platform = "web",
  guestIdentifier,
}) {
  const token = auth()?.access_token;
  const url = `${apiRoutes.legalConsents}${token ? `?token=${token}` : ""}`;

  const response = await fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      "X-Platform": platform,
    },
    body: JSON.stringify({
      consents,
      context,
      order_id: orderId,
      platform,
      guest_identifier: guestIdentifier,
    }),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data?.message || "Onay kaydı oluşturulamadı.");
  }

  return data;
}

export function getGuestConsentId() {
  if (typeof window === "undefined") return null;

  const key = "seyfibaba_guest_consent_id";
  let id = window.localStorage.getItem(key);

  if (!id) {
    id =
      typeof crypto !== "undefined" && crypto.randomUUID
        ? crypto.randomUUID()
        : `guest-${Date.now()}`;
    window.localStorage.setItem(key, id);
  }

  return id;
}
