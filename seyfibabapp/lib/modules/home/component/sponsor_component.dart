import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../model/brand_model.dart';
import '../widgets/home_theme.dart';

class SponsorComponent extends StatefulWidget {
  const SponsorComponent({super.key, required this.brands});

  final List<BrandModel> brands;

  static bool hasDisplayLogo(String? logo) {
    final value = (logo ?? '').trim();
    if (value.isEmpty) return false;
    if (value.contains('preview.png')) return false;
    if (value.contains('placeholder')) return false;
    if (value.contains('server-error')) return false;
    return true;
  }

  @override
  State<SponsorComponent> createState() => _SponsorComponentState();
}

class _SponsorComponentState extends State<SponsorComponent> {
  static const double _itemSpacing = 10;

  late ScrollController _scrollController;
  Timer? _autoScrollTimer;
  bool _userInteracting = false;

  List<BrandModel> get _loopItems =>
      widget.brands.length > 1 ? [...widget.brands, ...widget.brands] : widget.brands;

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController();
    _scrollController.addListener(_handleManualScrollLoop);
    WidgetsBinding.instance.addPostFrameCallback((_) => _startAutoScroll());
  }

  @override
  void didUpdateWidget(covariant SponsorComponent oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.brands != widget.brands) {
      _autoScrollTimer?.cancel();
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(0);
      }
      WidgetsBinding.instance.addPostFrameCallback((_) => _startAutoScroll());
    }
  }

  @override
  void dispose() {
    _autoScrollTimer?.cancel();
    _scrollController.removeListener(_handleManualScrollLoop);
    _scrollController.dispose();
    super.dispose();
  }

  double get _loopWidth {
    if (!_scrollController.hasClients || widget.brands.length < 2) return 0;
    return _scrollController.position.maxScrollExtent / 2;
  }

  void _handleManualScrollLoop() {
    if (!_scrollController.hasClients || widget.brands.length < 2) return;

    final loopWidth = _loopWidth;
    if (loopWidth <= 0) return;

    final offset = _scrollController.offset;
    if (offset >= loopWidth) {
      _scrollController.jumpTo(offset - loopWidth);
    } else if (offset < 0) {
      _scrollController.jumpTo(offset + loopWidth);
    }
  }

  void _startAutoScroll() {
    _autoScrollTimer?.cancel();
    if (widget.brands.length < 2) return;

    _autoScrollTimer = Timer.periodic(const Duration(milliseconds: 30), (_) {
      if (!mounted || _userInteracting || !_scrollController.hasClients) return;

      final loopWidth = _loopWidth;
      if (loopWidth <= 0) return;

      final next = _scrollController.offset + 0.55;
      if (next >= loopWidth) {
        _scrollController.jumpTo(next - loopWidth);
      } else {
        _scrollController.jumpTo(next);
      }
    });
  }

  void _pauseAutoScroll() {
    _userInteracting = true;
    _autoScrollTimer?.cancel();
  }

  void _resumeAutoScrollLater() {
    Future.delayed(const Duration(milliseconds: 1200), () {
      if (!mounted) return;
      if (_scrollController.hasClients &&
          _scrollController.position.isScrollingNotifier.value) {
        _resumeAutoScrollLater();
        return;
      }
      _userInteracting = false;
      _startAutoScroll();
    });
  }

  @override
  Widget build(BuildContext context) {
    if (widget.brands.isEmpty) return const SizedBox.shrink();

    return SizedBox(
      height: 52,
      child: NotificationListener<ScrollNotification>(
        onNotification: (notification) {
          if (notification is ScrollStartNotification &&
              notification.dragDetails != null) {
            _pauseAutoScroll();
          } else if (notification is ScrollEndNotification) {
            _resumeAutoScrollLater();
          }
          return false;
        },
        child: ListView.separated(
          controller: _scrollController,
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          physics: const BouncingScrollPhysics(
            parent: AlwaysScrollableScrollPhysics(),
          ),
          itemCount: _loopItems.length,
          separatorBuilder: (_, __) => const SizedBox(width: _itemSpacing),
          itemBuilder: (context, index) {
            final brand = _loopItems[index];
            return _BrandItem(
              brand: brand,
              onTap: () {
                Navigator.pushNamed(
                  context,
                  RouteNames.brandProductScreen,
                  arguments: brand.slug,
                );
              },
            );
          },
        ),
      ),
    );
  }
}

class _BrandItem extends StatelessWidget {
  const _BrandItem({
    required this.brand,
    required this.onTap,
  });

  final BrandModel brand;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final showLogo = SponsorComponent.hasDisplayLogo(brand.logo);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(showLogo ? 10 : 24),
        child: Ink(
          decoration: BoxDecoration(
            color: whiteColor,
            borderRadius: BorderRadius.circular(showLogo ? 10 : 24),
            border: Border.all(color: HomeTheme.border.withValues(alpha: 0.85)),
          ),
          child: showLogo
              ? Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  child: SizedBox(
                    width: 72,
                    child: CustomImage(
                      path: RemoteUrls.imageUrl(brand.logo),
                      fit: BoxFit.contain,
                    ),
                  ),
                )
              : Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  child: CustomText(
                    text: brand.name,
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    maxLine: 1,
                    overflow: TextOverflow.ellipsis,
                    color: HomeTheme.textDark,
                  ),
                ),
        ),
      ),
    );
  }
}
