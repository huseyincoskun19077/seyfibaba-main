import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shop_o/utils/utils.dart';

import 'constants.dart';


class MyTheme {
  static final borderRadius = Utils.borderRadius(r: 10.0);
  static Color dynamicColor = borderColor;

//Utils.dynamicPrimaryColor(context)

  static theme(BuildContext context) => ThemeData(
        brightness: Brightness.light,
        primaryColor: primaryColor,
        scaffoldBackgroundColor: scaBgColor,
        colorScheme: const ColorScheme.light(secondary: primaryColor),
        appBarTheme:  const AppBarTheme(
          backgroundColor: scaBgColor,
          scrolledUnderElevation: 0.0,
          centerTitle: true,
          titleTextStyle: TextStyle(color: blackColor, fontSize: 20, fontWeight: FontWeight.bold),
          iconTheme: IconThemeData(color: blackColor),
          elevation: 0,
        ),
        textTheme: GoogleFonts.interTextTheme(
          const TextTheme(
            bodySmall: TextStyle(fontSize: 12, height: 1.83),
            bodyLarge: TextStyle(
                fontSize: 16, fontWeight: FontWeight.w500, height: 1.375),
            bodyMedium: TextStyle(fontSize: 14, height: 1.5714),
            labelLarge:
                TextStyle(fontSize: 16, height: 2, fontWeight: FontWeight.w600),
            // titleLarge: const TextStyle(
            //     fontSize: 16, height: 2, fontWeight: FontWeight.w600),
          ),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            minimumSize: const Size(double.infinity, 64),
            backgroundColor: dynamicColor,
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(50)),
          ),
        ),
        bottomNavigationBarTheme: const BottomNavigationBarThemeData(
          elevation: 3,
          backgroundColor: Color(0x00ffffff),
          selectedLabelStyle: TextStyle(color: blackColor, fontSize: 14.0),
          unselectedLabelStyle: TextStyle(color: grayColor, fontSize: 12.0),
          selectedItemColor: blackColor,
          unselectedItemColor: grayColor,
          showUnselectedLabels: true,
        ),
        inputDecorationTheme: InputDecorationTheme(
          isDense: true,
          hintStyle: const TextStyle(color: grayColor),
          labelStyle: const TextStyle(color: grayColor, fontSize: 16),
          contentPadding: Utils.symmetric(h: 20.0,v: 18.0),
          border: OutlineInputBorder(
            borderRadius: borderRadius,
            borderSide: const BorderSide(color: borderColor),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: borderRadius,
            borderSide: const BorderSide(color: borderColor),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: borderRadius,
            borderSide: const BorderSide(color: borderColor),
          ),
          fillColor: whiteColor,
          filled: true,
          focusColor: whiteColor,
        ),
        textSelectionTheme: const TextSelectionThemeData(
          cursorColor: blackColor,
          selectionColor: blackColor,
          selectionHandleColor: blackColor,
        ),

        // progressIndicatorTheme: ProgressIndicatorThemeData(
        //     color: Utils.dynamicPrimaryColor(context)),
      );
}
