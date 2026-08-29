import 'package:flutter/material.dart';

import '../../../utils/constants.dart';

class DotIndicatorWidget extends StatelessWidget {
  const DotIndicatorWidget(
      {super.key, required this.currentIndex, required this.dotNumber});

  final int currentIndex;
  final int dotNumber;

  @override
  Widget build(BuildContext context) {
    final list = <Widget>[];
    for (int i = 0; i < dotNumber; i++) {
      list.add(_singleDot(i == currentIndex));
    }
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: list,
    );
  }

  Widget _singleDot(bool isActive) {
    return AnimatedContainer(
      duration: kDuration,
      margin: const EdgeInsets.only(right: 6, top: 8, bottom: 16),
      height: 8,
      width: isActive ? 22 : 8,
      decoration: BoxDecoration(
        color: isActive ? yellowColor : yellowColor.withValues(alpha: 0.28),
        borderRadius: BorderRadius.circular(99),
      ),
    );
  }
}
