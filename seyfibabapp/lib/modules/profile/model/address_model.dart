import 'dart:convert';

import 'package:equatable/equatable.dart';

import '../controllers/address/address_cubit.dart';
import 'city_model.dart';
import 'country_model.dart';
import 'country_state_model.dart';

class AddressModel extends Equatable {
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
  final int serialNo;
  final int defaultShipping;
  final int defaultBilling;
  final String createdAt;
  final String updatedAt;
  final double latitude;
  final double longitude;
  final double distance;
  final double priceRange;
  final CountryModel? country;
  final CountryStateModel? countryState;
  final AddressState addState;
  final CityModel? city;
  final String invoiceType;
  final String tcIdentity;
  final String taxNumber;
  final String taxOffice;
  final String companyName;
  final bool isEInvoice;
  final String zipCode;
  final String neighborhood;

  const AddressModel({
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
    required this.createdAt,
    required this.updatedAt,
    required this.latitude,
    required this.longitude,
    required this.distance,
    required this.priceRange,
    required this.country,
    required this.countryState,
     this.serialNo  = 0,
     this.addState = const AddressStateInitial(),
    required this.city,
    this.invoiceType = 'individual',
    this.tcIdentity = '',
    this.taxNumber = '',
    this.taxOffice = '',
    this.companyName = '',
    this.isEInvoice = false,
    this.zipCode = '',
    this.neighborhood = '',
  });

  AddressModel copyWith({
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
    int? serialNo,
    int? defaultShipping,
    int? defaultBilling,
    String? createdAt,
    String? updatedAt,
    double? latitude,
    double? longitude,
    double? distance,
    double? priceRange,
    CountryModel? country,
    CountryStateModel? countryState,
    AddressState? addState,
    CityModel? city,
  }) {
    return AddressModel(
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
      serialNo: serialNo ?? this.serialNo,
      defaultShipping: defaultShipping ?? this.defaultShipping,
      defaultBilling: defaultBilling ?? this.defaultBilling,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      distance: distance ?? this.distance,
      priceRange: priceRange ?? this.priceRange,
      country: country ?? this.country,
      countryState: countryState ?? this.countryState,
      addState: addState ?? this.addState,
      city: city ?? this.city,
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
    if (country != null) {
      result.addAll({'country': country!.toMap()});
    }
    if (countryState != null) {
      result.addAll({'country_state': countryState!.toMap()});
    }
    if (city != null) result.addAll({'city': city!.toMap()});

    return result;
  }


  Map<String, dynamic> toGuestMap() {
    final result = <String, dynamic>{};

    result.addAll({'name': name});
    result.addAll({'email': email});
    result.addAll({'phone': phone});
    result.addAll({'address': address});
    result.addAll({'type': 'home'});
    result.addAll({'country': countryId});
    result.addAll({'state': stateId});
    result.addAll({'city': cityId});
    result.addAll({'latitude': latitude.toString()});
    result.addAll({'longitude': longitude.toString()});
    // result.addAll({'shipping_method_id': userId.toString()});

    return result;
  }

  factory AddressModel.fromMap(Map<String, dynamic> map) {
    return AddressModel(
      id: map['id']?.toInt() ?? 0,
      userId: map['user_id'] != null ? int.parse(map['user_id'].toString()) : 0,
      name: map['name'] ?? '',
      email: map['email'] ?? '',
      phone: map['phone'] ?? '',
      address: map['address'] ?? '',
      type: map['type'] ?? "",
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
      distance: map['distance_in_km'] != null
          ? double.parse(map['distance_in_km'].toString())
          : 0.0,
      priceRange: map['per_km_price_range'] != null
          ? double.parse(map['per_km_price_range'].toString())
          : 0.0,
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
      country:
          map['country'] != null ? CountryModel.fromMap(map['country']) : null,
      countryState: map['country_state'] != null
          ? CountryStateModel.fromMap(map['country_state'])
          : null,
      city: map['city'] != null ? CityModel.fromMap(map['city']) : null,
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

  factory AddressModel.fromJson(String source) =>
      AddressModel.fromMap(json.decode(source));

  @override
  String toString() {
    return 'AddressModel(id: $id, user_id: $userId, name: $name, email: $email, phone: $phone, address: $address, type: $type, country_id: $countryId, state_id: $stateId, city_id: $cityId,default_shipping: $defaultShipping,  default_billing: $defaultBilling, created_at: $createdAt, updated_at: $updatedAt, country: $country, country_state: $countryState, city: $city)';
  }

  static AddressModel init(){
    return const AddressModel(
      id : 0,
      userId : 0,
      name : '',
      email : '',
      phone : '',
      address : '',
      type : 'home',
      countryId : 0,
      stateId : 0,
      cityId : 0,
      serialNo : 0,
      defaultShipping : 0,
      defaultBilling : 0,
      createdAt : '',
      updatedAt : '',
      country : null,
      countryState : null,
      city : null,
      latitude : 0,
      longitude : 0,
      distance : 0,
      priceRange : 0,
      addState : AddressStateInitial(),
    );
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
      serialNo,
      defaultShipping,
      defaultBilling,
      createdAt,
      updatedAt,
      country,
      countryState,
      city,
      latitude,
      longitude,
      distance,
      priceRange,
      addState,
    ];
  }
}
