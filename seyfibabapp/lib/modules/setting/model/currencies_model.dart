// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';

class CurrenciesModel extends Equatable {
  final int id;
  final String currencyName;
  final String countryCode;
  final String currencyCode;
  final String currencyIcon;
  final String isDefault;
  final double currencyRate;
  final String currencyPosition;
  final int status;
  final String createdAt;
  final String updatedAt;

  const CurrenciesModel({
    required this.id,
    required this.currencyName,
    required this.countryCode,
    required this.currencyCode,
    required this.currencyIcon,
    required this.isDefault,
    required this.currencyRate,
    required this.currencyPosition,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
  });

  CurrenciesModel copyWith({
    int? id,
    String? currencyName,
    String? countryCode,
    String? currencyCode,
    String? currencyIcon,
    String? isDefault,
    double? currencyRate,
    String? currencyPosition,
    int? status,
    String? createdAt,
    String? updatedAt,
  }) {
    return CurrenciesModel(
      id: id ?? this.id,
      currencyName: currencyName ?? this.currencyName,
      countryCode: countryCode ?? this.countryCode,
      currencyCode: currencyCode ?? this.currencyCode,
      currencyIcon: currencyIcon ?? this.currencyIcon,
      isDefault: isDefault ?? this.isDefault,
      currencyRate: currencyRate ?? this.currencyRate,
      currencyPosition: currencyPosition ?? this.currencyPosition,
      status: status ?? this.status,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'currency_name': currencyName,
      'country_code': countryCode,
      'currency_code': currencyCode,
      'currency_icon': currencyIcon,
      'is_default': isDefault,
      'currency_rate': currencyRate,
      'currency_position': currencyPosition,
      'status': status,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory CurrenciesModel.fromMap(Map<String, dynamic> map) {
    return CurrenciesModel(
      id: map['id'] ?? 0,
      currencyName: map['currency_name'] ?? '',
      countryCode: map['country_code'] ?? '',
      currencyCode: map['currency_code'] ?? '',
      currencyIcon: map['currency_icon'] ?? '',
      isDefault: map['is_default'] ?? '',
      currencyRate: map['currency_rate'] != null
          ? double.parse(map['currency_rate'].toString())
          : 0.0,
      currencyPosition: map['currency_position'] ?? '',
      status: map['status'] != null ? int.parse(map['status'].toString()) : 0,
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory CurrenciesModel.fromJson(String source) =>
      CurrenciesModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props {
    return [
      id,
      currencyName,
      countryCode,
      currencyCode,
      currencyIcon,
      isDefault,
      currencyRate,
      currencyPosition,
      status,
      createdAt,
      updatedAt,
    ];
  }
}
