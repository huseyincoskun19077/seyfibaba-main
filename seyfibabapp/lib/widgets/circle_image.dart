import 'package:flutter/material.dart';

import '../utils/constants.dart';
import '../utils/k_images.dart';
import '../utils/utils.dart';
import 'custom_image.dart';

class CircleImage extends StatelessWidget {
  const CircleImage({
    super.key,
    this.image = Kimages.kNetworkImage,
    this.size = 100.0,
    this.isFile,
    this.type = ImageType.circle,
    this.radius,
    this.borderColor,
  });

  final String image;
  final double size;
  final bool? isFile;
  final ImageType type;
  final double? radius;
  final Color? borderColor;

  @override
  Widget build(BuildContext context) {
    final r = radius ?? 0.0;
    final b = borderColor ?? transparent;
    final fileImage = isFile ?? false;
    if (type == ImageType.rectangle) {
      return SizedBox(
        height: Utils.vSize(size),
        width: Utils.vSize(size),
        child: ClipRRect(
          borderRadius: Utils.borderRadius(r: r),
          child: CustomImage(
            path: image,
            fit: BoxFit.cover,
            isFile: fileImage,
            height: Utils.vSize(size),
            width: Utils.vSize(size),
          ),
        ),
      );
    } else if (type == ImageType.border) {
      return Container(
        height: Utils.vSize(size),
        width: Utils.vSize(size),
        decoration:
        BoxDecoration(shape: BoxShape.circle, border: Border.all(color: b)),
        child: ClipRRect(
          borderRadius: Utils.borderRadius(r: size / 2.0),
          child: CustomImage(
            path: image,
            fit: BoxFit.cover,
            isFile: fileImage,
            height: Utils.vSize(size),
            width: Utils.vSize(size),
          ),
        ),
      );
    } else {
      return Container(
        height: Utils.vSize(size),
        width: Utils.vSize(size),
        decoration: const BoxDecoration(
          shape: BoxShape.circle,
        ),
        child: ClipRRect(
          borderRadius: Utils.borderRadius(r: size / 2.0),
          child: CustomImage(
            path: image,
            fit: BoxFit.cover,
            isFile: fileImage,
            height: Utils.vSize(size),
            width: Utils.vSize(size),
          ),
        ),
      );
    }
  }
}

enum ImageType { circle, rectangle, border }
