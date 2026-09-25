import 'package:equatable/equatable.dart';

class AnnouncementModalModel extends Equatable {
  final int id;
  final String title;
  final String description;
  final String image;
  final int status;
  final int expiredDate;
  final String? link;
  final String? mobileLink;
  final String? ctaText;
  final int showOnWeb;
  final int showOnMobile;

  const AnnouncementModalModel({
    required this.id,
    required this.title,
    required this.description,
    required this.image,
    required this.status,
    required this.expiredDate,
    this.link,
    this.mobileLink,
    this.ctaText,
    this.showOnWeb = 1,
    this.showOnMobile = 1,
  });

  bool get isActive => status == 1;
  bool get hasImage => image.trim().isNotEmpty;
  bool get showOnMobileApp => showOnMobile == 1;

  String get effectiveLink {
    final mobile = (mobileLink ?? '').trim();
    if (mobile.isNotEmpty) return mobile;
    return (link ?? '').trim();
  }

  factory AnnouncementModalModel.fromMap(Map<String, dynamic> map) {
    return AnnouncementModalModel(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      title: '${map['title'] ?? ''}',
      description: '${map['description'] ?? ''}',
      image: '${map['image'] ?? ''}',
      status: int.tryParse('${map['status'] ?? 0}') ?? 0,
      expiredDate: int.tryParse('${map['expired_date'] ?? 7}') ?? 7,
      link: map['link']?.toString(),
      mobileLink: map['mobile_link']?.toString(),
      ctaText: map['cta_text']?.toString(),
      showOnWeb: int.tryParse('${map['show_on_web'] ?? 1}') ?? 1,
      showOnMobile: int.tryParse('${map['show_on_mobile'] ?? 1}') ?? 1,
    );
  }

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'image': image,
      'status': status,
      'expired_date': expiredDate,
      'link': link,
      'mobile_link': mobileLink,
      'cta_text': ctaText,
      'show_on_web': showOnWeb,
      'show_on_mobile': showOnMobile,
    };
  }

  @override
  List<Object?> get props => [
        id,
        title,
        description,
        image,
        status,
        expiredDate,
        link,
        mobileLink,
        ctaText,
        showOnWeb,
        showOnMobile,
      ];
}
