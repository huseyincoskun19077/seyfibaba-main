import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../core/router_name.dart';
import '../../utils/my_theme.dart';
import '../../utils/utils.dart';
import '../authentication/controller/login/login_bloc.dart';
import 'controller/app_setting_cubit/app_setting_cubit.dart';
import 'controller/currency/currency_cubit.dart';
import 'controller/internet_status/internet_status_bloc.dart';
import 'controller/translate_cubit/translate_cubit.dart';
import 'widgets/animation_splash_widget.dart';
import 'widgets/setting_error_widget.dart';

class AnimatedSplashScreen extends StatefulWidget {
  const AnimatedSplashScreen({super.key});

  @override
  SplashScreenState createState() => SplashScreenState();
}

class SplashScreenState extends State<AnimatedSplashScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController animationController;
  late Animation<double> animation;
  late LoginBloc loginBloc;
  late AppSettingCubit sCubit;
  late CurrencyCubit cCubit;
  late TranslateCubit tCubit;

  @override
  void dispose() {
    animationController.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    loginBloc = context.read<LoginBloc>();
    sCubit = context.read<AppSettingCubit>();
    cCubit = context.read<CurrencyCubit>();
    tCubit = context.read<TranslateCubit>();
    animationController =
        AnimationController(vsync: this, duration: const Duration(seconds: 2));
    animation =
        CurvedAnimation(parent: animationController, curve: Curves.easeOut);

    animation.addListener(() {
      if (mounted) setState(() {});
    });
    animationController.forward();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _continueIfReady(sCubit.state);
    });
  }

  void _continueIfReady(AppSettingState state) {
    if (state is! AppSettingStateLoaded) return;

    MyTheme.dynamicColor = Utils.dynamicPrimaryColor(context);
    cCubit.resetList(false);

    final currencies = sCubit.settingModel?.currencies;
    if (currencies != null) {
      for (final currency in currencies) {
        final def = '${currency.isDefault}'.toLowerCase();
        if ((def == 'yes' || def == '1') && currency.status == 1) {
          cCubit.addNewCurrency(currency);
        }
      }
    }

    final languages = sCubit.settingModel?.languages;
    if (languages != null) {
      for (final language in languages) {
        final def = '${language.isDefault}'.toLowerCase();
        if ((def == 'yes' || def == '1') && language.status == 1) {
          loginBloc.add(LoginEventLanguageCode(language.langCode));
          cCubit.addNewLanguage(language);
          tCubit.translateNavText(language.langCode);
        }
      }
    }

    if ((state.settingModel.maintainTextModel?.status ?? 0) == 0) {
      final next = sCubit.isOnBoardingShown
          ? RouteNames.mainPage
          : RouteNames.onBoardingScreen;
      Navigator.pushReplacementNamed(context, next);
    } else {
      Navigator.pushReplacementNamed(context, RouteNames.maintainScreen);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: MultiBlocListener(
        listeners: [
          BlocListener<InternetStatusBloc, InternetStatusState>(
            listener: (context, state) {
              if (state is InternetStatusBackState) {
                // Utils.showSnackBar(context, state.message);
                sCubit.loadWebSetting();
              } else if (state is InternetStatusLostState) {
                debugPrint('no internet');
                Utils.showSnackBar(context, state.message);
              }
            },
          ),
          BlocListener<AppSettingCubit, AppSettingState>(
            listener: (context, state) {
              if (state is AppSettingStateLoaded) {
                _continueIfReady(state);
              } else if (state is AppSettingStateError) {
                Utils.errorSnackBar(
                  context,
                  'Sunucuya bağlanılamadı. İnterneti kontrol edip tekrar deneyin.',
                );
              }
            },
          ),
        ],
        child: BlocBuilder<AppSettingCubit, AppSettingState>(
          builder: (context, state) {
            if (state is AppSettingStateError) {
              return SettingErrorWidget(
                message: 'Sunucuya bağlanılamadı.\nTekrar denemek için yenile.',
              );
            }
            return AnimationSplashWidget(animation: animation);
          },
        ),
      ),
    );
  }
}

// class SplashWidget extends StatelessWidget {
//   const SplashWidget({super.key});
//
//   @override
//   Widget build(BuildContext context) {
//     final size = MediaQuery.sizeOf(context);
//     const image = 'assets/celove_splash.png';
//     return SizedBox(
//       height: size.height,
//       width: size.width,
//       child: const CustomImage(path: image),
//     );
//   }
// }
