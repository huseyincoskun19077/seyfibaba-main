import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../utils/k_images.dart';
import '../../../utils/k_strings.dart';
import '../../../widgets/custom_image.dart';
import '../controller/app_setting_cubit/app_setting_cubit.dart';

class AnimationSplashWidget extends StatelessWidget {
  const AnimationSplashWidget({
    super.key,
    required this.animation,
  });

  final Animation<double> animation;

  @override
  Widget build(BuildContext context) {
    final appSetting = context.watch<AppSettingCubit>();
    final logoUrl = appSetting.settingModel?.setting.logo ?? '';
    final screenWidth = MediaQuery.sizeOf(context).width;
    final scale = animation.value.clamp(0.0, 1.0);
    final logoWidth = screenWidth * 0.78 * scale;
    final logoHeight = logoWidth * 0.38;

    return Container(
      color: Colors.white,
      child: Stack(
        fit: StackFit.expand,
        children: <Widget>[
          Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Logo: API'den yükle, yoksa isme geri dön
                AnimatedOpacity(
                  opacity: animation.value.clamp(0.0, 1.0),
                  duration: const Duration(milliseconds: 600),
                  child: logoUrl.isNotEmpty
                      ? SizedBox(
                          width: logoWidth,
                          height: logoHeight,
                          child: CustomImage(
                            path: logoUrl,
                            width: logoWidth,
                            height: logoHeight,
                            fit: BoxFit.contain,
                            errorPath: Kimages.splashRoundLogo,
                          ),
                        )
                      : Text(
                          'Seyfibaba',
                          style: GoogleFonts.poppins(
                            fontSize: scale * 44,
                            fontWeight: FontWeight.w700,
                            color: const Color(0xFF1A1A2E),
                          ),
                        ),
                ),
                const SizedBox(height: 16),
                AnimatedOpacity(
                  opacity: (animation.value * 2 - 1).clamp(0.0, 1.0),
                  duration: const Duration(milliseconds: 400),
                  child: Text(
                    KStrings.splashTitle,
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      color: Colors.grey[600],
                      fontWeight: FontWeight.w400,
                    ),
                  ),
                ),
              ],
            ),
          ),
          // Alt kısımda versiyon
          Positioned(
            bottom: 32,
            left: 0,
            right: 0,
            child: AnimatedOpacity(
              opacity: animation.value.clamp(0.0, 1.0),
              duration: const Duration(milliseconds: 600),
              child: Text(
                'v${KStrings.appVersion}',
                textAlign: TextAlign.center,
                style: GoogleFonts.poppins(
                  fontSize: 12,
                  color: Colors.grey[400],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
