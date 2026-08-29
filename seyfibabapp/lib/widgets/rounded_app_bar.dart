import 'package:flutter/material.dart';

import '../utils/constants.dart';
import 'app_bar_leading.dart';
import 'custom_text.dart';

class RoundedAppBar extends AppBar {
  final String? titleText;

  final Color bgColor;
  final Color textColor;
  final void Function()? onTap;
  final List<Widget>? options;
  final bool showBackButton;

  RoundedAppBar({
    this.titleText,
    this.textColor = blackColor,
    this.bgColor = whiteColor,
    this.onTap,
    this.options,
    this.showBackButton = true,
    super.key,
  }) : super(
          titleSpacing: 0,
          backgroundColor: bgColor,
          leading:
              showBackButton ? const AppbarLeading() : const SizedBox.shrink(),
          iconTheme: const IconThemeData(color: Colors.white),
          titleTextStyle: TextStyle(color: textColor, fontSize: 18.0, fontWeight: FontWeight.w600),
          title: titleText != null ? CustomText(text: titleText,fontWeight: FontWeight.w600,fontSize: 18.0) : null,
          elevation: 0.0,
          automaticallyImplyLeading: false,
          actions: options,
        );
}
