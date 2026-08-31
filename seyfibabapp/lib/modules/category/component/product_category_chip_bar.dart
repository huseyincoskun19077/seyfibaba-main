import 'package:flutter/material.dart';

import '../../home/model/home_category_model.dart';
import '../../home/widgets/home_theme.dart';
import '../model/sub_category_model.dart';

class ProductCategoryChipBar extends StatelessWidget {
  const ProductCategoryChipBar({
    super.key,
    this.categories = const [],
    this.subCategories = const [],
    this.selectedCategorySlug,
    this.selectedSubCategorySlug,
    this.onCategoryTap,
    this.onSubCategoryTap,
    this.showCategories = true,
  });

  final List<HomePageCategoriesModel> categories;
  final List<SubCategoryModel> subCategories;
  final String? selectedCategorySlug;
  final String? selectedSubCategorySlug;
  final ValueChanged<HomePageCategoriesModel?>? onCategoryTap;
  final ValueChanged<SubCategoryModel?>? onSubCategoryTap;
  final bool showCategories;

  @override
  Widget build(BuildContext context) {
    final showSubRow =
        subCategories.isNotEmpty && (showCategories ? selectedCategorySlug != null : true);

    if (!showCategories && subCategories.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (showCategories && categories.isNotEmpty) ...[
          SizedBox(
            height: 40,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              children: [
                _Chip(
                  label: 'Tümü',
                  selected: selectedCategorySlug == null,
                  onTap: () => onCategoryTap?.call(null),
                ),
                ...categories.map(
                  (c) => Padding(
                    padding: const EdgeInsets.only(left: 8),
                    child: _Chip(
                      label: c.name,
                      selected: selectedCategorySlug == c.slug,
                      onTap: () => onCategoryTap?.call(c),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 6),
        ],
        if (showSubRow)
          SizedBox(
            height: 38,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              children: [
                _Chip(
                  label: 'Tümü',
                  selected: selectedSubCategorySlug == null,
                  compact: true,
                  onTap: () => onSubCategoryTap?.call(null),
                ),
                ...subCategories.map(
                  (s) => Padding(
                    padding: const EdgeInsets.only(left: 8),
                    child: _Chip(
                      label: s.name,
                      selected: selectedSubCategorySlug == s.slug,
                      compact: true,
                      onTap: () => onSubCategoryTap?.call(s),
                    ),
                  ),
                ),
              ],
            ),
          ),
        if (showCategories || showSubRow) const SizedBox(height: 4),
      ],
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({
    required this.label,
    required this.selected,
    required this.onTap,
    this.compact = false,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          padding: EdgeInsets.symmetric(
            horizontal: compact ? 12 : 14,
            vertical: compact ? 7 : 8,
          ),
          decoration: BoxDecoration(
            color: selected
                ? HomeTheme.brandYellow
                : const Color(0xFFF4F4F6),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: selected ? HomeTheme.brandYellow : HomeTheme.border,
            ),
          ),
          child: Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: compact ? 12 : 13,
              fontWeight: FontWeight.w700,
              color: HomeTheme.textDark,
            ),
          ),
        ),
      ),
    );
  }
}
