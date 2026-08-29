import 'package:flutter/material.dart';

import '../../../utils/constants.dart';

class FaqAppBar extends StatelessWidget implements PreferredSizeWidget {
  final double height;

  const FaqAppBar({super.key, this.onTap, this.height = 60});

  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: blackColor,
      child: SizedBox(
        height: height,
        child: Stack(
          children: [
            Container(
              height: height - 20,
              decoration: const BoxDecoration(
                color: blackColor,
                // color: Utils.dynamicPrimaryColor(context),
                borderRadius: BorderRadius.vertical(
                  bottom: Radius.circular(0),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget faqSearchBar() {
    return Positioned(
      bottom: 0,
      left: 20,
      right: 20,
      child: Container(
        decoration: const BoxDecoration(
          boxShadow: [
            BoxShadow(color: Colors.grey, blurRadius: 10),
          ],
        ),
        child: TextFormField(
          decoration: inputDecorationTheme.copyWith(
            prefixIcon: const Icon(Icons.search, size: 26),
            hintText: 'Search Name...',
            contentPadding: const EdgeInsets.symmetric(
              vertical: 11,
              horizontal: 16,
            ),
          ),
        ),
      ),
    );
  }

  @override
  Size get preferredSize => Size.fromHeight(height);
}
