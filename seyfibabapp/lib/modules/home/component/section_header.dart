import 'package:flutter/material.dart';

import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../widgets/custom_text.dart';
import '../widgets/home_theme.dart';

class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    this.color,
    this.onTap,
    required this.headerText,
    this.isSeeAllShow = true,
  });

  final Color? color;
  final String headerText;
  final VoidCallback? onTap;
  final bool isSeeAllShow;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          Container(
            width: 4,
            height: 20,
            decoration: BoxDecoration(
              color: HomeTheme.brandYellow,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: CustomText(
              text: headerText,
              fontSize: 17,
              color: color ?? blackColor,
              fontWeight: FontWeight.w700,
            ),
          ),
          if (isSeeAllShow && onTap != null)
            TextButton(
              onPressed: onTap,
              style: TextButton.styleFrom(
                foregroundColor: HomeTheme.muted,
                padding: const EdgeInsets.symmetric(horizontal: 8),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    Language.seeAll,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: HomeTheme.muted,
                    ),
                  ),
                  const SizedBox(width: 2),
                  Icon(
                    Icons.chevron_right_rounded,
                    size: 18,
                    color: HomeTheme.muted.withValues(alpha: 0.7),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
