import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../utils/language_string.dart';
import '../controller/repository/home_repository.dart';
import '../model/brand_model.dart';
import '../model/home_model.dart';
import '../widgets/home_theme.dart';
import 'section_header.dart';
import 'sponsor_component.dart';

class HomeBrandsSection extends StatefulWidget {
  const HomeBrandsSection({super.key, required this.model});

  final HomeModel model;

  @override
  State<HomeBrandsSection> createState() => _HomeBrandsSectionState();
}

class _HomeBrandsSectionState extends State<HomeBrandsSection> {
  List<BrandModel> _brands = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadBrands();
  }

  Future<void> _loadBrands() async {
    final repository = context.read<HomeRepository>();
    final result = await repository.getBrandList();

    if (!mounted) return;

    result.fold(
      (_) {
        setState(() {
          _brands = widget.model.brands;
          _loading = false;
        });
      },
      (brands) {
        setState(() {
          _brands = brands.isNotEmpty ? brands : widget.model.brands;
          _loading = false;
        });
      },
    );
  }

  String _brandSectionTitle() {
    for (final title in widget.model.sectionTitle) {
      if (title.key == 'Shop_by_Brand') {
        return title.custom ?? title.dDefault ?? Language.brand;
      }
    }
    return Language.brand;
  }

  @override
  Widget build(BuildContext context) {
    if (!_loading && _brands.isEmpty) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      padding: const EdgeInsets.only(top: 14, bottom: 14),
      decoration: HomeTheme.cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionHeader(
            headerText: _brandSectionTitle(),
            isSeeAllShow: false,
          ),
          const SizedBox(height: 12),
          if (_loading)
            const SizedBox(
              height: 52,
              child: Center(
                child: SizedBox(
                  width: 22,
                  height: 22,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            )
          else
            SponsorComponent(brands: _brands),
        ],
      ),
    );
  }
}
