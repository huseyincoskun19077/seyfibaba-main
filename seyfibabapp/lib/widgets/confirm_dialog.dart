import 'package:flutter/material.dart';
import '/utils/utils.dart';

import '../utils/constants.dart';
import 'custom_image.dart';
import 'custom_text.dart';
import 'primary_button.dart';

class ConfirmDialog extends StatelessWidget {
  const ConfirmDialog({
    super.key,
    required this.message,
    this.cancelText = 'Not Now',
    this.confirmText = 'Yes',
    required this.icon,
    required this.onTap,
  });

  final String message;
  final String cancelText;
  final String confirmText;
  final String icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      insetPadding: Utils.symmetric(h: 14.0, v: 36.0),
      shape: RoundedRectangleBorder(
        borderRadius: Utils.borderRadius(r: 12.0),
      ),
      child: Padding(
        padding: Utils.symmetric(h: 14.0, v: 30.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CustomImage(path: icon),
            Utils.verticalSpace(20.0),
            CustomText(
              text: message,
              // text: 'Do you want to Delete\nYour Account?',
              fontSize: 22.0,
              fontWeight: FontWeight.w700,
              color: blackColor,
              textAlign: TextAlign.center,
            ),
            Utils.verticalSpace(30.0),
            Row(
              children: [
                Expanded(
                    child: PrimaryButton(
                  onPressed: () => Navigator.of(context).pop(),
                  text: cancelText,
                  fontWeight: FontWeight.w600,
                  // bgColor: blackColor,
                  // textColor: whiteColor,
                  borderRadiusSize: 10.0,

                  bgColor: const Color(0xFFF7F7F7),
                  textColor: blackColor,
                )),
                Utils.horizontalSpace(20.0),
                Expanded(
                    child: PrimaryButton(
                  onPressed: onTap,
                  text: confirmText,
                  // bgColor: redColor,
                  // textColor: whiteColor,
                  bgColor: const Color(0xFFEF262C).withOpacity(0.1),
                  textColor: const Color(0xFFEF262C),
                  borderRadiusSize: 10.0,
                  fontWeight: FontWeight.w600,
                )),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
