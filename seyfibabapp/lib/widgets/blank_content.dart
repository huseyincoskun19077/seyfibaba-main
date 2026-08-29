import 'package:flutter/material.dart';
import 'package:shop_o/utils/k_images.dart';
import 'package:shop_o/widgets/custom_image.dart';
import 'package:shop_o/widgets/loading_widget.dart';

import '../utils/language_string.dart';
import '../utils/utils.dart';
import 'custom_text.dart';

class BlankContent extends StatelessWidget {
  const BlankContent({
    super.key,
    this.content,
    this.isLoading,
    this.icon,
  });

  final String? content;
  final bool? isLoading;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: Utils.mediaQuery(context).height * 0.6,
      width: double.infinity,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: isLoading != null && isLoading == true
            ? const [SizedBox(height: 100, width: 100, child: LoadingWidget())]
            : [
                const CustomImage(path: Kimages.emptyInbox),
                CustomText(
                  text: content ?? Language.noDataAvailable,
                  fontSize: 20.0,
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).hintColor.withOpacity(0.4),
                )
              ],
      ),
    );
  }
}
