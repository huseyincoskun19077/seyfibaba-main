import 'package:flutter/material.dart';

import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../home/widgets/home_theme.dart';
import '../model/order_model.dart';
import '../utils/order_display_status.dart';

/// Web [OrderStatusStepper] ile aynı 4 adım:
/// Sipariş alındı → Hazırlanıyor → Kargoda → Teslim
class OrderStatusTimeline extends StatelessWidget {
  const OrderStatusTimeline({super.key, required this.order});

  final OrderModel order;

  static const _steps = [
    'Sipariş alındı',
    'Hazırlanıyor',
    'Kargoda',
    'Teslim',
  ];

  @override
  Widget build(BuildContext context) {
    final display = OrderDisplayStatusHelper.resolve(order);

    if (display == OrderDisplayStatus.declined) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFFFEF2F2),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFFECACA)),
        ),
        child: Text(
          Language.orderIsDeclined,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: Color(0xFFB91C1C),
          ),
        ),
      );
    }

    final activeIndex =
        OrderDisplayStatusHelper.stepIndex(display).clamp(0, _steps.length - 1);
    final fullyDone = OrderDisplayStatusHelper.isFullyCompleted(display);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(12, 16, 12, 14),
      decoration: HomeTheme.cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            Language.orderStatusTitle,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: HomeTheme.textDark,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              for (var i = 0; i < _steps.length; i++) ...[
                if (i > 0)
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.only(top: 15),
                      child: Container(
                        height: 2,
                        color: fullyDone || i <= activeIndex
                            ? HomeTheme.brandYellow
                            : HomeTheme.headerBorder,
                      ),
                    ),
                  ),
                _StepDot(
                  index: i,
                  label: _steps[i],
                  done: fullyDone || i < activeIndex,
                  active: !fullyDone && i == activeIndex,
                ),
              ],
            ],
          ),
          if (order.createdAt.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(
              Utils.formatDate(order.createdAt),
              style: const TextStyle(
                fontSize: 11,
                color: HomeTheme.textMuted,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _StepDot extends StatelessWidget {
  const _StepDot({
    required this.index,
    required this.label,
    required this.done,
    required this.active,
  });

  final int index;
  final String label;
  final bool done;
  final bool active;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 64,
      child: Column(
        children: [
          Container(
            width: 32,
            height: 32,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: done ? HomeTheme.brandYellow : Colors.white,
              border: Border.all(
                color: done || active
                    ? HomeTheme.brandYellow
                    : HomeTheme.headerBorder,
                width: 2,
              ),
              boxShadow: active
                  ? [
                      BoxShadow(
                        color: HomeTheme.brandYellow.withValues(alpha: 0.25),
                        blurRadius: 6,
                      ),
                    ]
                  : null,
            ),
            child: done
                ? const Icon(Icons.check, size: 16, color: HomeTheme.textDark)
                : Text(
                    '${index + 1}',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: active
                          ? HomeTheme.textDark
                          : HomeTheme.textMuted,
                    ),
                  ),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 10,
              height: 1.2,
              fontWeight:
                  active || done ? FontWeight.w600 : FontWeight.w500,
              color: active || done
                  ? HomeTheme.textDark
                  : HomeTheme.textMuted,
            ),
          ),
        ],
      ),
    );
  }
}
