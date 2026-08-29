import 'dart:convert';

import 'package:equatable/equatable.dart';

class SettingModel extends Equatable {
  final String logo;
  final String favicon;
  final String timezone;
  final String defaultPhoneCode;
  final int enableUserRegister;
  final int phoneNumberRequired;
  final int enableMultivendor;
  final String topBarEmail;
  final String topBarPhone;
  final String currencyName;
  final String currencyIcon;
  final String sellerCondition;
  final String themeOne;
  final String themeTwo;
  final String mobileHubBgTop;
  final String mobileHubBgBottom;
  final String mobileHubFeatureStart;
  final String mobileHubFeatureEnd;
  final String mobileHubShopImage;
  final String mobileHubCrmImage;
  final String mobileHubSecondhandImage;
  final String mobileOnboardingBg;
  final String mobileOnboardingImage1;
  final String mobileOnboardingImage2;
  final String mobileOnboardingImage3;
  final String mapKey;
  final int mapStatus;
  final double bankTransferDiscountPercent;
  final String bankTransferInfo;

  const SettingModel({
    required this.logo,
    required this.favicon,
    required this.timezone,
    required this.defaultPhoneCode,
    required this.enableUserRegister,
    required this.phoneNumberRequired,
    required this.enableMultivendor,
    required this.topBarEmail,
    required this.topBarPhone,
    required this.currencyName,
    required this.currencyIcon,
    required this.sellerCondition,
    required this.themeOne,
    required this.themeTwo,
    this.mobileHubBgTop = '#FFFBF0',
    this.mobileHubBgBottom = '#F8F8F8',
    this.mobileHubFeatureStart = '#FFF3CC',
    this.mobileHubFeatureEnd = '#FFBB38',
    this.mobileHubShopImage = '',
    this.mobileHubCrmImage = '',
    this.mobileHubSecondhandImage = '',
    this.mobileOnboardingBg = '#F4F0FA',
    this.mobileOnboardingImage1 = '',
    this.mobileOnboardingImage2 = '',
    this.mobileOnboardingImage3 = '',
    required this.mapKey,
    required this.mapStatus,
    this.bankTransferDiscountPercent = 3,
    this.bankTransferInfo = '',
  });

  SettingModel copyWith({
    String? logo,
    String? favicon,
    String? timezone,
    String? defaultPhoneCode,
    int? enableUserRegister,
    int? phoneNumberRequired,
    int? enableMultivendor,
    String? topBarEmail,
    String? topBarPhone,
    String? currencyName,
    String? currencyIcon,
    String? sellerCondition,
    String? themeOne,
    String? themeTwo,
    String? mobileHubBgTop,
    String? mobileHubBgBottom,
    String? mobileHubFeatureStart,
    String? mobileHubFeatureEnd,
    String? mobileHubShopImage,
    String? mobileHubCrmImage,
    String? mobileHubSecondhandImage,
    String? mobileOnboardingBg,
    String? mobileOnboardingImage1,
    String? mobileOnboardingImage2,
    String? mobileOnboardingImage3,
    String? mapKey,
    int? mapStatus,
    double? bankTransferDiscountPercent,
    String? bankTransferInfo,
  }) {
    return SettingModel(
      logo: logo ?? this.logo,
      favicon: favicon ?? this.favicon,
      timezone: timezone ?? this.timezone,
      defaultPhoneCode: defaultPhoneCode ?? this.defaultPhoneCode,
      enableUserRegister: enableUserRegister ?? this.enableUserRegister,
      phoneNumberRequired: phoneNumberRequired ?? this.phoneNumberRequired,
      enableMultivendor: enableMultivendor ?? this.enableMultivendor,
      topBarEmail: topBarEmail ?? this.topBarEmail,
      topBarPhone: topBarPhone ?? this.topBarPhone,
      currencyName: currencyName ?? this.currencyName,
      currencyIcon: currencyIcon ?? this.currencyIcon,
      sellerCondition: sellerCondition ?? this.sellerCondition,
      themeOne: themeOne ?? this.themeOne,
      themeTwo: themeTwo ?? this.themeTwo,
      mobileHubBgTop: mobileHubBgTop ?? this.mobileHubBgTop,
      mobileHubBgBottom: mobileHubBgBottom ?? this.mobileHubBgBottom,
      mobileHubFeatureStart:
          mobileHubFeatureStart ?? this.mobileHubFeatureStart,
      mobileHubFeatureEnd: mobileHubFeatureEnd ?? this.mobileHubFeatureEnd,
      mobileHubShopImage: mobileHubShopImage ?? this.mobileHubShopImage,
      mobileHubCrmImage: mobileHubCrmImage ?? this.mobileHubCrmImage,
      mobileHubSecondhandImage:
          mobileHubSecondhandImage ?? this.mobileHubSecondhandImage,
      mobileOnboardingBg: mobileOnboardingBg ?? this.mobileOnboardingBg,
      mobileOnboardingImage1:
          mobileOnboardingImage1 ?? this.mobileOnboardingImage1,
      mobileOnboardingImage2:
          mobileOnboardingImage2 ?? this.mobileOnboardingImage2,
      mobileOnboardingImage3:
          mobileOnboardingImage3 ?? this.mobileOnboardingImage3,
      mapKey: mapKey ?? this.mapKey,
      mapStatus: mapStatus ?? this.mapStatus,
      bankTransferDiscountPercent:
          bankTransferDiscountPercent ?? this.bankTransferDiscountPercent,
      bankTransferInfo: bankTransferInfo ?? this.bankTransferInfo,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'logo': logo,
      'favicon': favicon,
      'timezone': timezone,
      'default_phone_code': defaultPhoneCode,
      'enable_user_register': enableUserRegister,
      'phone_number_required': phoneNumberRequired,
      'enable_multivendor': enableMultivendor,
      'topbar_email': topBarEmail,
      'topbar_phone': topBarPhone,
      'currency_name': currencyName,
      'currency_icon': currencyIcon,
      'seller_condition': sellerCondition,
      'theme_one': themeOne,
      'theme_two': themeTwo,
      'mobile_hub_bg_top': mobileHubBgTop,
      'mobile_hub_bg_bottom': mobileHubBgBottom,
      'mobile_hub_feature_start': mobileHubFeatureStart,
      'mobile_hub_feature_end': mobileHubFeatureEnd,
      'mobile_hub_shop_image': mobileHubShopImage,
      'mobile_hub_crm_image': mobileHubCrmImage,
      'mobile_hub_secondhand_image': mobileHubSecondhandImage,
      'mobile_onboarding_bg': mobileOnboardingBg,
      'mobile_onboarding_image_1': mobileOnboardingImage1,
      'mobile_onboarding_image_2': mobileOnboardingImage2,
      'mobile_onboarding_image_3': mobileOnboardingImage3,
    };
  }

  factory SettingModel.fromMap(Map<String, dynamic> map) {
    return SettingModel(
      logo: map['logo'] ?? '',
      favicon: map['favicon'] ?? '',
      defaultPhoneCode: map['default_phone_code'] ?? 'BD',
      timezone: map['timezone'] ?? '',
      enableUserRegister: map['enable_user_register'] != null
          ? int.parse(map['enable_user_register'].toString())
          : 0,
      phoneNumberRequired: map['phone_number_required'] != null
          ? int.parse(map['phone_number_required'].toString())
          : 0,
      enableMultivendor: map['enable_multivendor'] != null
          ? int.parse(map['enable_multivendor'].toString())
          : 0,
      topBarEmail: map['topbar_email'] ?? '',
      topBarPhone: map['topbar_phone'] ?? '',
      currencyName: map['currency_name'] ?? '',
      currencyIcon: map['currency_icon'] ?? '',
      sellerCondition: map['seller_condition'] ?? '',
      themeOne: map['theme_one'] ?? '',
      themeTwo: map['theme_two'] ?? '',
      mobileHubBgTop: map['mobile_hub_bg_top']?.toString() ?? '#FFFBF0',
      mobileHubBgBottom: map['mobile_hub_bg_bottom']?.toString() ?? '#F8F8F8',
      mobileHubFeatureStart:
          map['mobile_hub_feature_start']?.toString() ?? '#FFF3CC',
      mobileHubFeatureEnd:
          map['mobile_hub_feature_end']?.toString() ?? '#FFBB38',
      mobileHubShopImage: map['mobile_hub_shop_image']?.toString() ?? '',
      mobileHubCrmImage: map['mobile_hub_crm_image']?.toString() ?? '',
      mobileHubSecondhandImage:
          map['mobile_hub_secondhand_image']?.toString() ?? '',
      mobileOnboardingBg:
          map['mobile_onboarding_bg']?.toString() ?? '#F4F0FA',
      mobileOnboardingImage1:
          map['mobile_onboarding_image_1']?.toString() ?? '',
      mobileOnboardingImage2:
          map['mobile_onboarding_image_2']?.toString() ?? '',
      mobileOnboardingImage3:
          map['mobile_onboarding_image_3']?.toString() ?? '',
      mapKey: map['map_key'] ?? '',
      mapStatus: map['map_status'] != null
          ? int.parse(map['map_status'].toString())
          : 0,
      bankTransferDiscountPercent: map['bank_transfer_discount_percent'] != null
          ? double.parse(map['bank_transfer_discount_percent'].toString())
          : 3,
      bankTransferInfo: map['bank_transfer_info']?.toString() ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory SettingModel.fromJson(String source) =>
      SettingModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props {
    return [
      logo,
      favicon,
      timezone,
      defaultPhoneCode,
      enableUserRegister,
      phoneNumberRequired,
      enableMultivendor,
      topBarEmail,
      topBarPhone,
      currencyName,
      currencyIcon,
      sellerCondition,
      themeOne,
      themeTwo,
      mobileHubBgTop,
      mobileHubBgBottom,
      mobileHubFeatureStart,
      mobileHubFeatureEnd,
      mobileHubShopImage,
      mobileHubCrmImage,
      mobileHubSecondhandImage,
      mobileOnboardingBg,
      mobileOnboardingImage1,
      mobileOnboardingImage2,
      mobileOnboardingImage3,
      mapKey,
      mapStatus,
      bankTransferDiscountPercent,
      bankTransferInfo,
    ];
  }
}
