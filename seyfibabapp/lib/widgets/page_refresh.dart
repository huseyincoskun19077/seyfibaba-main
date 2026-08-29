import 'package:flutter/material.dart';

import '../utils/utils.dart';

class PageRefresh extends StatelessWidget {
  const PageRefresh({
    super.key,
    required this.child,
    required this.onRefresh,
    this.color,
  });

  final Widget child;
  final Future<void> Function() onRefresh;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final c = color ?? Utils.dynamicPrimaryColor(context);
    return RefreshIndicator(
      displacement: 100.0,
      triggerMode: RefreshIndicatorTriggerMode.anywhere,
      color: c,
      edgeOffset: 180,
      onRefresh: onRefresh,
      child: child,
    );
  }
}
