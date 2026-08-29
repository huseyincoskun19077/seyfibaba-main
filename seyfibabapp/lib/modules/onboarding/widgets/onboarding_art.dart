import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/k_images.dart';
import '../../../widgets/custom_image.dart';

class OnboardingArt extends StatelessWidget {
  const OnboardingArt({
    super.key,
    required this.art,
    this.remotePath,
    this.background = const Color(0xFFF4F0FA),
  });

  final int art;
  final String? remotePath;
  final Color background;

  static const _images = [
    Kimages.onboarding1,
    Kimages.onboarding2,
    Kimages.onboarding3,
  ];

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.paddingOf(context).bottom;
    final remote = remotePath?.trim() ?? '';
    final asset = _images[art.clamp(0, _images.length - 1)];

    return ColoredBox(
      color: background,
      child: Padding(
        padding: EdgeInsets.fromLTRB(28, 40, 28, 118 + bottom),
        child: remote.isNotEmpty
            ? CustomImage(
                path: RemoteUrls.imageUrl(remote),
                fit: BoxFit.contain,
              )
            : Image.asset(
                asset,
                fit: BoxFit.contain,
                alignment: Alignment.center,
              ),
      ),
    );
  }
}
