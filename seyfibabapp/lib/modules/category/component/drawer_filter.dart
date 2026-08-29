import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../widgets/custom_text.dart';
import '/widgets/capitalized_word.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/primary_button.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../animated_splash_screen/controller/currency/currency_cubit.dart';
import '../../home/controller/cubit/product/product_state_model.dart';
import '../controller/cubit/category_cubit.dart';
import '../model/product_categories_model.dart';

class DrawerFilter extends StatefulWidget {
  const DrawerFilter({super.key});

  @override
  State<DrawerFilter> createState() => _DrawerFilterState();
}

class _DrawerFilterState extends State<DrawerFilter> {
  RangeValues _currentRangeValues = const RangeValues(0, 600);
  double maxValue = 0.0;
  double minValue = double.infinity; // Start with a very high minValue
  late CategoryCubit categoryCubit;

  @override
  void initState() {
    categoryCubit = context.read<CategoryCubit>();
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    final currency =
        context.read<AppSettingCubit>().settingModel!.setting.currencyIcon;
    final cCubit = context.read<CurrencyCubit>();
    final catCubit = context.read<CategoryCubit>();

    if (catCubit.productCategoriesModel != null &&
        catCubit.catProducts.isNotEmpty) {
      for (var p in catCubit.catProducts) {
        final price = p.offerPrice;

        if (price < minValue) minValue = price;
        if (price > maxValue) maxValue = price;
      }

      if (_currentRangeValues.start == 0 && _currentRangeValues.end == 600) {
        _currentRangeValues = RangeValues(minValue, maxValue);
      }
    } else {
      // Handle case where there are no products or model is null
      minValue = 0.0;
      maxValue = 0.0;
      _currentRangeValues = RangeValues(minValue, maxValue);
      categoryCubit.minPriceChange(0.0);
      categoryCubit.maxPriceChange(0.0);
    }

    String convertPrice(double minV, double maxV) {
      if (cCubit.state.currencies.isNotEmpty) {
        return "${Utils.convertMulCurrency(minV, context, cCubit.state.currencies.first)} - ${Utils.convertMulCurrency(maxV, context, cCubit.state.currencies.first)}";
      } else {
        return "$currency$minV - $currency$maxV";
      }
    }

    if (catCubit.productCategoriesModel != null) {
      return _buildDrawer(
          context, convertPrice, catCubit.productCategoriesModel!);
    } else {
      return const SizedBox.shrink();
    }
  }

  Widget _buildDrawer(BuildContext context, String Function(double minV, double maxV) convertPrice, ProductCategoriesModel filterOptions) {
    return Drawer(
      shape: const RoundedRectangleBorder(),
      child: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 30),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            IconButton(
                padding: EdgeInsets.zero,
                onPressed: () => Scaffold.of(context).closeEndDrawer(),
                icon: Container(
                    height: 25,
                    width: 25,
                    decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                            width: 1,
                            color: Utils.dynamicPrimaryColor(context))),
                    child: const Icon(
                      Icons.clear,
                      color: redColor,
                      size: 15,
                    ))),
            CustomText(text: Language.price.capitalizeByWord()),
            BlocBuilder<CategoryCubit, ProductStateModel>(
              builder: (context, state) {
                return RangeSlider(
                  values: _currentRangeValues,
                  min: minValue,
                  max: maxValue,
                  // divisions: 5,
                  activeColor: blackColor,
                  inactiveColor: grayColor,
                  labels: RangeLabels(
                    minValue.round().toString(),
                    maxValue.round().toString(),
                  ),
                  onChanged: (RangeValues values) {
                    setState(() {
                      _currentRangeValues = values;
                      minValue = values.start;
                      maxValue = values.end;
                      categoryCubit.minPriceChange(minValue);
                      categoryCubit.maxPriceChange(maxValue);
                    });
                  },
                );
              },
            ),
            BlocBuilder<CategoryCubit, ProductStateModel>(
              builder: (context, state) {
                debugPrint('minvalue ${state.minPrice}');
                debugPrint('maxvalue ${state.maxPrice}');
                return CustomText(
                  text:
                      "${Language.price.capitalizeByWord()} ${convertPrice(state.minPrice, state.maxPrice)}",
                  key: ValueKey('${minValue}_$maxValue'),
                );
              },
            ),
            const SizedBox(height: 10),
            if (filterOptions.categories.isNotEmpty) ...[
              CustomText(
                text: Language.category.capitalizeByWord(),
                fontSize: 16,
                fontWeight: FontWeight.w700,
              ),
              const SizedBox(height: 10),
              allCategories(filterOptions),
            ],
            const SizedBox(height: 10),
            if (filterOptions.brands.isNotEmpty) ...[
              CustomText(
                text: Language.brand.capitalizeByWord(),
                fontSize: 16,
                fontWeight: FontWeight.w700,
              ),
              const SizedBox(height: 10),
              allBrandItems(filterOptions),
            ],
            const SizedBox(height: 10),
            if (filterOptions.activeVariants.isNotEmpty) ...[
              ...List.generate(
                filterOptions.activeVariants.length,
                (index) => Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CustomText(
                      text: filterOptions.activeVariants[index].name,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                    const SizedBox(height: 10),
                    if (filterOptions.activeVariants[index].activeVariantsItems
                        .isNotEmpty) ...[
                      allVariantItems(filterOptions, index),
                    ],
                    const SizedBox(height: 10),
                  ],
                ),
              )
            ],
            const SizedBox(height: 20),
            PrimaryButton(
                borderRadiusSize: 24,
                text: Language.findProduct.capitalizeByWord(),
                onPressed: () {
                  // final data = FilterModelDto(
                  //   brands: brands,
                  //   variantItems: variantsItem,
                  //   minPrice: minValue,
                  //   maxPrice: maxValue,
                  //   page: '1',
                  // );

                  //debugPrint('filter-data $data');
                  context.read<CategoryCubit>().getFilterProducts();
                  Scaffold.of(context).closeEndDrawer();
                }),
          ],
        ),
      ),
    );
  }

  Widget allCategories(ProductCategoriesModel filterOptions) {
    return BlocBuilder<CategoryCubit, ProductStateModel>(
      builder: (context, state) {
        return Wrap(
          // alignment: WrapAlignment.spaceBetween,
          // spacing: 20.0,
          children: List.generate(
            filterOptions.categories.length,
            (index) => GestureDetector(
              onTap: () =>
                  categoryCubit.addCategories(filterOptions.categories[index]),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                // mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Checkbox(
                    value: state.categories
                        .contains(filterOptions.categories[index]),
                    onChanged: (bool? val) {
                      categoryCubit
                          .addCategories(filterOptions.categories[index]);
                    },
                    activeColor: blackColor,
                    checkColor: whiteColor,
                  ),
                  CustomText(
                    text: filterOptions.categories[index].name,
                  )
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget allVariantItems(ProductCategoriesModel filterOptions, int index) {
    return BlocBuilder<CategoryCubit, ProductStateModel>(
      builder: (context, state) {
        return Wrap(
          children: List.generate(
            filterOptions.activeVariants[index].activeVariantsItems.length,
            (i) {
              final vItem =
                  filterOptions.activeVariants[index].activeVariantsItems[i];
              return GestureDetector(
                onTap: () => categoryCubit.addVariantItem(vItem),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  // mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Checkbox(
                      value: state.variantItems.contains(vItem),
                      onChanged: (bool? val) {
                        categoryCubit.addVariantItem(vItem);
                      },
                      activeColor: blackColor,
                      checkColor: whiteColor,
                    ),
                    CustomText(text: vItem.name)
                  ],
                ),
              );
            },
          ),
        );
      },
    );
  }

  Widget allBrandItems(ProductCategoriesModel filterOptions) {
    return BlocBuilder<CategoryCubit, ProductStateModel>(
      builder: (context, state) {
        return Wrap(
          // alignment: WrapAlignment.spaceBetween,
          // spacing: 20.0,
          children: List.generate(
            filterOptions.brands.length,
            (index) => GestureDetector(
              onTap: () => categoryCubit.addBrand(filterOptions.brands[index]),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                // mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Checkbox(
                    value: state.brands.contains(filterOptions.brands[index]),
                    onChanged: (bool? val) {
                      categoryCubit.addBrand(filterOptions.brands[index]);
                    },
                    activeColor: blackColor,
                    checkColor: whiteColor,
                  ),
                  CustomText(
                    text: filterOptions.brands[index].name,
                  )
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
