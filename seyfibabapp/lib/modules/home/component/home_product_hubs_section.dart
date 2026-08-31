import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../widgets/custom_image.dart';
import '../model/product_model.dart';
import '../widgets/home_theme.dart';

class HomeProductHubsSection extends StatelessWidget {
  const HomeProductHubsSection({
    super.key,
    required this.popularProducts,
    required this.discountedProducts,
    required this.onPopularTap,
    required this.onDiscountedTap,
  });

  final List<ProductModel> popularProducts;
  final List<ProductModel> discountedProducts;
  final VoidCallback onPopularTap;
  final VoidCallback onDiscountedTap;

  @override
  Widget build(BuildContext context) {
    if (popularProducts.isEmpty && discountedProducts.isEmpty) {
      return const SliverToBoxAdapter(child: SizedBox.shrink());
    }

    return SliverToBoxAdapter(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
        child: Row(
          children: [
            if (discountedProducts.isNotEmpty)
              Expanded(
                child: _ProductHubCard(
                  title: 'İndirimli Ürünler',
                  products: discountedProducts,
                  onTap: onDiscountedTap,
                  accent: const Color(0xFFFF6B35),
                  backgroundStart: const Color(0xFFFFF4EE),
                  backgroundEnd: const Color(0xFFFFE8DC),
                  fallbackIcon: Icons.local_offer_outlined,
                ),
              ),
            if (discountedProducts.isNotEmpty && popularProducts.isNotEmpty)
              const SizedBox(width: 10),
            if (popularProducts.isNotEmpty)
              Expanded(
                child: _ProductHubCard(
                  title: 'Popüler Ürünler',
                  products: popularProducts,
                  onTap: onPopularTap,
                  accent: HomeTheme.brandYellow,
                  backgroundStart: const Color(0xFFFFFBF0),
                  backgroundEnd: const Color(0xFFFFF3CC),
                  fallbackIcon: Icons.trending_up_rounded,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _ProductHubCard extends StatefulWidget {
  const _ProductHubCard({
    required this.title,
    required this.products,
    required this.onTap,
    required this.accent,
    required this.backgroundStart,
    required this.backgroundEnd,
    required this.fallbackIcon,
  });

  final String title;
  final List<ProductModel> products;
  final VoidCallback onTap;
  final Color accent;
  final Color backgroundStart;
  final Color backgroundEnd;
  final IconData fallbackIcon;

  @override
  State<_ProductHubCard> createState() => _ProductHubCardState();
}

class _ProductHubCardState extends State<_ProductHubCard> {
  Timer? _timer;
  int _imageIndex = 0;

  List<String> get _imagePaths => widget.products
      .map((p) => p.thumbImage.trim())
      .where((path) => path.isNotEmpty)
      .toList();

  @override
  void initState() {
    super.initState();
    _startRotation();
  }

  @override
  void didUpdateWidget(covariant _ProductHubCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.products != widget.products) {
      _imageIndex = 0;
      _restartRotation();
    }
  }

  void _startRotation() {
    _timer?.cancel();
    if (_imagePaths.length <= 1) return;
    _timer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (!mounted) return;
      setState(() => _imageIndex = (_imageIndex + 1) % _imagePaths.length);
    });
  }

  void _restartRotation() {
    _timer?.cancel();
    _startRotation();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final images = _imagePaths;
    final currentImage = images.isEmpty ? '' : images[_imageIndex % images.length];

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: widget.onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          height: 128,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [widget.backgroundStart, widget.backgroundEnd],
            ),
            border: Border.all(color: HomeTheme.border.withValues(alpha: 0.65)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: Stack(
              clipBehavior: Clip.hardEdge,
              children: [
                Positioned(
                  right: -6,
                  bottom: -10,
                  width: 88,
                  height: 88,
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: ClipOval(
                      child: AnimatedSwitcher(
                        duration: const Duration(milliseconds: 450),
                        switchInCurve: Curves.easeOut,
                        switchOutCurve: Curves.easeIn,
                        child: currentImage.isEmpty
                            ? ColoredBox(
                                key: const ValueKey('fallback'),
                                color: Colors.white,
                                child: Icon(
                                  widget.fallbackIcon,
                                  color: widget.accent,
                                  size: 32,
                                ),
                              )
                            : CustomImage(
                                key: ValueKey(currentImage),
                                path: RemoteUrls.imageUrl(currentImage),
                                fit: BoxFit.cover,
                              ),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  left: 0,
                  top: 0,
                  bottom: 0,
                  right: 52,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(12, 12, 4, 12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 22,
                          height: 3,
                          decoration: BoxDecoration(
                            color: widget.accent,
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          widget.title,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                            color: HomeTheme.textDark,
                            height: 1.2,
                          ),
                        ),
                        const Spacer(),
                        Row(
                          children: [
                            Text(
                              'Keşfet',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: HomeTheme.textMuted.withValues(alpha: 0.9),
                              ),
                            ),
                            Icon(
                              Icons.arrow_forward_ios_rounded,
                              size: 10,
                              color: HomeTheme.textMuted.withValues(alpha: 0.7),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
