import 'package:equatable/equatable.dart';

class BuyerPersonalizationData extends Equatable {
  final String shopName;
  final String businessType;
  final String businessTypeOther;
  final String businessStatus;
  final DateTime? personalizationCompletedAt;
  final DateTime? personalizationSkippedAt;
  final bool shouldShowPersonalization;

  const BuyerPersonalizationData({
    this.shopName = '',
    this.businessType = '',
    this.businessTypeOther = '',
    this.businessStatus = '',
    this.personalizationCompletedAt,
    this.personalizationSkippedAt,
    this.shouldShowPersonalization = false,
  });

  bool get isCompleted => personalizationCompletedAt != null;

  bool get shouldPrompt {
    if (isCompleted) return false;
    if (shouldShowPersonalization) return true;
    if (personalizationSkippedAt == null) return true;
    return DateTime.now().isAfter(
      personalizationSkippedAt!.add(const Duration(days: 1)),
    );
  }

  factory BuyerPersonalizationData.fromPersonInfo(
    Map<String, dynamic> map, {
    bool shouldShow = false,
  }) {
    return BuyerPersonalizationData(
      shopName: map['shop_name']?.toString() ?? '',
      businessType: map['business_type']?.toString() ?? '',
      businessTypeOther: map['business_type_other']?.toString() ?? '',
      businessStatus: map['business_status']?.toString() ?? '',
      personalizationCompletedAt: _parseDate(map['personalization_completed_at']),
      personalizationSkippedAt: _parseDate(map['personalization_skipped_at']),
      shouldShowPersonalization: shouldShow,
    );
  }

  static DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    final text = value.toString().trim();
    if (text.isEmpty) return null;
    return DateTime.tryParse(text);
  }

  Map<String, String> toSubmitMap() {
    return {
      'shop_name': shopName.trim(),
      'business_type': businessType,
      if (businessType == 'other') 'business_type_other': businessTypeOther.trim(),
      'business_status': businessStatus,
    };
  }

  @override
  List<Object?> get props => [
        shopName,
        businessType,
        businessTypeOther,
        businessStatus,
        personalizationCompletedAt,
        personalizationSkippedAt,
        shouldShowPersonalization,
      ];
}
