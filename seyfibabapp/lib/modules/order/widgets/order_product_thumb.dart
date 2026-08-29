import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../widgets/custom_image.dart';
import '../../home/widgets/home_theme.dart';

class OrderProductThumb extends StatelessWidget {
  const OrderProductThumb({
    super.key,
    required this.thumbImage,
    this.size = 56,
    this.radius = 12,
  });

  final String thumbImage;
  final double size;
  final double radius;

  @override
  Widget build(BuildContext context) {
    if (thumbImage.isNotEmpty) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(radius),
        child: CustomImage(
          path: RemoteUrls.imageUrl(thumbImage),
          fit: BoxFit.cover,
          width: size,
          height: size,
        ),
      );
    }

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: HomeTheme.brandYellow.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(radius),
      ),
      child: Icon(
        Icons.inventory_2_outlined,
        size: size * 0.48,
        color: HomeTheme.textDark,
      ),
    );
  }
}
