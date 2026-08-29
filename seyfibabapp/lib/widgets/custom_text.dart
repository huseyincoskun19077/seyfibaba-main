import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_fonts/google_fonts.dart';

import '../modules/animated_splash_screen/controller/translate_cubit/translate_cubit.dart';
import '../modules/animated_splash_screen/controller/translate_cubit/translate_state_model.dart';
import '../modules/authentication/controller/login/login_bloc.dart';
import '../utils/constants.dart';

class CustomText extends StatefulWidget {
  const CustomText({
    super.key,
    required this.text,
    this.fontWeight = FontWeight.w400,
    this.fontSize = 14.0,
    this.height = 1.4,
    this.maxLine = 6,
    this.color = blackColor,
    this.decorationColor = blackColor,
    this.decoration = TextDecoration.none,
    this.overflow = TextOverflow.ellipsis,
    this.textAlign = TextAlign.start,
    this.letterSpacing = 0.5,
    this.isTranslate = true,
  });

  final String text;
  final Color color;
  final Color decorationColor;
  final double fontSize;
  final FontWeight fontWeight;
  final double height;
  final TextAlign textAlign;
  final int maxLine;
  final TextOverflow overflow;
  final TextDecoration decoration;
  final double letterSpacing;
  final bool isTranslate;

  @override
  CustomTextState createState() => CustomTextState();
}

class CustomTextState extends State<CustomText> {
  late TranslateCubit translateCubit;
  late LoginBloc loginBloc;
  String? lastTranslatedText;

  @override
  void initState() {
    super.initState();
    translateCubit = TranslateCubit();
    loginBloc = context.read<LoginBloc>();

    // Only translate if the text needs translation
    if (widget.isTranslate) {
      translateText(widget.text);
    }
  }

  @override
  void didUpdateWidget(covariant CustomText oldWidget) {
    super.didUpdateWidget(oldWidget);

    if (widget.text != oldWidget.text ||
        widget.isTranslate != oldWidget.isTranslate) {
      translateText(widget.text);
    }
  }

  void translateText(String text) {
    if (widget.isTranslate) {
      translateCubit.translated(text, loginBloc.state.langCode);
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<TranslateCubit, TranslateStateModel>(
      bloc: translateCubit,
      builder: (context, state) {
        // debugPrint('rebuild-from-custom');
        final displayText = widget.isTranslate == true
            ? (state.loading ? widget.text : state.text)
            : widget.text;
        // debugPrint('displayText $displayText');
        //translateCubit.addText(displayText);

        return Text(
          displayText,
          textAlign: widget.textAlign,
          overflow: widget.overflow,
          maxLines: widget.maxLine,
          style: GoogleFonts.inter(
            fontWeight: widget.fontWeight,
            fontSize: widget.fontSize.sp,
            color: widget.color,
            height: widget.height.h,
            decoration: widget.decoration,
            decorationColor: widget.decorationColor,
            letterSpacing: widget.letterSpacing,
          ),
        );
      },
      buildWhen: (previous, current) {
        // final shouldRebuild = translateCubit.langCode != loginBloc.state.langCode;
        // debugPrint("Should rebuild: $shouldRebuild");
        // return translateCubit.state.isRebuild == true;
        return true;
      },
    );
  }

  @override
  void dispose() {
    translateCubit.close();
    super.dispose();
  }
}
