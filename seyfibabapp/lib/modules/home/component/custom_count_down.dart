import 'package:flutter/material.dart';

import '../../../widgets/custom_text.dart';

class CustomCircularCountDownProgress extends StatelessWidget {
  const CustomCircularCountDownProgress({
    super.key,
    required this.value,
    required this.title,
    required this.maxValue,
    required this.color,
  }) : assert(maxValue > value, "maxValue must be greater then value");
  final int value;
  final String title;
  final int maxValue;
  final Color color;

  @override
  Widget build(BuildContext context) {
    // double percent = 1 - (value / maxValue);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        CircleAvatar(
          radius: 30.0,
          backgroundColor: Colors.white,
          child: Center(
            child: CustomText(
                text: "$value",
                isTranslate: false,
                fontSize: 20.0,
                fontWeight: FontWeight.w800,
                height: 1,
                color: color),
          ),
        ),
        const SizedBox(height: 10),
        Text(
          title,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            height: 1,
            color: Colors.white,
            shadows: [
              Shadow(
                color: Colors.black87,
                blurRadius: 8,
                offset: Offset(0, 1),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
