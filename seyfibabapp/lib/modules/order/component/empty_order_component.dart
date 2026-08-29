import 'package:flutter/material.dart';

import '../../../utils/language_string.dart';
import '../../../widgets/app_empty_state.dart';

class EmptyOrderComponent extends StatelessWidget {
  const EmptyOrderComponent({super.key, this.tabIndex = 0});

  final int tabIndex;

  String get _title {
    switch (tabIndex) {
      case 1:
        return Language.emptyPendingOrders;
      case 2:
        return Language.emptyProgressOrders;
      case 3:
        return Language.emptyDeliveredOrders;
      case 4:
        return Language.emptyCompletedOrders;
      case 5:
        return Language.emptyDeclinedOrders;
      default:
        return Language.emptyAllOrders;
    }
  }

  String get _subtitle {
    switch (tabIndex) {
      case 0:
        return Language.emptyAllOrdersHint;
      default:
        return Language.emptyTabOrdersHint;
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppEmptyState(
      icon: Icons.shopping_bag_outlined,
      title: _title,
      subtitle: _subtitle,
    );
  }
}
