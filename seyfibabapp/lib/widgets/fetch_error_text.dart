import 'package:flutter/cupertino.dart';

import '../utils/constants.dart';
import 'custom_text.dart';

class FetchErrorText extends StatelessWidget {
  const FetchErrorText({
    super.key,
    required this.text,
    this.color = redColor,
  });

  final String text;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: CustomText(text: text, color: color, fontSize: 14.0),
    );
  }
}
