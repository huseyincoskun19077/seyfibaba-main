import 'package:flutter/material.dart';

import '../../../utils/constants.dart';
import '../../../widgets/custom_text.dart';
import '../../home/widgets/home_theme.dart';

class CategoryLevelTile extends StatelessWidget {
  const CategoryLevelTile({
    super.key,
    required this.name,
    required this.onTap,
    this.highlight = false,
  });

  final String name;
  final VoidCallback onTap;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final fontSize = width < 360 ? 11.0 : 12.5;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          decoration: BoxDecoration(
            color:
                highlight ? greenColor.withValues(alpha: 0.08) : HomeTheme.card,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: highlight ? greenColor : HomeTheme.border,
            ),
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
            child: Center(
              child: CustomText(
                text: name,
                maxLine: 2,
                textAlign: TextAlign.center,
                overflow: TextOverflow.ellipsis,
                fontSize: fontSize,
                fontWeight: highlight ? FontWeight.w700 : FontWeight.w600,
                color: highlight ? greenColor : blackColor,
                height: 1.25,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Alt kategori / alt-alt kategori grid — ekran genişliğine göre 2 veya 3 sütun.
class CategoryLevelGrid extends StatelessWidget {
  const CategoryLevelGrid({
    super.key,
    required this.itemCount,
    required this.itemBuilder,
  });

  final int itemCount;
  final IndexedWidgetBuilder itemBuilder;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final crossCount = width < 380 ? 2 : 3;
    final spacing = width < 360 ? 8.0 : 10.0;
    final horizontal = width < 360 ? 10.0 : 14.0;
    // 2 satır metin + padding için yeterli sabit yükseklik (taşma/kayma olmasın)
    final mainExtent = width < 360 ? 78.0 : 86.0;

    return GridView.builder(
      padding: EdgeInsets.fromLTRB(horizontal, 14, horizontal, 24),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossCount,
        crossAxisSpacing: spacing,
        mainAxisSpacing: spacing,
        mainAxisExtent: mainExtent,
      ),
      itemCount: itemCount,
      itemBuilder: itemBuilder,
    );
  }
}
