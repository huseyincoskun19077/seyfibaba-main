import 'package:flutter/material.dart';

import '../utils/constants.dart';

class CustomRadioButton extends StatelessWidget {
  const CustomRadioButton({
    super.key,
    this.onTap,
    this.isSelected = true,
  });
  final bool isSelected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Container(
        height: 24,
        width: 24,
        decoration: BoxDecoration(
            border: Border.all(color: paragraphColor),
            color: Colors.white,
            borderRadius: BorderRadius.circular(12)),
        padding: const EdgeInsets.all(4),
        child: isSelected
            ? const CircleAvatar(backgroundColor: paragraphColor)
            : null,
      ),
    );
  }
}
