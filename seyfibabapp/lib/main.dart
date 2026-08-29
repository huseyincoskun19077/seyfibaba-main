import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shop_o/utils/constants.dart';
import '/utils/my_theme.dart';
import 'core/push/push_notification_service.dart';
import 'core/router_name.dart';
import 'firebase_options.dart';
import 'state_injector.dart';
import 'utils/k_strings.dart';
import 'utils/utils.dart';
import 'widgets/custom_text.dart';

final GlobalKey<NavigatorState> rootNavigatorKey = GlobalKey<NavigatorState>();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Emülatör/cihaz saati veya SSL sorunlarında fonts.gstatic.com çökmesin.
  GoogleFonts.config.allowRuntimeFetching = false;
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);
  await StateInjector.init();
  runApp(const MyApp());
  WidgetsBinding.instance.addPostFrameCallback((_) {
    unawaited(_initPushSafely());
  });
}

Future<void> _initPushSafely() async {
  try {
    await PushNotificationService.instance
        .initialize(navigatorKey: rootNavigatorKey);
  } catch (e, st) {
    debugPrint('Push init skipped: $e\n$st');
  }
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    ErrorWidget.builder = (FlutterErrorDetails errorDetails) {
      if (kReleaseMode) {
        return Container(
          color: Colors.grey[200],
          alignment: Alignment.center,
          child: CustomText(text: errorDetails.summary.toString()),
        );
      } else {
        // return CustomText(
        //   text:errorDetails.exceptionAsString(),
        //   color: redColor,
        //   fontWeight: FontWeight.w500,
        // );
        return Container(
          color: redColor,
          padding: Utils.all(value: 16.0),
          alignment: Alignment.center,
          child: SingleChildScrollView(
            child: CustomText(
              text: errorDetails.exceptionAsString(),
              color: whiteColor,
              fontWeight: FontWeight.w500,
            ),
          ),
        );
      }
    };
    return ScreenUtilInit(
      designSize: const Size(375.0, 812.0),
      minTextAdapt: true,
      splitScreenMode: true,
      useInheritedMediaQuery: true,
      builder: (BuildContext context, child) {
        return MultiRepositoryProvider(
          providers: StateInjector.repositoryProviders,
          child: MultiBlocProvider(
            providers: StateInjector.blocProviders,
            child: MaterialApp(
              navigatorKey: rootNavigatorKey,
              debugShowCheckedModeBanner: false,
              title: KStrings.appName,
              theme: MyTheme.theme(context),
              onGenerateRoute: RouteNames.generateRoute,
              initialRoute: RouteNames.animatedSplashScreen,
              builder: (context, child) {
                return MediaQuery(
                  data: MediaQuery.of(
                    context,
                  ).copyWith(textScaler: const TextScaler.linear(1.0)),
                  child: child ?? const SizedBox.shrink(),
                );
              },
            ),
          ),
        );
      },
    );
  }
}
