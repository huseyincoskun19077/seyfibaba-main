import 'package:flutter/material.dart';

import '../../../core/router_name.dart';
import '../../home/widgets/home_theme.dart';

class SellerKycBanner extends StatelessWidget {
  const SellerKycBanner({
    super.key,
    required this.kycStatus,
    this.message,
    this.tappable = true,
  });

  final String kycStatus;
  final String? message;
  final bool tappable;

  bool get isBlocking => kycStatus != 'approved';

  @override
  Widget build(BuildContext context) {
    if (!isBlocking) return const SizedBox.shrink();

    final (title, body, color) = switch (kycStatus) {
      'pending' => (
          'KYC incelemede',
          'Belgeleriniz inceleniyor. Onaylanınca ürün ekleyebilir / Excel yükleyebilirsiniz.',
          const Color(0xFFF9A825),
        ),
      'rejected' => (
          'KYC reddedildi',
          'Belgelerinizi güncelleyin — dokunun.',
          Colors.redAccent,
        ),
      _ => (
          'KYC gerekli',
          message ??
              'Ürün eklemek için doğrulama yapın — dokunup belge yükleyin.',
          HomeTheme.brandYellow,
        ),
    };

    final card = Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 4),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.45)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontWeight: FontWeight.w800,
              color: HomeTheme.textDark,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            body,
            style: const TextStyle(
              fontSize: 12,
              color: HomeTheme.textMuted,
              height: 1.35,
            ),
          ),
        ],
      ),
    );

    if (!tappable) return card;
    return GestureDetector(
      onTap: () =>
          Navigator.pushNamed(context, RouteNames.sellerKycScreen),
      child: card,
    );
  }
}
