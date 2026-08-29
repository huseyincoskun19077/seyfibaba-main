// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';

class DeliveryManModel extends Equatable {
  final int id;
  final String manImage;
  final String fname;
  final String lname;
  final String email;
  final String manType;
  final String idnType;
  final String idnNum;
  final String idnImage;
  final String phone;
  final int status;
  final String deviceToken;
  final double latitude;
  final double longitude;
  final double uLatitude;
  final double uLongitude;
  final double dLatitude;
  final double dLongitude;
  final String createdAt;
  final String updatedAt;

  const DeliveryManModel({
    required this.id,
    required this.manImage,
    required this.fname,
    required this.lname,
    required this.email,
    required this.manType,
    required this.idnType,
    required this.idnNum,
    required this.idnImage,
    required this.phone,
    required this.status,
    required this.deviceToken,
    required this.latitude,
    required this.longitude,
    required this.uLatitude,
    required this.uLongitude,
    required this.dLatitude,
    required this.dLongitude,
    required this.createdAt,
    required this.updatedAt,
  });

  DeliveryManModel copyWith({
    int? id,
    String? manImage,
    String? fname,
    String? lname,
    String? email,
    String? manType,
    String? idnType,
    String? idnNum,
    String? idnImage,
    String? phone,
    int? status,
    String? deviceToken,
    double? latitude,
    double? longitude,
    double? uLatitude,
    double? uLongitude,
    double? dLatitude,
    double? dLongitude,
    String? createdAt,
    String? updatedAt,
  }) {
    return DeliveryManModel(
      id: id ?? this.id,
      manImage: manImage ?? this.manImage,
      fname: fname ?? this.fname,
      lname: lname ?? this.lname,
      email: email ?? this.email,
      manType: manType ?? this.manType,
      idnType: idnType ?? this.idnType,
      idnNum: idnNum ?? this.idnNum,
      idnImage: idnImage ?? this.idnImage,
      phone: phone ?? this.phone,
      status: status ?? this.status,
      deviceToken: deviceToken ?? this.deviceToken,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      uLatitude: uLatitude ?? this.uLatitude,
      uLongitude: uLongitude ?? this.uLongitude,
      dLatitude: dLatitude ?? this.dLatitude,
      dLongitude: dLongitude ?? this.dLongitude,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'man_image': manImage,
      'fname': fname,
      'lname': lname,
      'email': email,
      'man_type': manType,
      'idn_type': idnType,
      'idn_num': idnNum,
      'idn_image': idnImage,
      'phone': phone,
      'status': status,
      'device_token': deviceToken,
      'latitude': latitude,
      'longitude': longitude,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory DeliveryManModel.fromMap(Map<String, dynamic> map) {
    return DeliveryManModel(
      id: map['id'] ??0,
      manImage: map['man_image'] ?? '',
      fname: map['fname'] ?? '',
      lname: map['lname'] ?? '',
      email: map['email'] ?? '',
      manType: map['man_type'] ?? '',
      idnType: map['idn_type'] ?? '',
      idnNum: map['idn_num'] ?? '',
      idnImage: map['idn_image'] ?? '',
      phone: map['phone'] ?? '',
      status: map['status'] != null ? int.parse(map['status'].toString()) : 0,
      deviceToken: map['device_token'] ?? '',
      latitude: map['latitude'] != null
          ? double.parse(map['latitude'].toString())
          : 0.0,
      longitude: map['longitude'] != null
          ? double.parse(map['longitude'].toString())
          : 0.0,
      uLatitude: map['user_latitude'] != null
          ? double.parse(map['user_latitude'].toString())
          : 0.0,
      uLongitude: map['user_longitude'] != null
          ? double.parse(map['user_longitude'].toString())
          : 0.0,
      dLatitude: map['deliveryman_latitude'] != null
          ? double.parse(map['deliveryman_latitude'].toString())
          : 0.0,
      dLongitude: map['deliveryman_longitude'] != null
          ? double.parse(map['deliveryman_longitude'].toString())
          : 0.0,
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory DeliveryManModel.fromJson(String source) =>
      DeliveryManModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props {
    return [
      id,
      manImage,
      fname,
      lname,
      email,
      manType,
      idnType,
      idnNum,
      idnImage,
      phone,
      status,
      deviceToken,
      latitude,
      longitude,
      uLatitude,
      uLongitude,
      dLatitude,
      dLongitude,
      createdAt,
      updatedAt,
    ];
  }
}
