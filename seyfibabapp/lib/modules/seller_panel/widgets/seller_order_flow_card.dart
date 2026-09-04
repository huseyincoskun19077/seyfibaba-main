import 'package:flutter/material.dart';

import '../../home/widgets/home_theme.dart';

class SellerOrderFlowInfo {
  const SellerOrderFlowInfo({
    required this.sellerStatus,
    required this.payoutState,
    required this.payoutLabel,
    required this.payoutDetail,
    this.carrierName,
    this.trackingNumber,
    this.trackingUrl,
    this.payoutProcessedAt,
  });

  final int sellerStatus;
  final String payoutState;
  final String payoutLabel;
  final String payoutDetail;
  final String? carrierName;
  final String? trackingNumber;
  final String? trackingUrl;
  final String? payoutProcessedAt;

  static SellerOrderFlowInfo fromOrderMap(Map<String, dynamic> order) {
    final products = _products(order);
    final sellerStatus = _sellerStatus(products);
    final payout = _payoutInfo(order, products, sellerStatus);
    final cargo = _cargo(order);

    return SellerOrderFlowInfo(
      sellerStatus: sellerStatus,
      payoutState: payout.$1,
      payoutLabel: payout.$2,
      payoutDetail: payout.$3,
      carrierName: cargo.$1,
      trackingNumber: cargo.$2,
      trackingUrl: cargo.$3,
      payoutProcessedAt: '${order['payout_processed_at'] ?? ''}'.trim().isEmpty
          ? null
          : '${order['payout_processed_at']}',
    );
  }

  static List<Map<String, dynamic>> _products(Map<String, dynamic> order) {
    final products = order['order_products'] ?? order['orderProducts'];
    if (products is! List) return const [];
    return products
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  static int _sellerStatus(List<Map<String, dynamic>> products) {
    if (products.isEmpty) return 0;
    if (products.any((p) => int.tryParse('${p['seller_status']}') == 4)) {
      return 4;
    }
    var min = 99;
    for (final p in products) {
      final s = int.tryParse('${p['seller_status'] ?? 0}') ?? 0;
      if (s < min) min = s;
    }
    return min == 99 ? 0 : min;
  }

  static (String, String, String) _payoutInfo(
    Map<String, dynamic> order,
    List<Map<String, dynamic>> products,
    int sellerStatus,
  ) {
    if (sellerStatus < 3) {
      return (
        'waiting',
        'Hakediş henüz başlamadı',
        'Müşteri siparişi teslim aldığında hakediş süreci başlar.',
      );
    }

    if ('${order['payout_blocked_at'] ?? ''}'.trim().isNotEmpty) {
      final reason = '${order['payout_block_reason'] ?? ''}'.trim();
      return (
        'blocked',
        'Hakediş bekletiliyor',
        reason.isEmpty
            ? 'Bu siparişin ödemesi geçici olarak durduruldu.'
            : reason,
      );
    }

    final payoutStatus = '${order['payout_status'] ?? 'pending'}';
    final paid = '${order['payout_processed_at'] ?? ''}'.trim().isNotEmpty ||
        payoutStatus == 'completed' ||
        payoutStatus == 'paid' ||
        products.any(
          (p) =>
              '${p['iyzico_approved_at'] ?? ''}'.trim().isNotEmpty ||
              '${p['payout_status'] ?? ''}' == 'paid',
        );

    if (paid) {
      final at = '${order['payout_processed_at'] ?? ''}'.trim();
      return (
        'paid',
        'Hakediş ödemesi yapıldı',
        at.isEmpty ? 'Satıcı hesabınıza aktarım tamamlandı.' : 'Ödeme işlendi: $at',
      );
    }

    final method = '${order['payment_method'] ?? ''}'.toLowerCase();
    if (method == 'bankpayment') {
      return (
        'pending',
        'Hakediş çekilebilir değil',
        'Havale siparişlerinde tutar çekim talebi ile ödenir.',
      );
    }

    final eligible = '${order['payout_eligible_at'] ?? ''}'.trim();
    return (
      'pending',
      'Hakediş ödemesi bekleniyor',
      eligible.isEmpty
          ? 'Bekleme süresi sonunda hesabınıza otomatik aktarılır.'
          : 'Tahmini aktarım: $eligible',
    );
  }

  static (String?, String?, String?) _cargo(Map<String, dynamic> order) {
    final cargo = order['seller_cargo'] ??
        order['cargo_shipment'] ??
        order['cargoShipment'];
    if (cargo is Map) {
      return (
        '${cargo['carrier_name'] ?? ''}'.trim().isEmpty
            ? null
            : '${cargo['carrier_name']}',
        '${cargo['tracking_number'] ?? ''}'.trim().isEmpty
            ? null
            : '${cargo['tracking_number']}',
        '${cargo['tracking_url'] ?? ''}'.trim().isEmpty
            ? null
            : '${cargo['tracking_url']}',
      );
    }
    return (null, null, null);
  }

  List<_FlowStep> steps() {
    if (sellerStatus == 4) {
      return const [
        _FlowStep(
          key: 'cancelled',
          title: 'Sipariş iptal',
          description: 'Bu sipariş iptal edilmiş veya reddedilmiş.',
          state: _FlowStepState.cancelled,
        ),
      ];
    }

    final defs = [
      const _FlowStep(
        key: 'received',
        title: 'Yeni sipariş',
        description: 'Ödeme alındı, hazırlık bekleniyor.',
        state: _FlowStepState.upcoming,
      ),
      const _FlowStep(
        key: 'preparing',
        title: 'Hazırlık onayı',
        description: 'Ürünü paketleyip kargoya vereceğinizi onaylayın.',
        state: _FlowStepState.upcoming,
      ),
      const _FlowStep(
        key: 'shipped',
        title: 'Kargoya verildi',
        description: 'Kargo firması ve takip numarasını girin.',
        state: _FlowStepState.upcoming,
      ),
      const _FlowStep(
        key: 'completed',
        title: 'Teslim alındı',
        description: 'Müşteri teslim aldığında sipariş tamamlanır.',
        state: _FlowStepState.upcoming,
      ),
      const _FlowStep(
        key: 'payout',
        title: 'Hakediş ödemesi',
        description: 'Tamamlanan siparişin ödemesi hesabınıza aktarılır.',
        state: _FlowStepState.upcoming,
      ),
    ];

    final progressIndex = sellerStatus <= 0
        ? 0
        : sellerStatus == 1
            ? 1
            : sellerStatus == 2
                ? 2
                : 3;

    return List.generate(defs.length, (index) {
      final step = defs[index];
      if (step.key == 'payout') {
        if (sellerStatus < 3) {
          return step.copyWith(state: _FlowStepState.upcoming);
        }
        if (payoutState == 'paid') {
          return step.copyWith(state: _FlowStepState.done);
        }
        return step.copyWith(state: _FlowStepState.current);
      }

      if (sellerStatus >= 3 && index <= 3) {
        return step.copyWith(state: _FlowStepState.done);
      }
      if (index < progressIndex) {
        return step.copyWith(state: _FlowStepState.done);
      }
      if (index == progressIndex) {
        return step.copyWith(state: _FlowStepState.current);
      }
      return step.copyWith(state: _FlowStepState.upcoming);
    });
  }
}

enum _FlowStepState { done, current, upcoming, cancelled }

class _FlowStep {
  const _FlowStep({
    required this.key,
    required this.title,
    required this.description,
    required this.state,
  });

  final String key;
  final String title;
  final String description;
  final _FlowStepState state;

  _FlowStep copyWith({_FlowStepState? state}) => _FlowStep(
        key: key,
        title: title,
        description: description,
        state: state ?? this.state,
      );
}

class SellerOrderFlowCard extends StatelessWidget {
  const SellerOrderFlowCard({
    super.key,
    required this.flow,
    this.onConfirmPreparing,
    this.onShip,
  });

  final SellerOrderFlowInfo flow;
  final VoidCallback? onConfirmPreparing;
  final VoidCallback? onShip;

  @override
  Widget build(BuildContext context) {
    final steps = flow.steps();

    return Container(
      decoration: HomeTheme.cardDecoration(),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Sipariş Süreci',
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
          ),
          const SizedBox(height: 4),
          const Text(
            'Adımları sırayla tamamlayın. Sonraki adım önceki bitmeden açılmaz.',
            style: TextStyle(color: HomeTheme.textMuted, fontSize: 12),
          ),
          const SizedBox(height: 14),
          ...steps.map((step) => _StepTile(
                step: step,
                flow: flow,
                onConfirmPreparing: onConfirmPreparing,
                onShip: onShip,
              )),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: HomeTheme.bg,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: HomeTheme.border.withValues(alpha: 0.6)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Hakediş durumu',
                  style: TextStyle(
                    fontSize: 11,
                    color: HomeTheme.textMuted,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  flow.payoutLabel,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 4),
                Text(
                  flow.payoutDetail,
                  style: const TextStyle(
                    color: HomeTheme.textMuted,
                    fontSize: 12,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _StepTile extends StatelessWidget {
  const _StepTile({
    required this.step,
    required this.flow,
    this.onConfirmPreparing,
    this.onShip,
  });

  final _FlowStep step;
  final SellerOrderFlowInfo flow;
  final VoidCallback? onConfirmPreparing;
  final VoidCallback? onShip;

  @override
  Widget build(BuildContext context) {
    final isDone = step.state == _FlowStepState.done;
    final isCurrent = step.state == _FlowStepState.current;

    Color iconBg;
    Color iconFg;
    if (isDone) {
      iconBg = const Color(0xFFDFF5E5);
      iconFg = const Color(0xFF1B7A3D);
    } else if (isCurrent) {
      iconBg = HomeTheme.brandYellow.withValues(alpha: 0.45);
      iconFg = HomeTheme.textDark;
    } else {
      iconBg = const Color(0xFFF1F3F5);
      iconFg = HomeTheme.textMuted;
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 30,
            height: 30,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: iconBg, shape: BoxShape.circle),
            child: isDone
                ? Icon(Icons.check, size: 16, color: iconFg)
                : Text(
                    '${_stepNumber(step.key)}',
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 12,
                      color: iconFg,
                    ),
                  ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  step.title,
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: isCurrent ? HomeTheme.textDark : HomeTheme.textMuted,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  step.description,
                  style: const TextStyle(
                    fontSize: 12,
                    color: HomeTheme.textMuted,
                    height: 1.35,
                  ),
                ),
                if (isCurrent &&
                    step.key == 'received' &&
                    flow.sellerStatus == 0 &&
                    onConfirmPreparing != null) ...[
                  const SizedBox(height: 10),
                  _ActionBox(
                    text:
                        'Siparişi hazırlayacağınızı onaylayın. Bu adımdan sonra kargo bilgisi girebilirsiniz.',
                    buttonLabel: 'Siparişi Hazırlayacağımı Onayla',
                    onPressed: onConfirmPreparing!,
                  ),
                ],
                if (isCurrent &&
                    step.key == 'preparing' &&
                    flow.sellerStatus == 1 &&
                    onShip != null) ...[
                  const SizedBox(height: 10),
                  _ActionBox(
                    text:
                        'Kargoya teslim ettiniz mi? Kargo firması ve takip numarası zorunludur.',
                    buttonLabel: 'Kargoya Teslim Ettim',
                    onPressed: onShip!,
                  ),
                ],
                if (isCurrent &&
                    step.key == 'shipped' &&
                    flow.sellerStatus == 2) ...[
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFE8F4FD),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      flow.trackingNumber == null
                          ? 'Kargo yolda. Müşteri uygulamadan “Teslim Aldım” dediğinde sipariş tamamlanır.'
                          : 'Kargo yolda: ${flow.carrierName ?? 'Kargo'} — ${flow.trackingNumber}\nMüşteri teslim aldığında sipariş tamamlanır.',
                      style: const TextStyle(fontSize: 12, height: 1.35),
                    ),
                  ),
                ],
                if (isDone &&
                    step.key == 'shipped' &&
                    flow.trackingNumber != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    '${flow.carrierName ?? 'Kargo'} — ${flow.trackingNumber}',
                    style: const TextStyle(fontSize: 11, color: HomeTheme.textMuted),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  int _stepNumber(String key) => switch (key) {
        'received' => 1,
        'preparing' => 2,
        'shipped' => 3,
        'completed' => 4,
        'payout' => 5,
        _ => 0,
      };
}

class _ActionBox extends StatelessWidget {
  const _ActionBox({
    required this.text,
    required this.buttonLabel,
    required this.onPressed,
  });

  final String text;
  final String buttonLabel;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: HomeTheme.brandYellow.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: HomeTheme.brandYellow.withValues(alpha: 0.45)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(text, style: const TextStyle(fontSize: 12, height: 1.35)),
          const SizedBox(height: 10),
          FilledButton(
            onPressed: onPressed,
            style: FilledButton.styleFrom(
              backgroundColor: HomeTheme.brandYellow,
              foregroundColor: HomeTheme.textDark,
              padding: const EdgeInsets.symmetric(vertical: 12),
            ),
            child: Text(buttonLabel),
          ),
        ],
      ),
    );
  }
}

String sellerStatusListLabel(int status) => switch (status) {
      0 => 'Yeni Sipariş',
      1 => 'Hazırlanıyor',
      2 => 'Kargoda',
      3 => 'Tamamlandı',
      4 => 'İptal',
      _ => 'Durum $status',
    };

String sellerPayoutShortLabel(String payoutState) => switch (payoutState) {
      'paid' => 'Ödendi',
      'blocked' => 'Bekletiliyor',
      'waiting' => 'Bekliyor',
      _ => 'Beklemede',
    };
