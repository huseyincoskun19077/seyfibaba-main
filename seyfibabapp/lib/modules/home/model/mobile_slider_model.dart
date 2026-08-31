import 'dart:convert';

import 'package:equatable/equatable.dart';

class MobileSliderModel extends Equatable {
  final int id;
  final String image;
  final String title;
  final String subtitle;
  final String link;
  final String productSlug;
  final int serial;
  final bool status;

  const MobileSliderModel({
    required this.id,
    required this.image,
    required this.title,
    required this.subtitle,
    required this.link,
    required this.productSlug,
    required this.serial,
    required this.status,
  });

  factory MobileSliderModel.fromMap(Map<String, dynamic> map) {
    return MobileSliderModel(
      id: map['id'] != null ? int.parse(map['id'].toString()) : 0,
      image: map['image']?.toString() ?? '',
      title: map['title']?.toString() ?? '',
      subtitle: map['subtitle']?.toString() ?? '',
      link: map['link']?.toString() ?? '',
      productSlug: map['product_slug']?.toString() ?? '',
      serial: map['serial'] != null ? int.parse(map['serial'].toString()) : 0,
      status: map['status'] == true ||
          map['status'] == 1 ||
          map['status']?.toString() == '1',
    );
  }

  factory MobileSliderModel.fromJson(String source) =>
      MobileSliderModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  List<Object?> get props => [
        id,
        image,
        title,
        subtitle,
        link,
        productSlug,
        serial,
        status,
      ];
}
