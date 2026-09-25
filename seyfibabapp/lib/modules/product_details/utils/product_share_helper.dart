import 'package:flutter/material.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/constants.dart';

Future<void> shareProduct({
  required String name,
  required String slug,
}) async {
  final url = RemoteUrls.productShareUrl(slug);
  await Share.share('$name\n$url', subject: name);
}

Future<void> shareProductToFacebook(String slug) async {
  final url = RemoteUrls.productShareUrl(slug);
  final uri = Uri.parse(
    'https://www.facebook.com/sharer/sharer.php?u=${Uri.encodeComponent(url)}',
  );
  await launchUrl(uri, mode: LaunchMode.externalApplication);
}

Future<void> shareProductToTwitter({
  required String name,
  required String slug,
}) async {
  final url = RemoteUrls.productShareUrl(slug);
  final uri = Uri.parse(
    'https://twitter.com/intent/tweet?url=${Uri.encodeComponent(url)}&text=${Uri.encodeComponent(name)}',
  );
  await launchUrl(uri, mode: LaunchMode.externalApplication);
}

Future<void> shareProductToInstagram({
  required String name,
  required String slug,
}) async {
  // Instagram URL share desteklemez; native share ile seçilir.
  await shareProduct(name: name, slug: slug);
}

class ProductSocialShareRow extends StatelessWidget {
  const ProductSocialShareRow({
    super.key,
    required this.name,
    required this.slug,
  });

  final String name;
  final String slug;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        const Text(
          'Paylaş',
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: Color(0xFF04334A),
          ),
        ),
        const SizedBox(width: 12),
        _SocialChip(
          label: 'Facebook',
          color: const Color(0xFF1877F2),
          onTap: () => shareProductToFacebook(slug),
        ),
        const SizedBox(width: 8),
        _SocialChip(
          label: 'X',
          color: const Color(0xFF0F1419),
          onTap: () => shareProductToTwitter(name: name, slug: slug),
        ),
        const SizedBox(width: 8),
        _SocialChip(
          label: 'Instagram',
          color: const Color(0xFFE4405F),
          onTap: () => shareProductToInstagram(name: name, slug: slug),
        ),
        const SizedBox(width: 8),
        _SocialChip(
          label: 'Diğer',
          color: iconGreyColor,
          onTap: () => shareProduct(name: name, slug: slug),
        ),
      ],
    );
  }
}

class _SocialChip extends StatelessWidget {
  const _SocialChip({
    required this.label,
    required this.color,
    required this.onTap,
  });

  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withValues(alpha: 0.35)),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: color,
          ),
        ),
      ),
    );
  }
}
