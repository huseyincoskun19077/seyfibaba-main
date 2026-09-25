import 'package:equatable/equatable.dart';

class StoryModel extends Equatable {
  final int id;
  final String title;
  final String image;
  final String type;
  final String? feed;
  final String? link;
  final String? mobileLink;
  final String? seeAllUrl;
  final int serial;
  final bool showOnWeb;
  final bool showOnMobile;

  const StoryModel({
    required this.id,
    required this.title,
    required this.image,
    required this.type,
    this.feed,
    this.link,
    this.mobileLink,
    this.seeAllUrl,
    this.serial = 0,
    this.showOnWeb = true,
    this.showOnMobile = true,
  });

  bool get isProductFeed => type == 'product_feed';

  /// Mobilde önce mobile_link, yoksa web link.
  String get effectiveMobileHref {
    final m = (mobileLink ?? '').trim();
    if (m.isNotEmpty) return m;
    return (link ?? '').trim();
  }

  static bool _flag(dynamic v, {bool fallback = true}) {
    if (v == null) return fallback;
    if (v is bool) return v;
    final s = '$v'.toLowerCase();
    if (s == '1' || s == 'true') return true;
    if (s == '0' || s == 'false') return false;
    return fallback;
  }

  factory StoryModel.fromMap(Map<String, dynamic> map) {
    return StoryModel(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      title: '${map['title'] ?? ''}',
      image: '${map['image'] ?? ''}',
      type: '${map['type'] ?? 'link'}',
      feed: map['feed']?.toString(),
      link: map['link']?.toString(),
      mobileLink: map['mobile_link']?.toString(),
      seeAllUrl: map['see_all_url']?.toString(),
      serial: int.tryParse('${map['serial'] ?? 0}') ?? 0,
      showOnWeb: _flag(map['show_on_web']),
      showOnMobile: _flag(map['show_on_mobile']),
    );
  }

  @override
  List<Object?> get props => [
        id,
        title,
        image,
        type,
        feed,
        link,
        mobileLink,
        seeAllUrl,
        serial,
        showOnWeb,
        showOnMobile,
      ];
}
