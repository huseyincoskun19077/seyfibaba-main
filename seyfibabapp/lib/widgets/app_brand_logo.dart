import 'package:flutter/material.dart';

import '../utils/k_images.dart';

/// Uygulama logosu — her zaman local `logo.png` (API kullanılmaz).
class AppBrandLogo extends StatelessWidget {
  const AppBrandLogo({
    super.key,
    this.height = 36,
    this.width,
    this.fit = BoxFit.contain,
    this.alignment = Alignment.centerLeft,
  });

  final double height;
  final double? width;
  final BoxFit fit;
  final Alignment alignment;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: alignment,
      child: Image.asset(
        Kimages.appLogo,
        height: height,
        width: width,
        fit: fit,
        alignment: alignment,
      ),
    );
  }
}
