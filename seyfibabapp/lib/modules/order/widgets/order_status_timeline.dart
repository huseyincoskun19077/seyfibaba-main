import 'package:flutter/material.dart';

import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../home/widgets/home_theme.dart';
import '../model/order_model.dart';

class OrderStatusTimeline extends StatelessWidget {
  const OrderStatusTimeline({super.key, required this.order});

  final OrderModel order;

  int get _activeIndex {
    switch (order.orderStatus) {
      case 1:
        return 1;
      case 2:
        return 2;
      case 3:
        return 3;
      default:
        return 0;
    }
  }

  List<({String title, String? date})> get _steps => [
        (title: Language.orderReceived, date: order.createdAt),
        (title: Language.progress, date: order.orderApprovalDate),
        (title: Language.delivered, date: order.orderDeliveredDate),
        (title: Language.completed, date: order.orderCompletedDate),
      ];

  @override
  Widget build(BuildContext context) {
    if (order.orderStatus == 4) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        decoration: HomeTheme.cardDecoration(),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: Colors.red.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.cancel_outlined, color: Colors.red),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    Language.orderIsDeclined,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: HomeTheme.textDark,
                    ),
                  ),
                  if (order.orderDeclinedDate.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      Utils.formatDate(order.orderDeclinedDate),
                      style: const TextStyle(
                        fontSize: 12,
                        color: HomeTheme.textMuted,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      );
    }

    final active = _activeIndex;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
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
          for (var i = 0; i < _steps.length; i++)
            _TimelineRow(
              title: _steps[i].title,
              date: _steps[i].date,
              isDone: i <= active,
              isActive: i == active,
              isLast: i == _steps.length - 1,
            ),
        ],
      ),
    );
  }
}

class _TimelineRow extends StatelessWidget {
  const _TimelineRow({
    required this.title,
    required this.date,
    required this.isDone,
    required this.isActive,
    required this.isLast,
  });

  final String title;
  final String? date;
  final bool isDone;
  final bool isActive;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
    final dotColor = isDone ? HomeTheme.brandYellow : HomeTheme.headerBorder;
    final lineColor = isDone ? HomeTheme.brandYellow : HomeTheme.headerBorder;

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 24,
            child: Column(
              children: [
                Container(
                  width: isActive ? 14 : 10,
                  height: isActive ? 14 : 10,
                  decoration: BoxDecoration(
                    color: isDone ? dotColor : Colors.transparent,
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: isDone ? dotColor : HomeTheme.textMuted,
                      width: isActive ? 0 : 2,
                    ),
                  ),
                ),
                if (!isLast)
                  Expanded(
                    child: Container(
                      width: 2,
                      margin: const EdgeInsets.symmetric(vertical: 4),
                      color: lineColor,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 0 : 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                      color: isDone
                          ? HomeTheme.textDark
                          : HomeTheme.textMuted,
                    ),
                  ),
                  if (date != null && date!.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      Utils.formatDate(date!),
                      style: const TextStyle(
                        fontSize: 11,
                        color: HomeTheme.textMuted,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
