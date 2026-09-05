import 'package:flutter/material.dart';

import '../model/order_model.dart';
import '../model/product_order_model.dart';

/// Web `getOrderStatus` / stepper ile aynı sipariş durumu.
enum OrderDisplayStatus {
  pending,
  preparing,
  inCargo,
  delivered,
  completed,
  declined,
}

class OrderDisplayStatusHelper {
  OrderDisplayStatusHelper._();

  /// Sipariş geneli (web getOrderStatus ile aynı)
  static OrderDisplayStatus resolve(OrderModel order) {
    if (order.orderStatus == 4) {
      return OrderDisplayStatus.declined;
    }

    final lines = order.orderProducts;
    if (lines.isNotEmpty) {
      final anyDelivered = lines.any((l) => l.isDelivered);
      final allDelivered = lines.every((l) => l.isDelivered);
      final anyShipped = lines.any(isLineShipped);
      final anyApproved = lines.any((l) => l.sellerStatus >= 1);
      final allConfirmed = lines.every((l) => l.isCustomerConfirmed);

      if (allConfirmed && (anyDelivered || order.orderStatus == 3)) {
        return OrderDisplayStatus.completed;
      }
      if (allDelivered || order.orderStatus >= 3) {
        return OrderDisplayStatus.delivered;
      }
      // order_status >= 1 kullanma: 2 (kargoda) yanlışlıkla Hazırlanıyor oluyordu
      if (anyShipped || order.orderStatus == 2) {
        return OrderDisplayStatus.inCargo;
      }
      if (anyApproved || order.orderStatus == 1) {
        return OrderDisplayStatus.preparing;
      }
      return OrderDisplayStatus.pending;
    }

    switch (order.orderStatus) {
      case 1:
        return OrderDisplayStatus.preparing;
      case 2:
        return OrderDisplayStatus.inCargo;
      case 3:
        return OrderDisplayStatus.completed;
      case 4:
        return OrderDisplayStatus.declined;
      default:
        return OrderDisplayStatus.pending;
    }
  }

  /// Ürün satırı durumu (kargoya verilen ürün → Kargoda)
  static OrderDisplayStatus resolveLine(OrderedProductModel line) {
    if (line.isCustomerConfirmed) {
      return OrderDisplayStatus.delivered;
    }
    if (line.isDelivered) {
      return OrderDisplayStatus.delivered;
    }
    if (isLineShipped(line)) {
      return OrderDisplayStatus.inCargo;
    }
    if (line.sellerStatus >= 1) {
      return OrderDisplayStatus.preparing;
    }
    return OrderDisplayStatus.pending;
  }

  static bool isLineShipped(OrderedProductModel line) {
    if (line.shippedAt != null && line.shippedAt!.trim().isNotEmpty) {
      return true;
    }
    if (line.sellerStatus >= 2) {
      return true;
    }
    if (line.cargo?.hasTracking ?? false) {
      return true;
    }
    return false;
  }

  /// Web stepper adım indeksi (0..3). Reddedildi → -1.
  static int stepIndex(OrderDisplayStatus status) {
    switch (status) {
      case OrderDisplayStatus.pending:
        return 0;
      case OrderDisplayStatus.preparing:
        return 1;
      case OrderDisplayStatus.inCargo:
        return 2;
      case OrderDisplayStatus.delivered:
      case OrderDisplayStatus.completed:
        return 3;
      case OrderDisplayStatus.declined:
        return -1;
    }
  }

  static bool isFullyCompleted(OrderDisplayStatus status) =>
      status == OrderDisplayStatus.completed;

  static String badgeLabel(OrderDisplayStatus status) {
    switch (status) {
      case OrderDisplayStatus.pending:
        return 'Sipariş alındı';
      case OrderDisplayStatus.preparing:
        return 'Hazırlanıyor';
      case OrderDisplayStatus.inCargo:
        return 'Kargoda';
      case OrderDisplayStatus.delivered:
      case OrderDisplayStatus.completed:
        return 'Teslim';
      case OrderDisplayStatus.declined:
        return 'Reddedildi';
    }
  }

  static ({Color bg, Color fg}) badgeColors(OrderDisplayStatus status) {
    switch (status) {
      case OrderDisplayStatus.preparing:
        return (bg: const Color(0xFFEFF6FF), fg: const Color(0xFF2563EB));
      case OrderDisplayStatus.inCargo:
        return (bg: const Color(0xFFEEF2FF), fg: const Color(0xFF4338CA));
      case OrderDisplayStatus.delivered:
      case OrderDisplayStatus.completed:
        return (bg: const Color(0xFFECFDF3), fg: const Color(0xFF059669));
      case OrderDisplayStatus.declined:
        return (bg: const Color(0xFFFEF2F2), fg: const Color(0xFFDC2626));
      case OrderDisplayStatus.pending:
        return (bg: const Color(0xFFFFF6E5), fg: const Color(0xFFB45309));
    }
  }
}
