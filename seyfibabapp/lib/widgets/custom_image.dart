import 'dart:io';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:shimmer/shimmer.dart';

import '../core/remote_urls.dart';
import '../../utils/constants.dart';

class CustomImage extends StatelessWidget {
  const CustomImage({
    super.key,
    required this.path,
    this.fit = BoxFit.contain,
    this.height,
    this.width,
    this.color,
    this.isFile = false,
    this.errorPath,
  });

  final String? path;
  final String? errorPath;
  final BoxFit fit;
  final double? height, width;
  final Color? color;
  final bool isFile;

  static bool isAssetPath(String imagePath) => imagePath.startsWith('assets/');

  static bool isRemotePath(String imagePath) {
    if (imagePath.startsWith('http://') ||
        imagePath.startsWith('https://') ||
        imagePath.startsWith('www.')) {
      return true;
    }

    final normalized =
        imagePath.startsWith('/') ? imagePath.substring(1) : imagePath;

    return normalized.startsWith('uploads/') ||
        normalized.startsWith('storage/') ||
        normalized.startsWith('public/');
  }

  static String resolveNetworkUrl(String imagePath) {
    if (imagePath.startsWith('http://') ||
        imagePath.startsWith('https://') ||
        imagePath.startsWith('www.')) {
      return imagePath;
    }

    final normalized =
        imagePath.startsWith('/') ? imagePath.substring(1) : imagePath;

    if (isRemotePath(normalized)) {
      return RemoteUrls.imageUrl(normalized);
    }

    return imagePath;
  }

  @override
  Widget build(BuildContext context) {
    const kNetImg =
        'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png';
    final imagePath = path ?? kNetImg;

    if (isFile) {
      return Image.file(
        File(imagePath),
        fit: fit,
        color: color,
        height: height,
        width: width,
      );
    }

    if (imagePath.endsWith('.svg') && isAssetPath(imagePath)) {
      return SizedBox(
        height: height,
        width: width,
        child: SvgPicture.asset(
          imagePath,
          fit: fit,
          height: height,
          width: width,
          color: color,
        ),
      );
    }

    if (isRemotePath(imagePath)) {
      return CachedNetworkImage(
        imageUrl: resolveNetworkUrl(imagePath),
        fit: fit,
        color: color,
        height: height,
        width: width,
        progressIndicatorBuilder: (context, url, downloadProgress) {
          return Shimmer.fromColors(
            baseColor: Colors.grey.shade200,
            highlightColor: Colors.grey.shade100,
            child: Container(
              height: height ?? 100,
              width: width ?? 100,
              color: whiteColor,
            ),
          );
        },
        errorWidget: (context, url, error) {
          if (errorPath != null && isAssetPath(errorPath!)) {
            return Image.asset(
              errorPath!,
              fit: fit,
              color: color,
              height: height,
              width: width,
            );
          }
          if (error.toString().contains('Invalid image data')) {
            return Image.network(
              Uri.encodeFull(kNetImg),
              fit: fit,
              color: color,
              height: height,
              width: width,
            );
          } else if (error is HttpException || error is Exception) {
            return Image.network(
              Uri.encodeFull(errorPath ?? kNetImg),
              fit: fit,
              color: color,
              height: height,
              width: width,
            );
          } else if (error.toString().contains('Invalid statusCode: 404')) {
            return Image.network(
              Uri.encodeFull(kNetImg),
              fit: fit,
              color: color,
              height: height,
              width: width,
            );
          } else {
            return const Icon(Icons.error);
          }
        },
      );
    }

    return Image.asset(
      imagePath,
      fit: fit,
      color: color,
      height: height,
      width: width,
      errorBuilder: (context, error, stackTrace) {
        if (errorPath != null && isAssetPath(errorPath!)) {
          return Image.asset(
            errorPath!,
            fit: fit,
            color: color,
            height: height,
            width: width,
          );
        }
        return const Icon(Icons.broken_image_outlined);
      },
    );
  }
}
