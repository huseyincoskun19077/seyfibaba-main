/** Misafir sipariş backend ile uyumlu — güvenlik nedeniyle kapalı */
export const isGuestCheckoutEnabled = () =>
  process.env.NEXT_PUBLIC_GUEST_CHECKOUT_ENABLED === "true";
