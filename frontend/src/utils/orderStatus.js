export function getOrderStatus(order) {
  const lines = order?.order_products || order?.orderProducts || [];

  if (Array.isArray(lines) && lines.length > 0) {
    const anyDelivered = lines.some((line) => !!line?.delivered_at);
    const allDelivered = lines.every((line) => !!line?.delivered_at);
    const anyShipped =
      lines.some((line) => !!line?.shipped_at) ||
      lines.some((line) => Number(line?.seller_status) >= 2);
    const anyApproved = lines.some((line) => Number(line?.seller_status) >= 1);
    const allConfirmed = lines.every(
      (line) => !!line?.customer_confirmed_at || !!line?.auto_confirmed_at
    );

    if (allConfirmed && (anyDelivered || Number(order?.order_status) === 3)) {
      return "Tamamlandı";
    }
    if (allDelivered) {
      return "Teslim Edildi";
    }
    if (anyShipped) {
      return "Kargoya Verildi";
    }
    if (anyApproved) {
      return "Hazırlanıyor";
    }
    return "Beklemede";
  }

  switch (Number(order?.order_status)) {
    case 0:
      return "Beklemede";
    case 1:
      return "Hazırlanıyor";
    case 2:
      return "Teslim Edildi";
    case 3:
      return "Tamamlandı";
    case 4:
      return "Reddedildi";
    default:
      return "Beklemede";
  }
}
