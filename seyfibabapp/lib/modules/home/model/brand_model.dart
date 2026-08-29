import 'dart:convert';

import 'package:equatable/equatable.dart';

class BrandModel extends Equatable {
  final int id;
  final String name;
  final String slug;
  final String logo;
  final int status;
  final int isFeatured;
  final int isTop;
  final int isPopular;
  final int isTrending;
  final double rating;
  final String createdAt;
  final String updatedAt;

  const BrandModel({
    required this.id,
    required this.name,
    required this.slug,
    required this.logo,
    required this.status,
    required this.isFeatured,
    required this.isTop,
    required this.isPopular,
    required this.isTrending,
    required this.rating,
    required this.createdAt,
    required this.updatedAt,
  });

  BrandModel copyWith({
    int? id,
    String? name,
    String? slug,
    String? logo,
    int? status,
    int? isFeatured,
    int? isTop,
    int? isPopular,
    int? isTrending,
    double? rating,
    String? createdAt,
    String? updatedAt,
  }) {
    return BrandModel(
      id: id ?? this.id,
      name: name ?? this.name,
      slug: slug ?? this.slug,
      logo: logo ?? this.logo,
      status: status ?? this.status,
      isFeatured: isFeatured ?? this.isFeatured,
      isTop: isTop ?? this.isTop,
      isPopular: isPopular ?? this.isPopular,
      isTrending: isTrending ?? this.isTrending,
      rating: rating ?? this.rating,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  Map<String, dynamic> toMap() {
    final result = <String, dynamic>{};

    result.addAll({'id': id});
    result.addAll({'name': name});
    result.addAll({'slug': slug});
    result.addAll({'logo': logo});
    result.addAll({'status': status});
    result.addAll({'isFeatured': isFeatured});
    result.addAll({'isTop': isTop});
    result.addAll({'isPopular': isPopular});
    result.addAll({'isTrending': isTrending});
    result.addAll({'rating': rating});
    result.addAll({'createdAt': createdAt});
    result.addAll({'updatedAt': updatedAt});

    return result;
  }

  static int _parseInt(dynamic value) {
    if (value == null) return 0;
    return int.tryParse(value.toString()) ?? 0;
  }

  static double _parseDouble(dynamic value) {
    if (value == null) return 0;
    return double.tryParse(value.toString()) ?? 0;
  }

  factory BrandModel.fromMap(Map<String, dynamic> map) {
    return BrandModel(
      id: _parseInt(map['id']),
      name: map['name']?.toString() ?? '',
      slug: map['slug']?.toString() ?? '',
      logo: map['logo']?.toString() ?? '',
      status: _parseInt(map['status']),
      isFeatured: _parseInt(map['is_featured'] ?? map['isFeatured']),
      isTop: _parseInt(map['is_top'] ?? map['isTop']),
      isPopular: _parseInt(map['is_popular'] ?? map['isPopular']),
      isTrending: _parseInt(map['is_trending'] ?? map['isTrending']),
      rating: _parseDouble(map['rating']),
      createdAt: map['created_at']?.toString() ?? map['createdAt']?.toString() ?? '',
      updatedAt: map['updated_at']?.toString() ?? map['updatedAt']?.toString() ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory BrandModel.fromJson(String source) =>
      BrandModel.fromMap(json.decode(source));

  @override
  String toString() {
    return 'BrandModel(id: $id, name: $name, slug: $slug, logo: $logo, status: $status, isFeatured: $isFeatured, isTop: $isTop, isPopular: $isPopular, isTrending: $isTrending, rating: $rating, createdAt: $createdAt, updatedAt: $updatedAt)';
  }

  @override
  List<Object> get props {
    return [
      id,
      name,
      slug,
      logo,
      status,
      isFeatured,
      isTop,
      isPopular,
      isTrending,
      rating,
      createdAt,
      updatedAt,
    ];
  }
}
