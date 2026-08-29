import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import '../utils/constants.dart';
import '../utils/utils.dart';
import 'custom_text.dart';

class PrimaryButton extends StatelessWidget {
  const PrimaryButton({
    super.key,
    this.maximumSize = const Size(double.infinity, 52.0),
    required this.text,
    this.fontSize = 18.0,
    this.fontWeight = FontWeight.w500,
    required this.onPressed,
    this.textColor = whiteColor,
    this.bgColor = blackColor,
    this.borderColor = primaryColor,
    this.minimumSize = const Size(double.infinity, 52.0),
    this.borderRadiusSize = 10.0,
    this.buttonType = ButtonType.elevated,
    this.padding,
    this.icon,
    this.fontFamily,
  });

  final VoidCallback? onPressed;

  final String text;
  final Size maximumSize;
  final Size minimumSize;
  final double fontSize;
  final double borderRadiusSize;
  final Color textColor;
  final Color bgColor;
  final Color borderColor;
  final ButtonType buttonType;
  final EdgeInsets? padding;
  final Widget? icon;
  final FontWeight fontWeight;
  final String? fontFamily;

  @override
  Widget build(BuildContext context) {
    final p = padding ?? Utils.all(value: 0.0);
    final tempIcon = icon ?? const Icon(Icons.add);
    final borderRadius = Utils.borderRadius(r: borderRadiusSize);
    if (buttonType == ButtonType.iconButton) {
      return Padding(
        padding: p,
        child: ElevatedButton.icon(
          style: ButtonStyle(
            backgroundColor: WidgetStateProperty.all(bgColor),
            splashFactory: NoSplash.splashFactory,
            shadowColor: WidgetStateProperty.all(transparent),
            overlayColor: WidgetStateProperty.all(transparent),
            elevation: WidgetStateProperty.all(0.0),
            shape: WidgetStateProperty.all(RoundedRectangleBorder(
                borderRadius: borderRadius,
                side: BorderSide(color: borderColor))),
            minimumSize: WidgetStateProperty.all(minimumSize),
            maximumSize: WidgetStateProperty.all(maximumSize),
          ),
          onPressed: onPressed,
          label: tempIcon,
          icon: Padding(
            padding: p,
            child: CustomText(
              text: text,
              color: textColor,
              fontSize: fontSize.sp,
              height: 1.5.h,
              fontWeight: fontWeight,
              textAlign: TextAlign.center,
            ),
          ),
        ),
      );
    } else if (buttonType == ButtonType.outlined) {
      return Padding(
        padding: p,
        child: OutlinedButton(
          onPressed: onPressed,
          style: ButtonStyle(
            backgroundColor: WidgetStateProperty.all(bgColor),
            splashFactory: NoSplash.splashFactory,
            shadowColor: WidgetStateProperty.all(transparent),
            overlayColor: WidgetStateProperty.all(transparent),
            elevation: WidgetStateProperty.all(0.0),
            shape: WidgetStateProperty.all(RoundedRectangleBorder(
                borderRadius: borderRadius,
                side: BorderSide(color: borderColor))),
            minimumSize: WidgetStateProperty.all(minimumSize),
            maximumSize: WidgetStateProperty.all(maximumSize),
          ),
          child: Padding(
            padding: Utils.only(bottom: 0.0),
            child: CustomText(
              text: text,
              color: textColor,
              fontSize: fontSize.sp,
              height: 1.5.h,
              fontWeight: fontWeight,
              textAlign: TextAlign.center,
            ),
          ),
        ),
      );
    } else {
      return Padding(
        padding: p,
        child: ElevatedButton(
          onPressed: onPressed,
          style: ButtonStyle(
            backgroundColor: WidgetStateProperty.all(bgColor),
            splashFactory: NoSplash.splashFactory,
            shadowColor: WidgetStateProperty.all(transparent),
            overlayColor: WidgetStateProperty.all(transparent),
            elevation: WidgetStateProperty.all(0.0),
            shape: WidgetStateProperty.all(
                RoundedRectangleBorder(borderRadius: borderRadius)),
            minimumSize: WidgetStateProperty.all(minimumSize),
            maximumSize: WidgetStateProperty.all(maximumSize),
          ),
          child: Padding(
            padding: Utils.only(bottom: 4.0),
            child: CustomText(
              text: text,
              color: textColor,
              fontSize: fontSize,
              height: 1.5,
              fontWeight: fontWeight,
              textAlign: TextAlign.center,
              maxLine: 1,
            ),
          ),
        ),
      );
    }
  }
}

enum ButtonType { elevated, outlined, iconButton }
