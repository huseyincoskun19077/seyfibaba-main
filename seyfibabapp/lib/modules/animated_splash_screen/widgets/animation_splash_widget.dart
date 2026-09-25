import 'package:flutter/material.dart';

import '../../../utils/k_images.dart';

class AnimationSplashWidget extends StatelessWidget {
  const AnimationSplashWidget({
    super.key,
    required this.animation,
  });

  final Animation<double> animation;

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    final maxW = size.width * 0.78;
    final maxH = size.height * 0.55;

    return ColoredBox(
      color: Colors.white,
      child: FadeTransition(
        opacity: animation,
        child: Center(
          child: ConstrainedBox(
            constraints: BoxConstraints(
              maxWidth: maxW,
              maxHeight: maxH,
            ),
            child: Image.asset(
              Kimages.splashScreen,
              fit: BoxFit.contain,
              alignment: Alignment.center,
            ),
          ),
        ),
      ),
    );
  }
}
