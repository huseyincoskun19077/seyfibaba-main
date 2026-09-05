const STATUS_STEP_INDEX = {
  Beklemede: 0,
  "Sipariş alındı": 0,
  Hazırlanıyor: 1,
  Kargoda: 2,
  "Kargoya Verildi": 2,
  Teslim: 3,
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
    case "Kargoda":
    case "Kargoya Verildi":
      return "bg-indigo-50 text-indigo-700 ring-indigo-200";
    case "Teslim":
    case "Teslim Edildi":
    case "Tamamlandı":
      return "bg-emerald-50 text-emerald-700 ring-emerald-200";
    case "Reddedildi":
      return "bg-red-50 text-red-700 ring-red-200";
    default:
      return "bg-amber-50 text-amber-800 ring-amber-200";
  }
}

function lineIsShipped(line) {
  if (line?.shipped_at) return true;
  if (Number(line?.seller_status) >= 2) return true;
  if (line?.cargo?.tracking_number || line?.cargo?.trackingNumber) return true;
  return false;
}

export function getOrderStatus(order) {
  const orderStatus = Number(order?.order_status);
  const lines = order?.order_products || order?.orderProducts || [];
  const orderCargo =
    order?.cargo_shipment ||
    order?.cargoShipment ||
    null;
  const hasOrderCargo = !!(
    orderCargo?.tracking_number || orderCargo?.trackingNumber
  );

  if (orderStatus === 4) {
    return "Reddedildi";
  }

  if (Array.isArray(lines) && lines.length > 0) {
    const anyDelivered = lines.some((line) => !!line?.delivered_at);
    const allDelivered = lines.every((line) => !!line?.delivered_at);
    const anyShipped = lines.some(lineIsShipped) || hasOrderCargo;
    const anyApproved = lines.some((line) => Number(line?.seller_status) >= 1);
    const allConfirmed = lines.every(
      (line) => !!line?.customer_confirmed_at || !!line?.auto_confirmed_at
    );

    if (allConfirmed && (anyDelivered || orderStatus === 3)) {
      return "Tamamlandı";
    }
    if (allDelivered || orderStatus >= 3) {
      return "Teslim";
    }
    // ÖNEMLİ: order_status >= 1 ile Hazırlanıyor deme — 2/3'ü de hazırlanıyor yapıyordu
    if (anyShipped || orderStatus === 2) {
      return "Kargoda";
    }
    if (anyApproved || orderStatus === 1) {
      return "Hazırlanıyor";
    }
    return "Sipariş alındı";
  }

  switch (orderStatus) {
    case 0:
      return "Sipariş alındı";
    case 1:
      return "Hazırlanıyor";
    case 2:
      return "Kargoda";
    case 3:
      return "Teslim";
    case 4:
      return "Reddedildi";
    default:
      return "Sipariş alındı";
  }
}
