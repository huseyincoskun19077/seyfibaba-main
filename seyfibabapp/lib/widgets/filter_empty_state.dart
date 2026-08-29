import 'package:flutter/material.dart';

import '../utils/language_string.dart';
import 'app_empty_state.dart';

/// Filtre sonucu boş liste için ortak boş state.
class FilterEmptyState extends StatelessWidget {
  const FilterEmptyState({super.key, this.hasProducts = true});

  final bool hasProducts;

  @override
  Widget build(BuildContext context) {
    return AppEmptyState(
      icon: hasProducts ? Icons.filter_alt_outlined : Icons.inventory_2_outlined,
      title: hasProducts ? Language.emptyFilterTitle : Language.noItemsFound,
      subtitle: hasProducts ? Language.emptyFilterHint : null,
    );
  }
}
