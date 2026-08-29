class SecondHandListing {
  SecondHandListing({
    required this.id,
    required this.title,
    required this.description,
    required this.price,
    required this.condition,
    required this.status,
    this.province,
    this.district,
    this.locality,
    this.neighborhood,
    this.viewsCount = 0,
    this.sellerVerified = false,
    this.sellerBusinessName,
    this.images = const [],
    this.userId,
    this.categoryId,
    this.subCategoryId,
    this.childCategoryId,
  });

  final int id;
  final String title;
  final String description;
  final num price;
  final String condition;
  final String status;
  final String? province;
  final String? district;
  final String? locality;
  final String? neighborhood;
  final int viewsCount;
  final bool sellerVerified;
  final String? sellerBusinessName;
  final List<SecondHandImage> images;
  final int? userId;
  final int? categoryId;
  final int? subCategoryId;
  final int? childCategoryId;

  factory SecondHandListing.fromMap(Map<String, dynamic> map) {
    final rawImages = map['images'];
    return SecondHandListing(
      id: int.tryParse('${map['id']}') ?? 0,
      title: '${map['title'] ?? ''}',
      description: '${map['description'] ?? ''}',
      price: num.tryParse('${map['price']}') ?? 0,
      condition: '${map['condition'] ?? ''}',
      status: '${map['status'] ?? ''}',
      province: map['province']?.toString(),
      district: map['district']?.toString(),
      locality: map['locality']?.toString(),
      neighborhood: map['neighborhood']?.toString(),
      viewsCount: int.tryParse('${map['views_count'] ?? 0}') ?? 0,
      sellerVerified: map['seller_verified'] == true ||
          map['seller_verified'] == 1 ||
          '${map['seller_verified']}' == '1',
      sellerBusinessName: map['seller_business_name']?.toString(),
      userId: int.tryParse('${map['user_id'] ?? map['user']?['id'] ?? ''}'),
      categoryId: int.tryParse('${map['category_id'] ?? ''}'),
      subCategoryId: int.tryParse('${map['sub_category_id'] ?? ''}'),
      childCategoryId: int.tryParse('${map['child_category_id'] ?? ''}'),
      images: rawImages is List
          ? rawImages
              .whereType<Map>()
              .map((e) => SecondHandImage.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : [],
    );
  }

  String get cityDistrictLabel {
    final parts = <String>[];
    final seen = <String>{};
    for (final raw in [province, district]) {
      final v = raw?.trim() ?? '';
      if (v.isEmpty) continue;
      final key = v.toLowerCase();
      if (seen.contains(key)) continue;
      seen.add(key);
      parts.add(v);
    }
    return parts.join(' · ');
  }

  String get locationLabel {
    final parts = <String>[];
    final seen = <String>{};
    for (final raw in [province, district, locality, neighborhood]) {
      final v = raw?.trim() ?? '';
      if (v.isEmpty) continue;
      final key = v.toLowerCase();
      if (seen.contains(key)) continue;
      seen.add(key);
      parts.add(v);
    }
    return parts.join(' · ');
  }
}

bool isCosmeticSecondHandCategory(Map<String, dynamic> category) {
  final hay =
      '${category['name'] ?? ''} ${category['slug'] ?? ''}'.toLowerCase();
  return hay.contains('kozmetik');
}

List<Map<String, dynamic>> withoutCosmeticSecondHandCategories(
  List<Map<String, dynamic>> categories,
) {
  return categories.where((c) => !isCosmeticSecondHandCategory(c)).toList();
}

class SecondHandImage {
  SecondHandImage({
    required this.id,
    this.url,
  });

  final int id;
  final String? url;

  factory SecondHandImage.fromMap(Map<String, dynamic> map) {
    final rawUrl = map['url']?.toString().trim();
    return SecondHandImage(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      url: (rawUrl != null && rawUrl.isNotEmpty) ? rawUrl : null,
    );
  }
}

class SecondHandVerification {
  SecondHandVerification({
    required this.status,
    this.businessName,
    this.taxNumber,
    this.barberRegistryNumber,
    this.adminNote,
  });

  final String status;
  final String? businessName;
  final String? taxNumber;
  final String? barberRegistryNumber;
  final String? adminNote;

  bool get isApproved => status == 'approved';
  bool get isPending => status == 'pending';
  bool get isRejected => status == 'rejected';

  factory SecondHandVerification.fromMap(Map<String, dynamic>? map) {
    if (map == null) {
      return SecondHandVerification(status: 'none');
    }
    return SecondHandVerification(
      status: '${map['status'] ?? 'none'}',
      businessName: map['business_name']?.toString(),
      taxNumber: map['tax_number']?.toString(),
      barberRegistryNumber: map['barber_registry_number']?.toString(),
      adminNote: map['admin_note']?.toString(),
    );
  }
}

class SecondHandConversation {
  SecondHandConversation({
    required this.id,
    required this.listingTitle,
    required this.counterpartyDisplay,
    required this.lastMessagePreview,
    required this.unreadCount,
    this.counterpartyId,
  });

  final int id;
  final String listingTitle;
  final String counterpartyDisplay;
  final String lastMessagePreview;
  final int unreadCount;
  final int? counterpartyId;

  factory SecondHandConversation.fromMap(Map<String, dynamic> map) {
    return SecondHandConversation(
      id: int.tryParse('${map['id']}') ?? 0,
      listingTitle: '${map['listing']?['title'] ?? map['listing_title'] ?? ''}',
      counterpartyDisplay:
          '${map['counterparty_display'] ?? map['seller_business_name'] ?? ''}',
      lastMessagePreview: '${map['last_message_preview'] ?? ''}',
      unreadCount: int.tryParse('${map['unread_count'] ?? 0}') ?? 0,
      counterpartyId: int.tryParse('${map['counterparty_id'] ?? ''}'),
    );
  }
}

class SecondHandMessage {
  SecondHandMessage({
    required this.id,
    required this.senderId,
    required this.body,
    required this.createdAt,
    this.senderDisplay,
  });

  final int id;
  final int senderId;
  final String body;
  final String createdAt;
  final String? senderDisplay;

  factory SecondHandMessage.fromMap(Map<String, dynamic> map) {
    return SecondHandMessage(
      id: int.tryParse('${map['id']}') ?? 0,
      senderId: int.tryParse('${map['sender_id'] ?? 0}') ?? 0,
      body: '${map['body'] ?? ''}',
      createdAt: '${map['created_at'] ?? ''}',
      senderDisplay: map['sender_display']?.toString(),
    );
  }
}

const secondHandStatusLabels = {
  'draft': 'Taslak',
  'pending': 'Onay bekliyor',
  'active': 'Yayında',
  'inactive': 'Pasif',
  'rejected': 'Reddedildi',
  'sold': 'Satıldı',
};

const secondHandConditionLabels = {
  'new': 'Sıfır',
  'lightly_used': 'Sıfır ayarında',
  'used': 'İyi durumda',
  'defective': 'Yıpranmış veya onarım gerekebilir',
};
