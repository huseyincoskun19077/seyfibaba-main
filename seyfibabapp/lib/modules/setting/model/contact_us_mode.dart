// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';

class ContactModel extends Equatable {
  final ContactUsModel? contactUsModel;
  final List<SocialLinks>? socialLinks;
  const ContactModel({
    this.contactUsModel,
    this.socialLinks,
  });

  ContactModel copyWith({
    ContactUsModel? contactUsModel,
    List<SocialLinks>? socialLinks,
  }) {
    return ContactModel(
      contactUsModel: contactUsModel ?? this.contactUsModel,
      socialLinks: socialLinks ?? this.socialLinks,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'contact': contactUsModel?.toMap(),
      'social_links': socialLinks?.map((x) => x.toMap()).toList(),
    };
  }

  factory ContactModel.fromMap(Map<String, dynamic> map) {
    return ContactModel(
      contactUsModel: map['contact'] != null ? ContactUsModel.fromMap(map['contact'] as Map<String,dynamic>) : null,
      socialLinks: map['social_links'] != null ? List<SocialLinks>.from((map['social_links'] as List<dynamic>).map<SocialLinks?>((x) => SocialLinks.fromMap(x as Map<String,dynamic>),),) : null,
    );
  }

  String toJson() => json.encode(toMap());

  factory ContactModel.fromJson(String source) => ContactModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props => [contactUsModel!, socialLinks!];
}


class ContactUsModel extends Equatable {
  final int id;
  final String banner;
  final String title;
  final String description;
  final String email;
  final String address;
  final String phone;
  final String map;
  final String createdAt;
  final String updatedAt;
  const ContactUsModel({
    required this.id,
    required this.banner,
    required this.title,
    required this.description,
    required this.email,
    required this.address,
    required this.phone,
    required this.map,
    required this.createdAt,
    required this.updatedAt,
  });

  ContactUsModel copyWith({
    int? id,
    String? banner,
    String? title,
    String? description,
    String? email,
    String? address,
    String? phone,
    String? map,
    String? createdAt,
    String? updatedAt,
  }) {
    return ContactUsModel(
      id: id ?? this.id,
      banner: banner ?? this.banner,
      title: title ?? this.title,
      description: description ?? this.description,
      email: email ?? this.email,
      address: address ?? this.address,
      phone: phone ?? this.phone,
      map: map ?? this.map,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  Map<String, dynamic> toMap() {
    final result = <String, dynamic>{};

    result.addAll({'id': id});
    result.addAll({'banner': banner});
    result.addAll({'title': title});
    result.addAll({'description': description});
    result.addAll({'email': email});
    result.addAll({'address': address});
    result.addAll({'phone': phone});
    result.addAll({'map': map});
    result.addAll({'created_at': createdAt});
    result.addAll({'updated_at': updatedAt});

    return result;
  }

  factory ContactUsModel.fromMap(Map<String, dynamic> map) {
    return ContactUsModel(
      id: map['id']?.toInt() ?? 0,
      banner: map['banner'] ?? '',
      title: map['title'] ?? '',
      description: map['description'] ?? '',
      email: map['email'] ?? '',
      address: map['address'] ?? '',
      phone: map['phone'] ?? '',
      map: map['map'] ?? '',
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory ContactUsModel.fromJson(String source) =>
      ContactUsModel.fromMap(json.decode(source));

  @override
  String toString() {
    return 'ContactUsModel(id: $id, banner: $banner, title: $title, description: $description, email: $email, address: $address, phone: $phone, map: $map, createdAt: $createdAt, updatedAt: $updatedAt)';
  }

  @override
  List<Object> get props {
    return [
      id,
      banner,
      title,
      description,
      email,
      address,
      phone,
      map,
      createdAt,
      updatedAt,
    ];
  }
}

class SocialLinks extends Equatable {
  final int id;
  final String link;
  final String icon;
  final String createdAt;
  final String updatedAt;
  const SocialLinks({
    required this.id,
    required this.link,
    required this.icon,
    required this.createdAt,
    required this.updatedAt,
  });

  SocialLinks copyWith({
    int? id,
    String? link,
    String? icon,
    String? createdAt,
    String? updatedAt,
  }) {
    return SocialLinks(
      id: id ?? this.id,
      link: link ?? this.link,
      icon: icon ?? this.icon,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'link': link,
      'icon': icon,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory SocialLinks.fromMap(Map<String, dynamic> map) {
    return SocialLinks(
      id: map['id'] ?? 0,
      link: map['link'] ?? '',
      icon: map['icon'] ?? '',
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory SocialLinks.fromJson(String source) => SocialLinks.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props {
    return [
      id,
      link,
      icon,
      createdAt,
      updatedAt,
    ];
  }
}
