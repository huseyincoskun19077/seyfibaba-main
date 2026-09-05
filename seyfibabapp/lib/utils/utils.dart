import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_font_awesome_web_names/flutter_font_awesome.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import '/core/data/datasources/remote_data_source_packages.dart';
import '/modules/authentication/controller/login/login_bloc.dart';
import '/modules/product_details/controller/cubit/product_details_cubit.dart';
import '/utils/constants.dart';
import '/widgets/custom_text.dart';
import 'package:timeago/timeago.dart' as timeago;
import 'package:html/parser.dart' show parse;
// import 'package:html/dom.dart' as html;

import '../core/router_name.dart';
import '../modules/animated_splash_screen/controller/currency/currency_cubit.dart';
import '../modules/animated_splash_screen/controller/translate_cubit/translate_cubit.dart';
import '../modules/cart/model/guest_cart_product.dart';
import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '/widgets/seyfibaba_loading.dart';
import '../dummy_data/country_phone_code.dart';
import '../modules/animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../modules/cart/model/cart_product_model.dart';
import '../modules/product_details/model/details_product_reviews_model.dart';
import '../modules/setting/model/currencies_model.dart';
import 'k_strings.dart';

class Utils {
  static final _selectedDate = DateTime.now();

  static final _initialTime = TimeOfDay.now();



  static String priceSeparator(double value, {String locale = 'en_US', String symbol = '', int radix = 1}) {
    try {
      final formatter = NumberFormat.currency(
        locale: locale,
        symbol: symbol,
        decimalDigits: radix,
      );
      return formatter.format(value);
    } catch (e) {
      return value.toStringAsFixed(radix);
    }
  }

  static String convertMulCurrency( var price, BuildContext context, CurrenciesModel c, [int radix = 1]) {

    const String icon = '₺';
    final newCurrency = c.currencyRate > 0 ? c.currencyRate : 1.0;

    if (price is double) {
      final result = price * newCurrency;
      return '${priceSeparator(result, symbol: '', radix: radix)}$icon';
    }
    if (price is String) {
      final r = double.tryParse(price) ?? 0.0;
      final p = r * newCurrency;
      return '${priceSeparator(p, symbol: '', radix: radix)}$icon';
    }
    if (price is int) {
      final p = price * newCurrency;
      return '${priceSeparator(p, symbol: '', radix: radix)}$icon';
    }
    final p = (price as num).toDouble() * newCurrency;
    return '${priceSeparator(p, symbol: '', radix: radix)}$icon';
  }

  static String formatPrice(var price, BuildContext context, [int radix = 1]) {
    final cCubit = context.read<CurrencyCubit>();
    final appSetting = context.read<AppSettingCubit>();

    if (cCubit.state.currencies.isNotEmpty) {
      return Utils.convertMulCurrency(price, context, cCubit.state.currencies.first, radix);
    } else if (appSetting.settingModel?.setting != null) {
      const currency = '₺';
      if (price is double) {
        return priceSeparator(price, symbol: currency, radix: radix);
      }
      if (price is String) {
        final parsedPrice = double.tryParse(price) ?? 0.0;
        return priceSeparator(parsedPrice, symbol: currency, radix: radix);
      }
      if (price is int) {
        return priceSeparator(price.toDouble(), symbol: currency, radix: radix);
      }
      return priceSeparator(0.0, symbol: currency, radix: radix);
    }
    // Fallback option with a default currency symbol
    else {
      const currency = '₺';
      if (price is double) {
        return priceSeparator(price, symbol: currency, radix: radix);
      }
      if (price is String) {
        final parsedPrice = double.tryParse(price) ?? 0.0;
        return priceSeparator(parsedPrice, symbol: currency, radix: radix);
      }
      if (price is int) {
        return priceSeparator(price.toDouble(), symbol: currency, radix: radix);
      }
      return priceSeparator(0.0, symbol: currency, radix: radix);
    }
  }


  static String imageContent(BuildContext context, String key) {
    final webSetting = context.read<AppSettingCubit>().settingModel;
    if (webSetting != null && webSetting.imageContent![key] != null) {
      return RemoteUrls.imageUrl(webSetting.imageContent![key]!);
    } else {
      return key;
    }
  }

  static final Map<String, String> _cachedText = {};

  static void clearCachedText() {
    _cachedText.clear();
  }

  static bool isLoggedIn(BuildContext context){
    final login = context.read<LoginBloc>();
    if(login.userInfo?.accessToken.isNotEmpty??false){
      return true;
    }else{
      return false;
    }
  }

  /// Aktif satıcı hesabı (login yanıtı / profil `is_vendor`).
  static bool isSeller(BuildContext context) {
    final login = context.read<LoginBloc>();
    final info = login.userInfo;
    if (info == null || info.accessToken.isEmpty) return false;
    if (info.isVendor == 1) return true;
    final nested = info.user.isVendor.trim();
    return nested == '1' || nested.toLowerCase() == 'true';
  }


  static Future<String> hintText(BuildContext context, String text) async {
    final tCubit = context.read<TranslateCubit>();
    final code = context.read<CurrencyCubit>().state.languages.first.langCode;

    if (_cachedText.containsKey(text)) {
      // debugPrint('cached-text $text');
      return _cachedText[text]!;
    }

    try {
      String translatedText = await tCubit.singleText(text, code);
      // debugPrint('translatedText-try $translatedText');
      _cachedText[text] = translatedText;
      return translatedText;
    } catch (e) {
      _cachedText[text] = text;
      // debugPrint('translatedText-catch $text');
      return text;
    }
  }


  static String formText(BuildContext context, String key) {
    final tCubit = context.read<TranslateCubit>();
    return tCubit.state.bottomText[key] ?? key;
  }


  static String shippingType(String type) {
    switch (type) {
      case 'base_on_weight':
        return "Shipping Rule Based on weight";
      case 'base_on_price':
        return "Shipping Rule Based on Price";
      case 'base_on_qty':
        return "Shipping Rule Based on Qty";
      default:
        return "";
    }
  }

  static String htmlTextConverter(String text) {
    var document = parse(text);
    String convertedText = parse(document.body?.text).documentElement?.text??'';
    return convertedText;
  }

  static String findDialCodeBy(String code) {
    for (var country in Countries.allCountries) {
      if (code == country['code']) {
        return country['dial_code']!.replaceAll('+', '');
      }
    }
    return '880';
  }

  static bool isMapEnable(BuildContext context) {
    final as = context.read<AppSettingCubit>();
    if (as.settingModel?.setting.mapStatus == 1) {
      return true;
    } else {
      return false;
    }
  }

  static String mapKey(BuildContext context) {
    final as = context.read<AppSettingCubit>();
    if (as.settingModel != null && as.settingModel!.setting.mapKey.isNotEmpty) {
      return as.settingModel!.setting.mapKey;
    } else {
      return '';
    }
  }

  static String findCountryDialCode(String phoneNumber, String code) {
    if (phoneNumber.isNotEmpty) {
      String number = phoneNumber.replaceAll(RegExp(r'\D'), '');

      if (code == 'code') {
        for (var country in Countries.allCountries) {
          if (number.startsWith(country['dial_code']!.replaceAll('+', ''))) {
            return country['dial_code']!.replaceAll('+', '');
          }
        }
        return '880';
      } else if (code == 'number') {
        for (var country in Countries.allCountries) {
          if (number.startsWith(country['dial_code']!.replaceAll('+', ''))) {
            final dialCodeLength =
                country['dial_code']!.replaceAll('+', '').length;
            final restOfNumber = phoneNumber.substring(dialCodeLength);
            // print('replaced-number $restOfNumber');
            return restOfNumber;
          }
        }
        return '12345678';
      } else {
        for (var country in Countries.allCountries) {
          if (number.startsWith(country['dial_code']!.replaceAll('+', ''))) {
            return country['code']!;
          }
        }
        return 'BD';
      }
    }
    return '';
  }

  static Widget verticalSpace(double space) {
    return SizedBox(height: space);
  }

  static Widget horizontalSpace(double space) {
    return SizedBox(width: space);
  }

  static Size mediaQuery(BuildContext context) => MediaQuery.sizeOf(context);

  static Color dynamicPrimaryColor(BuildContext context) {
    final color = context.read<AppSettingCubit>();
    if (color.settingModel?.setting != null) {
      // debugPrint('inside-if-bloc');
      if (color.settingModel?.setting.themeOne.isNotEmpty ?? false) {
        // debugPrint('inside-themeOne ${color.setting?.setting?.themeOneColor}');
        final themeColor =
        color.settingModel?.setting.themeOne.replaceAll('#', '0xFF');
        return Color(int.parse(themeColor ?? '0xFF7166F0'));
      } else if (color.settingModel?.setting.themeTwo.isNotEmpty ?? false) {
        // debugPrint('inside-themeTwo ${color.setting?.setting?.themeTwoColor}');
        final themeColor =
        color.settingModel?.setting.themeTwo.replaceAll('#', '0xFF');
        return Color(int.parse(themeColor ?? '0xFF7166F0'));
      } else {
        // debugPrint('inside-if-else $primaryColor');
        return primaryColor;
      }
    } else {
      // debugPrint('else-bloc $primaryColor');
      return primaryColor;
    }
  }

  static double productPrice(BuildContext context, ProductModel product) {
    final appSetting = context.read<AppSettingCubit>();
    double productPrice = 0.0;
    double flashPrice = 0.0;
    double offerPrice = 0.0;
    double mainPrice = 0.0;
    final isFlashSale = appSetting.settingModel!.flashSaleProducts
        .contains(FlashSaleProductsModel(productId: product.id));

    if (product.offerPrice != 0) {
      if (product.productVariants.isNotEmpty) {
        double p = 0.0;
        for (var i in product.productVariants) {
          if (i.activeVariantsItems.isNotEmpty) {
            p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
          }
        }
        offerPrice = p + product.offerPrice;
      } else {
        offerPrice = product.offerPrice;
      }
      productPrice = offerPrice;
    } else {
      if (product.productVariants.isNotEmpty) {
        double p = 0.0;
        for (var i in product.productVariants) {
          if (i.activeVariantsItems.isNotEmpty) {
            p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
          }
        }
        mainPrice = p + product.price;
      } else {
        mainPrice = product.price;
      }
      productPrice = mainPrice;
    }

    if (isFlashSale) {
      if (product.offerPrice != 0) {
        final discount =
            appSetting.settingModel!.flashSale.offer / 100 * offerPrice;

        flashPrice = offerPrice - discount;
      } else {
        final discount =
            appSetting.settingModel!.flashSale.offer / 100 * mainPrice;

        flashPrice = mainPrice - discount;
      }
      productPrice = flashPrice;
    }
    return productPrice;
  }

  static double cartProductRegularPrice(
      BuildContext context, CartProductModel cartProductModel) {
    double extra = 0.0;
    for (final variant in cartProductModel.variants) {
      if (variant.varientItem != null) {
        extra += variant.varientItem!.price;
      }
    }
    return extra + cartProductModel.product.price;
  }

  static double cartProductPrice(BuildContext context, CartProductModel cartProductModel) {
    final appSetting = context.read<AppSettingCubit>();
    double productPrice = 0.0;
    double flashPrice = 0.0;
    double offerPrice = 0.0;
    double mainPrice = 0.0;
    final isFlashSale = appSetting.settingModel!.flashSaleProducts.contains(
        FlashSaleProductsModel(productId: cartProductModel.product.id));

    if (cartProductModel.product.offerPrice != 0) {
      if (cartProductModel.variants.isNotEmpty) {
        double p = 0.0;

        for (var i in cartProductModel.variants) {
          // print("vItem1: $i");
          if (i.varientItem != null) {
            p += i.varientItem!.price;
          }
        }
        offerPrice = p + cartProductModel.product.offerPrice;
      } else {
        offerPrice = cartProductModel.product.offerPrice;
      }
      productPrice = offerPrice;
    } else {
      if (cartProductModel.variants.isNotEmpty) {
        double p = 0.0;
        for (var i in cartProductModel.variants) {
          // print("vItem2: $i");
          if (i.varientItem != null) {
            p += i.varientItem!.price;
          }
        }
        mainPrice = p + cartProductModel.product.price;
      } else {
        mainPrice = cartProductModel.product.price;
      }
      productPrice = mainPrice;
    }

    if (isFlashSale) {
      if (cartProductModel.product.offerPrice != 0) {
        final discount =
            appSetting.settingModel!.flashSale.offer / 100 * offerPrice;

        flashPrice = offerPrice - discount;
      } else {
        final discount =
            appSetting.settingModel!.flashSale.offer / 100 * mainPrice;

        flashPrice = mainPrice - discount;
      }
      productPrice = flashPrice;
    }
    return productPrice;
  }

  static double cartLineTotal(
      BuildContext context, CartProductModel cartProductModel) {
    return cartProductPrice(context, cartProductModel) *
        cartProductModel.qty.toDouble();
  }

  static double guestCartLineTotal(
      BuildContext context, GustCartProduct? product) {
    return guestCart(context, product) * (product?.qty ?? 1).toDouble();
  }


  static void successPayment(BuildContext context){
    final detail = context.read<ProductDetailsCubit>();
    if(Utils.isLoggedIn(context)){
      debugPrint('auth-user and go order page');
      Navigator.pushNamedAndRemoveUntil(context, RouteNames.orderScreen,
              (route) {
            if (route.settings.name == RouteNames.mainPage) {
              return true;
            } else {
              return false;
            }
          },arguments: true);
    }else{
      debugPrint('guest-user and go order detail page');
      Navigator.pushNamedAndRemoveUntil(context, RouteNames.singleOrderScreen,
              (route) {
            if (route.settings.name == RouteNames.mainPage) {
              return true;
            } else {
              return false;
            }
          });
    }

    Future.delayed(const Duration(milliseconds: 1200),(){
      detail.clearGuestProduct();
    });
  }

  static double guestCart(BuildContext context, GustCartProduct? product) {
    final appSetting = context.read<AppSettingCubit>();
    double productPrice = 0.0;
    double flashPrice = 0.0;
    double offerPrice = 0.0;
    double mainPrice = 0.0;
    final isFlashSale = appSetting.settingModel!.flashSaleProducts.contains(FlashSaleProductsModel(productId: product?.product?.id??0));

    if (product?.product?.offerPrice != 0.0) {

      if (product?.variants?.isNotEmpty??false) {
        double p = 0.0;

        for (int i = 0; i < (product?.variants?.length ?? 0); i++) {
          final item = product?.variants?[i];
          if(item?.variantItem?.price != 0.0){
            // debugPrint('inside-variant ${item?.variantItem?.price}');
            p += item?.variantItem?.price??0.0;
          }
        }
        offerPrice = p + (product?.product?.offerPrice??0.0);
      } else {
        offerPrice = product?.product?.offerPrice??0.0;
      }
      productPrice = offerPrice;
    } else {
      if (product?.variants?.isNotEmpty??false) {
        double p = 0.0;
        for (int i = 0; i < (product?.variants?.length ?? 0); i++) {
          final item = product?.variants?[i];
          if(item?.variantItem?.price != 0.0){
            // debugPrint('inside-variant ${item?.variantItem?.price}');
            p += item?.variantItem?.price??0.0;
          }
        }

        mainPrice = p + (product?.product?.price??0.0);
      } else {
        mainPrice = product?.product?.price??0.0;
      }
      productPrice = mainPrice;
    }

    if (isFlashSale) {
      if (product?.product?.offerPrice != 0.0) {
        // debugPrint('flash-exist ${appSetting.settingModel?.flashSale.offer}');
        final discount = appSetting.settingModel?.flashSale.offer??1.0 / 100.0 * offerPrice;

        flashPrice = offerPrice - discount;
        // debugPrint('flash-offer-price $flashPrice');
      } else {
        final discount = appSetting.settingModel?.flashSale.offer??1.0 / 100.0 * mainPrice;

        flashPrice = mainPrice - discount;
        // debugPrint('flash-price $flashPrice');
      }
      // debugPrint('flash-price $flashPrice');
      productPrice = flashPrice;
      // debugPrint('product-price $productPrice');
    }
    return productPrice;
  }

  static double guestCartRegularPrice(
      BuildContext context, GustCartProduct? product) {
    double extra = 0.0;
    final variants = product?.variants ?? [];
    for (final item in variants) {
      extra += item.variantItem?.price ?? 0.0;
    }
    return extra + (product?.product?.price ?? 0.0);
  }

  static String calculatePrice(CartResponseModel cartResponseModel) {
    double price = 0.0;
    for (var i = 0; i < cartResponseModel.cartProducts.length; i++) {
      if (cartResponseModel.cartProducts[i].product.offerPrice != 0) {
        final p = cartResponseModel.cartProducts[i].product.offerPrice;
        final q = cartResponseModel.cartProducts[i].product.qty;
        price = p * q;
      } else {
        final p = cartResponseModel.cartProducts[i].product.price;
        final q = cartResponseModel.cartProducts[i].product.qty;
        price = p * q;
      }
    }
    String price0 = price.toStringAsPrecision(2);
    debugPrint("Price: $price0");

    return "\$$price0";
  }

  static String formatDate(var date) {
    late DateTime dateTime;
    if (date is String) {
      dateTime = DateTime.parse(date);
    } else {
      dateTime = date;
    }

    return DateFormat.yMd().format(dateTime.toLocal());
  }

  static String numberCompact(num number) =>
      NumberFormat.compact().format(number);

  static String timeAgo(String? time) {
    try {
      if (time == null) return '';
      return timeago.format(
        DateTime.parse(time).toLocal(),
        locale: 'tr',
      );
    } catch (e) {
      return '';
    }
  }

  /// Yorum sahibi adını gizler: S**** C****
  static String maskReviewerName(String? fullName) {
    const maskLength = 4;
    final mask = '*' * maskLength;

    String maskPart(String part) {
      final trimmed = part.trim();
      if (trimmed.isEmpty) return '';
      return '${trimmed[0].toUpperCase()}$mask';
    }

    final raw = (fullName ?? '').trim();
    if (raw.isEmpty) return 'K$mask';

    final parts = raw.split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.length == 1) return maskPart(parts.first);

    return '${maskPart(parts.first)} ${maskPart(parts.last)}';
  }

  static double toDouble(String? number) {
    try {
      if (number == null) return 0;
      return double.tryParse(number) ?? 0;
    } catch (e) {
      return 0;
    }
  }

  static double? stringToDouble(String input) {
    final regex = RegExp(r'\d+(\.\d+)?');
    final match = regex.firstMatch(input);
    if (match != null) {
      return double.tryParse(match.group(0)!);
    }
    return 0.0;
  }

  static String dorpPricePercentage(double priceS, double offerPriceS) {
    // double price = Utils.toDouble(priceS);
    // double offerPrice = Utils.toDouble(offerPriceS);
    double dropPrice = (priceS - offerPriceS) * 100;

    double percentage = dropPrice / priceS;
    return "-${percentage.toStringAsFixed(1)}%";
  }

  static double toInt(String? number) {
    try {
      if (number == null) return 0;
      return double.tryParse(number) ?? 0;
    } catch (e) {
      return 0;
    }
  }

  static double hSize(double size) {
    return size;
  }

  static double vSize(double size) {
    return size;
  }

  static EdgeInsets symmetric({double h = 20.0, v = 0.0}) {
    return EdgeInsets.symmetric(
        horizontal: Utils.hPadding(size: h), vertical: Utils.vPadding(size: v));
  }

  static double radius(double radius) {
    return radius;
  }

  static BorderRadius borderRadius({double r = 10.0}) {
    return BorderRadius.circular(Utils.radius(r));
  }

  static EdgeInsets all({double value = 0.0}) {
    return EdgeInsets.all(value);
  }

  static EdgeInsets only({
    double left = 0.0,
    double top = 0.0,
    double right = 0.0,
    double bottom = 0.0,
  }) {
    return EdgeInsets.only(left: left, top: top, right: right, bottom: bottom);
  }

  static double vPadding({double size = 20.0}) {
    return size;
  }

  static double hPadding({double size = 20.0}) {
    return size;
  }

  static Future<DateTime?> selectDate(BuildContext context) => showDatePicker(
        context: context,
        initialDate: _selectedDate,
        firstDate: DateTime(1990, 1),
        lastDate: DateTime(2050),
      );

  static Future<TimeOfDay?> selectTime(BuildContext context) =>
      showTimePicker(context: context, initialTime: _initialTime);

  static loadingDialog(
    BuildContext context, {
    bool barrierDismissible = false,
  }) {
    if (_loadingDialogDepth > 0) return;
    _loadingDialogDepth++;
    showDialog(
      context: context,
      barrierDismissible: barrierDismissible,
      barrierColor: Colors.black.withValues(alpha: 0.35),
      builder: (BuildContext context) {
        return const Center(
          child: Material(
            type: MaterialType.transparency,
            child: SeyfibabaLoadingCard(),
          ),
        );
      },
    ).whenComplete(() {
      _loadingDialogDepth = 0;
    });
  }

  static Future showCustomDialog(
    BuildContext context, {
    Widget? child,
    bool barrierDismissible = false,
    EdgeInsets? padding,
  }) {
    return showDialog(
      context: context,
      barrierDismissible: barrierDismissible,
      builder: (BuildContext context) {
        final p = padding ?? Utils.symmetric();
        return Dialog(
          insetPadding: p,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          child: child,
        );
      },
    );
  }

  static void appInfoDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          // insetPadding: Utils.all(),
          contentPadding: Utils.symmetric(h: 30.0, v: 20.0),
          title: CustomText(
            text: Language.appInfo.capitalizeByWord(),
            fontSize: 16.0,
            fontWeight: FontWeight.w500,
            color: blackColor,
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              InfoLabel(
                  label: Language.name.capitalizeByWord(),
                  text: KStrings.appName),
              const SizedBox(height: 2.0),
              InfoLabel(
                  label: Language.version.capitalizeByWord(),
                  text: KStrings.appVersion),
              const SizedBox(height: 6),
              InfoLabel(
                  label: Language.developedBy.capitalizeByWord(),
                  text: "Seyfibaba"),
            ],
          ),
          actions: [
            TextButton(
                onPressed: () => Navigator.pop(context),
                child: CustomText(text: Language.dismiss.capitalizeByWord()))
          ],
        );
      },
    );
  }

  static int calCulateMaxDays(String startDate, String endDate) {
    final startDateTime = DateTime.parse(startDate);
    final endDateTime = DateTime.parse(endDate);
    final totalDays = endDateTime.difference(startDateTime).inDays;

    return totalDays >= 0 ? totalDays : 0;
  }

  static int calCulateRemainingHours(String startDate, String endDate) {
    final startDateTime =
        DateTime.now().toLocal().subtract(const Duration(days: 9));
    final endDateTime = DateTime.parse(endDate).toLocal();
    final totalHours = endDateTime.difference(startDateTime).inHours;

    if (totalHours < 0) return 24;

    return 24 - (totalHours % 24);
  }

  static int calCulateRemainingMinutes(String startDate, String endDate) {
    final startDateTime = DateTime.now().toLocal();
    final endDateTime = DateTime.parse(endDate).toLocal();
    final totalMinutes = endDateTime.difference(startDateTime).inMinutes;

    if (totalMinutes < 0) return 60;

    return 60 - (totalMinutes % (24 * 60));
  }

  static int calCulateRemainingDays(String startDate, String endDate) {
    final endDateTime = DateTime.parse(endDate).toLocal();
    final totalDaysGone =
        endDateTime.difference(DateTime.now().toLocal()).inDays;
    final totalDays = calCulateMaxDays(startDate, endDate);
    return totalDaysGone >= 0 ? totalDays - totalDaysGone : totalDays;
  }

  /// Checks if string is a valid username.
  static bool isUsername(String s) =>
      hasMatch(s, r'^[a-zA-Z0-9][a-zA-Z0-9_.]+[a-zA-Z0-9]$');

  /// Checks if string is Currency.
  static bool isCurrency(String s) => hasMatch(s,
      r'^(S?\$|\₩|Rp|\¥|\€|\₹|\₽|fr|R\$|R)?[ ]?[-]?([0-9]{1,3}[,.]([0-9]{3}[,.])*[0-9]{3}|[0-9]+)([,.][0-9]{1,2})?( ?(USD?|AUD|NZD|CAD|CHF|GBP|CNY|EUR|JPY|IDR|MXN|NOK|KRW|TRY|INR|RUB|BRL|ZAR|SGD|MYR))?$');

  /// Checks if string is phone number.
  static bool isPhoneNumber(String s) {
    if (s.length > 16 || s.length < 9) return false;
    return hasMatch(s, r'^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$');
  }

  /// Checks if string is email.
  static bool isEmail(String s) => hasMatch(s,
      r'^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$');

  static bool hasMatch(String? value, String pattern) {
    return (value == null) ? false : RegExp(pattern).hasMatch(value);
  }

  static bool isEmpty(dynamic value) {
    if (value is String) {
      return value.toString().trim().isEmpty;
    }
    if (value is Iterable || value is Map) {
      return value.isEmpty ?? false;
    }
    return false;
  }

  static void errorSnackBar(BuildContext context, String errorMsg,
      [Color textColor = redColor, int duration = 2500]) {
    final text = Language.localizeMessage(errorMsg);
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          duration: Duration(milliseconds: duration),
          content: CustomText(
              text: text,
              fontSize: 15.0,
              fontWeight: FontWeight.w500,
              color: textColor),
        ),
      );
  }

  static void showSnackBar(BuildContext context, String errorMsg,
      [Color textColor = whiteColor, int duration = 2500]) {
    final text = Language.localizeMessage(errorMsg);
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          duration: Duration(milliseconds: duration),
          content: CustomText(
              text: text,
              fontSize: 15.0,
              fontWeight: FontWeight.w500,
              color: textColor),
        ),
      );
  }

  static void showSnackBarWithAction(
      BuildContext context, String msg, VoidCallback onPress,
      [Color textColor = Colors.black]) {
    final snackBar = SnackBar(
      content: CustomText(text: msg, color: textColor),
      action: SnackBarAction(
        label: Language.active.capitalizeByWord(),
        onPressed: onPress,
      ),
    );
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(snackBar);
  }

  static void showSnackBarWithLogin(
      BuildContext context, String msg, VoidCallback _,
      [Color textColor = Colors.white]) {
    // Capture navigator/messenger while context is still mounted.
    // SnackBarAction can fire after the calling State is disposed.
    final navigator = Navigator.of(context, rootNavigator: true);
    final messenger = ScaffoldMessenger.of(context);
    final snackBar = SnackBar(
      content: CustomText(text: msg, color: textColor),
      action: SnackBarAction(
        label: Language.login,
        onPressed: () {
          navigator.pushNamedAndRemoveUntil(
            RouteNames.authenticationScreen,
            (route) => false,
          );
        },
      ),
    );
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(snackBar);
  }

  static double getRating(List<DetailsProductReviewModel> productReviews) {
    if (productReviews.isEmpty) return 0;

    double rating = productReviews.fold(
        0.0,
        (previousValue, element) =>
            previousValue + Utils.toDouble(element.rating.toString()));
    rating = rating / productReviews.length;
    return rating;
  }

  /// loadingDialog ile açılan overlay sayacı.
  /// ModalRoute.isCurrent tahmini, navigasyon sırasında yanlış pozitif verip
  /// "Bad state: No element" / navigator lock üretiyordu.
  static int _loadingDialogDepth = 0;

  static void closeDialog(BuildContext context) {
    if (_loadingDialogDepth <= 0) return;
    if (!context.mounted) {
      _loadingDialogDepth = 0;
      return;
    }
    try {
      final navigator = Navigator.of(context, rootNavigator: true);
      if (navigator.canPop()) {
        navigator.pop();
      } else {
        _loadingDialogDepth = 0;
      }
    } catch (_) {
      _loadingDialogDepth = 0;
    }
  }

  static void closeKeyBoard(BuildContext context) {
    FocusScope.of(context).requestFocus(FocusNode());
  }

  static Future<String?> pickSingleImage() async {
    final ImagePicker picker = ImagePicker();
    // Pick an image
    final XFile? image = await picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      return image.path;
    }
    return null;
  }

  static String orderStatus(String orderStatus) {
    switch (orderStatus) {
      case '0':
        return Language.pending.capitalizeByWord();
      case '1':
        return Language.progress.capitalizeByWord();
      case '2':
        return Language.delivered.capitalizeByWord();
      case '3':
        return Language.completed.capitalizeByWord();
      // case '4':
      //   return 'Declined';
      default:
        return Language.declined.capitalizeByWord();
    }
  }
}

class InfoLabel extends StatelessWidget {
  const InfoLabel({super.key, this.label, this.text});

  final String? label;
  final String? text;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        CustomText(text: "${label!} : ", color: blackColor),
        Utils.horizontalSpace(8.0),
        CustomText(
            text: text ?? '',
            color: blackColor,
            fontWeight: FontWeight.w600,
            fontSize: 16.0),
      ],
    );
  }
}

