const STATUS_STEP_INDEX = {
  Beklemede: 0,
  Hazırlanıyor: 1,
  "Kargoya Verildi": 2,
  "Teslim Edildi": 3,
  Tamamlandı: 4,
  Reddedildi: -1,
};

export function getOrderStatusStepIndex(status) {
  return STATUS_STEP_INDEX[status] ?? 0;
}

export function getOrderStatusBadgeClass(status) {
  switch (status) {
    case "Hazırlanıyor":
      return "bg-blue-50 text-blue-700 ring-blue-200";
    case "Kargoya Verildi":
      return "bg-indigo-50 text-indigo-700 ring-indigo-200";
    case "Teslim Edildi":
    case "Tamamlandı":
      return "bg-emerald-50 text-emerald-700 ring-emerald-200";
    case "Reddedildi":
      return "bg-red-50 text-red-700 ring-red-200";
    default:
      return "bg-amber-50 text-amber-800 ring-amber-200";
  }
}

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
