import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../core/router_name.dart';
import '../../utils/constants.dart';
import '../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../home/widgets/home_theme.dart';
import 'model/onbording_data.dart';
import 'widgets/dot_indicator_widget.dart';
import 'widgets/onboarding_art.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  OnboardingScreenState createState() => OnboardingScreenState();
}

class OnboardingScreenState extends State<OnboardingScreen> {
  late final PageController _pageController;
  int _currentPage = 0;

  int get _numPages => onBoardingList.length;
  bool get _isLast => _currentPage >= _numPages - 1;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _finish() async {
    await context.read<AppSettingCubit>().cachOnBoarding();
    if (!mounted) return;
    Navigator.pushNamedAndRemoveUntil(
      context,
      RouteNames.mainPage,
      (route) => false,
    );
  }

  void _next() {
    if (_isLast) {
      _finish();
      return;
    }
    _pageController.nextPage(
      duration: kDuration,
      curve: Curves.easeInOut,
    );
  }

  @override
  Widget build(BuildContext context) {
    final item = onBoardingList[_currentPage];
    final bottomPad = MediaQuery.paddingOf(context).bottom;
    final setting = context.read<AppSettingCubit>().settingModel?.setting;
    final bg = _hex(setting?.mobileOnboardingBg, const Color(0xFFF4F0FA));
    final remotes = [
      setting?.mobileOnboardingImage1 ?? '',
      setting?.mobileOnboardingImage2 ?? '',
      setting?.mobileOnboardingImage3 ?? '',
    ];

    return Scaffold(
      backgroundColor: bg,
      body: Stack(
        fit: StackFit.expand,
        children: [
          PageView.builder(
            controller: _pageController,
            itemCount: _numPages,
            onPageChanged: (page) => setState(() => _currentPage = page),
            itemBuilder: (context, index) {
              return OnboardingArt(
                art: onBoardingList[index].art,
                remotePath: remotes[index],
                background: bg,
              );
            },
          ),
          Positioned(
            top: 0,
            right: 0,
            child: SafeArea(
              child: TextButton(
                onPressed: _finish,
                child: const Text(
                  'Atla',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: HomeTheme.textMuted,
                  ),
                ),
              ),
            ),
          ),
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    bg.withValues(alpha: 0),
                    bg,
                  ],
                ),
              ),
              child: Padding(
                padding: EdgeInsets.fromLTRB(24, 16, 24, 16 + bottomPad),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    DotIndicatorWidget(
                      currentIndex: _currentPage,
                      dotNumber: _numPages,
                    ),
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: ElevatedButton(
                        onPressed: _next,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: item.accent,
                          foregroundColor: HomeTheme.textDark,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        child: Text(
                          _isLast ? 'Başla' : 'Devam',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

Color _hex(String? hex, Color fallback) {
  if (hex == null || hex.isEmpty) return fallback;
  var h = hex.replaceAll('#', '').trim();
  if (h.length == 6) h = 'FF$h';
  final v = int.tryParse(h, radix: 16);
  return v != null ? Color(v) : fallback;
}
