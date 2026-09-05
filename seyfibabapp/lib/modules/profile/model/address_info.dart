import 'dart:convert';

import 'package:equatable/equatable.dart';

class AddressInfo extends Equatable {
  final int id;
  final int userId;
  final String name;
  final String email;
  final String phone;
  final String address;
  final String type;
  final int countryId;
  final int stateId;
  final int cityId;
  final int defaultShipping;
  final int defaultBilling;
  final double latitude;
  final double longitude;
  final String createdAt;
  final String updatedAt;
  final String invoiceType;
  final String tcIdentity;
  final String taxNumber;
  final String taxOffice;
  final String companyName;
  final bool isEInvoice;
  final String zipCode;
  final String neighborhood;

  const AddressInfo({
    required this.id,
    required this.userId,
    required this.name,
    required this.email,
    required this.phone,
    required this.address,
    required this.type,
    required this.countryId,
    required this.stateId,
    required this.cityId,
    required this.defaultShipping,
    required this.defaultBilling,
    required this.latitude,
    required this.longitude,
    required this.createdAt,
    required this.updatedAt,
    this.invoiceType = 'individual',
    this.tcIdentity = '',
    this.taxNumber = '',
    this.taxOffice = '',
    this.companyName = '',
    this.isEInvoice = false,
    this.zipCode = '',
    this.neighborhood = '',
  });

  AddressInfo copyWith({
    int? id,
    int? userId,
    String? name,
    String? email,
    String? phone,
    String? address,
    String? type,
    int? countryId,
    int? stateId,
    int? cityId,
    int? defaultShipping,
    int? defaultBilling,
    double? latitude,
    double? longitude,
    String? createdAt,
    String? updatedAt,
    String? invoiceType,
    String? tcIdentity,
    String? taxNumber,
    String? taxOffice,
    String? companyName,
    bool? isEInvoice,
    String? zipCode,
    String? neighborhood,
  }) {
    return AddressInfo(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      name: name ?? this.name,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      address: address ?? this.address,
      type: type ?? this.type,
      countryId: countryId ?? this.countryId,
      stateId: stateId ?? this.stateId,
      cityId: cityId ?? this.cityId,
      defaultShipping: defaultShipping ?? this.defaultShipping,
      defaultBilling: defaultBilling ?? this.defaultBilling,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      invoiceType: invoiceType ?? this.invoiceType,
      tcIdentity: tcIdentity ?? this.tcIdentity,
      taxNumber: taxNumber ?? this.taxNumber,
      taxOffice: taxOffice ?? this.taxOffice,
      companyName: companyName ?? this.companyName,
      isEInvoice: isEInvoice ?? this.isEInvoice,
      zipCode: zipCode ?? this.zipCode,
      neighborhood: neighborhood ?? this.neighborhood,
    );
  }

  Map<String, dynamic> toMap() {
    final result = <String, dynamic>{};

    result.addAll({'id': id});
    result.addAll({'user_id': userId});
    result.addAll({'name': name});
    result.addAll({'email': email});
    result.addAll({'phone': phone});
    result.addAll({'address': address});
    result.addAll({'type': type});
    result.addAll({'country_id': countryId});
    result.addAll({'state_id': stateId});
    result.addAll({'city_id': cityId});
    result.addAll({'default_shipping': defaultShipping});
    result.addAll({'default_billing': defaultBilling});
    result.addAll({'created_at': createdAt});
    result.addAll({'updated_at': updatedAt});
    result.addAll({'invoice_type': invoiceType});
    result.addAll({'tc_identity': tcIdentity});
    result.addAll({'tax_number': taxNumber});
    result.addAll({'tax_office': taxOffice});
    result.addAll({'company_name': companyName});
    result.addAll({'is_e_invoice': isEInvoice ? 1 : 0});
    result.addAll({'zip_code': zipCode});
    result.addAll({'neighborhood': neighborhood});

    return result;
  }

  factory AddressInfo.fromMap(Map<String, dynamic> map) {
    return AddressInfo(
      id: map['id']?.toInt() ?? 0,
      userId: map['user_id'] != null ? int.parse(map['user_id'].toString()) : 0,
      name: map['name'] ?? '',
      email: map['email'] ?? '',
      phone: map['phone'] ?? '',
      address: map['address'] ?? '',
      type: map['type'] ?? '',
      countryId: map['country_id'] != null
          ? int.parse(map['country_id'].toString())
          : 0,
      stateId:
          map['state_id'] != null ? int.parse(map['state_id'].toString()) : 0,
      cityId: map['city_id'] != null ? int.parse(map['city_id'].toString()) : 0,
      defaultShipping: map['default_shipping'] != null
          ? int.parse(map['default_shipping'].toString())
          : 0,
      defaultBilling: map['default_billing'] != null
          ? int.parse(map['default_billing'].toString())
          : 0,
      latitude: map['latitude'] != null
          ? double.parse(map['latitude'].toString())
          : 0.0,
      longitude: map['longitude'] != null
          ? double.parse(map['longitude'].toString())
          : 0.0,
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
      invoiceType: map['invoice_type']?.toString() ?? 'individual',
      tcIdentity: map['tc_identity']?.toString() ?? '',
      taxNumber: map['tax_number']?.toString() ?? '',
      taxOffice: map['tax_office']?.toString() ?? '',
      companyName: map['company_name']?.toString() ?? '',
      isEInvoice: map['is_e_invoice'] == true ||
          map['is_e_invoice'] == 1 ||
          map['is_e_invoice']?.toString() == '1',
      zipCode: map['zip_code']?.toString() ??
          map['postal_code']?.toString() ??
          '',
      neighborhood: map['neighborhood']?.toString() ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory AddressInfo.fromJson(String source) =>
      AddressInfo.fromMap(json.decode(source));

  @override
  String toString() {
    return 'AddressInfo(id: $id, user_id: $userId, name: $name, email: $email, phone: $phone, address: $address, type: $type, country_id: $countryId, state_id: $stateId, city_id: $cityId,default_shipping: $defaultShipping,  default_billing: $defaultBilling, created_at: $createdAt, updated_at: $updatedAt)';
  }

  @override
  List<Object?> get props {
    return [
      id,
      userId,
      name,
      email,
      phone,
      address,
      type,
      countryId,
      stateId,
      cityId,
      defaultShipping,
      defaultBilling,
      latitude,
      longitude,
      cityId,
      createdAt,
      updatedAt,
      invoiceType,
      tcIdentity,
      taxNumber,
      taxOffice,
      companyName,
      isEInvoice,
      zipCode,
      neighborhood,
    ];
  }
}
