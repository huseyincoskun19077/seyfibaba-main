import 'package:flutter/material.dart';

import '../../../utils/constants.dart';
import '../../../utils/utils.dart';

class PaymentCard extends StatelessWidget {
  const PaymentCard({
    super.key,
    required this.isSelected,
    required this.index,
    required this.onTap,
  });
  final int index;
  final bool isSelected;
  final void Function(int i) onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => onTap(index),
      child: Container(
        width: 110,
        height: 66,
        decoration: BoxDecoration(
          color: isSelected ? Utils.dynamicPrimaryColor(context) : Colors.white,
          borderRadius: BorderRadius.circular(4),
          border: Border.all(color: borderColor),
        ),
        child: Icon(
          Icons.credit_card_outlined,
          color: isSelected ? Colors.white : iconGreyColor,
        ),
      ),
    );
  }
}
