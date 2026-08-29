import 'package:flutter/material.dart';

import '../../../utils/constants.dart';

/// Salon CRM görsel dili — yumuşak, sade, Seyfibaba sarısı ile.
class SalonCrmTheme {
  SalonCrmTheme._();

  static const bg = Color(0xFFF6F5F2);
  static const bgDeep = Color(0xFFEEECE7);
  static const surface = Color(0xFFFFFFFF);
  static const ink = Color(0xFF1C1C1A);
  static const inkSoft = Color(0xFF5C5C56);
  static const muted = Color(0xFF8A8A82);
  static const line = Color(0xFFE6E4DE);
  static const accent = yellowColor;
  static const accentSoft = Color(0xFFFFF3D6);
  static const success = Color(0xFF2F7D57);
  static const successSoft = Color(0xFFE8F5EE);
  static const danger = Color(0xFFB42318);
  static const dangerSoft = Color(0xFFFEECEC);

  static const radiusLg = 22.0;
  static const radius = 18.0;
  static const radiusSm = 14.0;

  static List<BoxShadow> get softShadow => [
        BoxShadow(
          color: const Color(0xFF1C1C1A).withValues(alpha: 0.05),
          blurRadius: 24,
          offset: const Offset(0, 10),
        ),
        BoxShadow(
          color: const Color(0xFF1C1C1A).withValues(alpha: 0.03),
          blurRadius: 6,
          offset: const Offset(0, 2),
        ),
      ];

  static BoxDecoration get pageGlow => const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Color(0xFFFFF8E8),
            bg,
            bg,
          ],
          stops: [0, 0.28, 1],
        ),
      );

  static BoxDecoration surfaceCard({
    Color? color,
    double radius = radius,
    bool elevated = true,
  }) =>
      BoxDecoration(
        color: color ?? surface,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: line.withValues(alpha: 0.9)),
        boxShadow: elevated ? softShadow : null,
      );

  static InputDecoration field(String label, {String? hint, Widget? prefix}) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      prefixIcon: prefix,
      filled: true,
      fillColor: surface,
      labelStyle: const TextStyle(color: inkSoft, fontWeight: FontWeight.w500),
      hintStyle: const TextStyle(color: muted),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(radiusSm),
        borderSide: const BorderSide(color: line),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(radiusSm),
        borderSide: const BorderSide(color: line),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(radiusSm),
        borderSide: const BorderSide(color: accent, width: 1.4),
      ),
    );
  }

  static TextStyle get titleLg => const TextStyle(
        fontSize: 28,
        height: 1.15,
        fontWeight: FontWeight.w700,
        letterSpacing: -0.6,
        color: ink,
      );

  static TextStyle get titleMd => const TextStyle(
        fontSize: 20,
        height: 1.2,
        fontWeight: FontWeight.w700,
        letterSpacing: -0.3,
        color: ink,
      );

  static TextStyle get body => const TextStyle(
        fontSize: 15,
        height: 1.45,
        fontWeight: FontWeight.w500,
        color: inkSoft,
      );

  static TextStyle get caption => const TextStyle(
        fontSize: 13,
        height: 1.4,
        fontWeight: FontWeight.w500,
        color: muted,
      );
}
