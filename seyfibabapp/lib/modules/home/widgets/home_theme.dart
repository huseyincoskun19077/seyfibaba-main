import 'package:flutter/material.dart';

import '../../../utils/constants.dart';

/// Ana sayfa görsel dili — Seyfibaba marka renkleri (web ile uyumlu)
class HomeTheme {
  HomeTheme._();

  /// Web: primarygray #f8f8f8
  static const bg = Color(0xFFF8F8F8);
  static const header = whiteColor;
  static const headerBorder = Color(0xFFEFEFEF);
  static const brandYellow = yellowColor;
  /// Web: qblack #222222
  static const textDark = Color(0xFF222222);
  /// Web: qgray #707070
  static const textMuted = Color(0xFF707070);
  static const card = whiteColor;
  static const muted = Color(0xFF8E8E93);
  static const border = Color(0xFFE8E8ED);

  static const radius = 14.0;
  static const radiusSm = 10.0;

  static BoxDecoration cardDecoration({Color? color}) => BoxDecoration(
        color: color ?? card,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: border.withValues(alpha: 0.6)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      );

  static BoxDecoration softCardShadow = BoxDecoration(
    color: card,
    borderRadius: BorderRadius.circular(radiusSm),
    boxShadow: [
      BoxShadow(
        color: Colors.black.withValues(alpha: 0.06),
        blurRadius: 12,
        offset: const Offset(0, 4),
      ),
    ],
  );
}
