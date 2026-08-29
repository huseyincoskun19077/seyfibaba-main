import 'package:carousel_slider/carousel_slider.dart';
import 'package:dots_indicator/dots_indicator.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../controller/cubit/home_controller_cubit.dart';
import '../widgets/home_theme.dart';
import 'single_offer_banner.dart';

class OfferBannerSlider extends StatefulWidget {
  const OfferBannerSlider({super.key});

  @override
  State<OfferBannerSlider> createState() => _OfferBannerSliderState();
}

class _OfferBannerSliderState extends State<OfferBannerSlider> {
  static const double _height = 160;
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    final homeCubit = context.read<HomeControllerCubit>();
    if (homeCubit.sliderBanner.isEmpty) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: SizedBox(
        height: _height,
        child: Stack(
          alignment: Alignment.bottomCenter,
          children: [
            CarouselSlider(
              options: CarouselOptions(
                height: _height,
                viewportFraction: 0.92,
                enlargeCenterPage: true,
                enlargeFactor: 0.18,
                enableInfiniteScroll: homeCubit.sliderBanner.length > 1,
                autoPlay: homeCubit.sliderBanner.length > 1,
                autoPlayInterval: const Duration(seconds: 4),
                autoPlayAnimationDuration: const Duration(milliseconds: 800),
                onPageChanged: (index, _) {
                  setState(() => _currentIndex = index);
                },
              ),
              items: homeCubit.sliderBanner
                  .map((e) => SingleOfferBanner(slider: e))
                  .toList(),
            ),
            if (homeCubit.sliderBanner.length > 1)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: DotsIndicator(
                  dotsCount: homeCubit.sliderBanner.length,
                  position: _currentIndex.toDouble(),
                  decorator: DotsDecorator(
                    activeColor: HomeTheme.brandYellow,
                    color: Colors.white.withValues(alpha: 0.7),
                    activeSize: const Size(18, 5),
                    size: const Size(6, 5),
                    activeShape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(3),
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(3),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
