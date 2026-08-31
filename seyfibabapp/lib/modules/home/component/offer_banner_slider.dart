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
  static const double _height = 172;
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    final homeCubit = context.read<HomeControllerCubit>();
    if (homeCubit.sliderBanner.isEmpty) return const SizedBox.shrink();

    final count = homeCubit.sliderBanner.length;

    return Padding(
      padding: const EdgeInsets.fromLTRB(0, 4, 0, 10),
      child: SizedBox(
        height: _height,
        child: Stack(
          alignment: Alignment.bottomCenter,
          children: [
            CarouselSlider(
              options: CarouselOptions(
                height: _height,
                viewportFraction: 0.9,
                enlargeCenterPage: false,
                padEnds: true,
                enableInfiniteScroll: count > 1,
                autoPlay: count > 1,
                autoPlayInterval: const Duration(seconds: 4),
                autoPlayAnimationDuration: const Duration(milliseconds: 700),
                onPageChanged: (index, _) {
                  setState(() => _currentIndex = index);
                },
              ),
              items: homeCubit.sliderBanner
                  .map((e) => Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 4),
                        child: SingleOfferBanner(slider: e),
                      ))
                  .toList(),
            ),
            if (count > 1)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: DotsIndicator(
                  dotsCount: count,
                  position: _currentIndex.toDouble(),
                  decorator: DotsDecorator(
                    activeColor: HomeTheme.brandYellow,
                    color: Colors.black.withValues(alpha: 0.22),
                    activeSize: const Size(16, 5),
                    size: const Size(5, 5),
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
