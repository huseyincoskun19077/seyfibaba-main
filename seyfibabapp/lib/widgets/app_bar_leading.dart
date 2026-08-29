import 'dart:io';

import 'package:flutter/material.dart';

import '../utils/constants.dart';
import '../utils/utils.dart';

class AppbarLeading extends StatelessWidget {
  const AppbarLeading({super.key});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: GestureDetector(
        onTap: () => Navigator.pop(context),
        child: Container(
          height: 35.0,
          width: 35.0,
          decoration: BoxDecoration(
            color: Utils.dynamicPrimaryColor(context),
            shape: BoxShape.circle,
          ),
          child: Icon(
            // Icons.arrow_back_ios_new,
            Platform.isAndroid ? Icons.arrow_back : Icons.arrow_back_ios_new,
            color: blackColor,
          ),
        ),
      ),
    );
  }
}
