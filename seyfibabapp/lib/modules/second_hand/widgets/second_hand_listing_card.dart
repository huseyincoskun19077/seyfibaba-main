import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/second_hand_models.dart';
import '../services/second_hand_service.dart';
import 'second_hand_ui.dart';

class SecondHandListingCard extends StatelessWidget {
  const SecondHandListingCard({
    super.key,
    required this.listing,
    required this.onTap,
    this.compact = false,
    this.grid = false,
  });

  final SecondHandListing listing;
  final VoidCallback onTap;
  final bool compact;
  /// 2 sütun ızgara için sıkı yükseklik uyumlu kart
  final bool grid;

  String _priceLabel() {
    return NumberFormat.currency(
      locale: 'tr_TR',
      symbol: '₺',
      decimalDigits: 0,
    ).format(listing.price);
  }

  @override
  Widget build(BuildContext context) {
    final image = listing.images.isNotEmpty ? listing.images.first : null;
    final condition =
        secondHandConditionLabels[listing.condition] ?? listing.condition;

    if (compact) {
      return _CompactCard(
        listing: listing,
        image: image,
        condition: condition,
        price: _priceLabel(),
        onTap: onTap,
      );
    }

    final priceStyle = TextStyle(
      fontSize: grid ? 15 : 18,
      fontWeight: FontWeight.w800,
      color: ShTheme.dark,
    );
    final titleStyle = TextStyle(
      fontSize: grid ? 13 : 14,
      fontWeight: FontWeight.w500,
      color: ShTheme.dark,
      height: 1.25,
    );

    final imageBlock = ClipRRect(
      borderRadius: const BorderRadius.vertical(
        top: Radius.circular(ShTheme.radius),
      ),
      child: grid
          ? _ListingImage(image: image)
          : AspectRatio(
              aspectRatio: 4 / 3,
              child: _ListingImage(image: image),
            ),
    );

    final infoBlock = Padding(
      padding: EdgeInsets.fromLTRB(grid ? 8 : 12, grid ? 8 : 10, grid ? 8 : 12, grid ? 8 : 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              Flexible(
                child: _ConditionChip(label: condition, compact: grid),
              ),
              if (!grid && listing.sellerVerified) ...[
                const SizedBox(width: 4),
                const Flexible(
                  child: ShVerifiedBadge(
                    compact: true,
                    label: 'Doğrulanmış satıcı',
                  ),
                ),
              ],
            ],
          ),
          SizedBox(height: grid ? 4 : 6),
          Text(
            _priceLabel(),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: priceStyle,
          ),
          SizedBox(height: grid ? 3 : 4),
          Text(
            listing.title,
            maxLines: grid ? 1 : 2,
            overflow: TextOverflow.ellipsis,
            style: titleStyle,
          ),
          if (!grid && listing.cityDistrictLabel.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              listing.cityDistrictLabel,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 11,
                color: ShTheme.muted,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ],
      ),
    );

    return RepaintBoundary(
      child: Material(
        color: ShTheme.card,
        borderRadius: BorderRadius.circular(ShTheme.radius),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(ShTheme.radius),
          child: Container(
            decoration: ShTheme.cardDecoration(),
            clipBehavior: Clip.antiAlias,
            child: grid
                ? Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Expanded(child: imageBlock),
                      infoBlock,
                    ],
                  )
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      imageBlock,
                      infoBlock,
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

class _CompactCard extends StatelessWidget {
  const _CompactCard({
    required this.listing,
    required this.image,
    required this.condition,
    required this.price,
    required this.onTap,
  });

  final SecondHandListing listing;
  final SecondHandImage? image;
  final String condition;
  final String price;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: ShTheme.card,
      borderRadius: BorderRadius.circular(ShTheme.radius),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(ShTheme.radius),
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: ShTheme.cardDecoration(),
          child: Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(ShTheme.radiusSm),
                child: SizedBox(
                  width: 88,
                  height: 88,
                  child: _ListingImage(image: image),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      price,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: ShTheme.dark,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      listing.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: ShTheme.dark,
                        height: 1.25,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Flexible(
                          child: _ConditionChip(label: condition, compact: true),
                        ),
                        if (listing.sellerVerified) ...[
                          const SizedBox(width: 4),
                          const Flexible(
                            child: ShVerifiedBadge(
                              compact: true,
                              label: 'Doğrulandı',
                            ),
                          ),
                        ],
                      ],
                    ),
                    if (listing.cityDistrictLabel.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(
                        listing.cityDistrictLabel,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 12,
                          color: ShTheme.muted,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: ShTheme.muted),
            ],
          ),
        ),
      ),
    );
  }
}

class _ConditionChip extends StatelessWidget {
  const _ConditionChip({required this.label, this.compact = false});

  final String label;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return ConstrainedBox(
      constraints: BoxConstraints(maxWidth: compact ? 120 : 180),
      child: Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 6 : 8,
        vertical: compact ? 2 : 3,
      ),
      decoration: BoxDecoration(
        color: ShTheme.primary,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(
          fontSize: compact ? 9 : 11,
          fontWeight: FontWeight.w800,
          color: ShTheme.dark,
          height: 1.1,
        ),
      ),
    ),
    );
  }
}

class _ListingImage extends StatelessWidget {
  const _ListingImage({required this.image});

  final SecondHandImage? image;

  @override
  Widget build(BuildContext context) {
    if (image == null || image!.id <= 0) {
      return Container(
        color: ShTheme.bg,
        child: const Center(
          child: Icon(Icons.image_outlined, size: 32, color: ShTheme.muted),
        ),
      );
    }
    return ColoredBox(
      color: const Color(0xFFF0F0F3),
      child: CachedNetworkImage(
        imageUrl: SecondHandService.resolveListingImageUrl(image!),
        fit: BoxFit.contain,
        width: double.infinity,
        height: double.infinity,
        memCacheWidth: (MediaQuery.devicePixelRatioOf(context) * 200).round(),
        fadeInDuration: const Duration(milliseconds: 120),
        placeholder: (_, __) => Container(color: ShTheme.bg),
        errorWidget: (_, __, ___) => Container(
          color: ShTheme.bg,
          child: const Icon(Icons.broken_image_outlined, color: ShTheme.muted),
        ),
      ),
    );
  }
}
