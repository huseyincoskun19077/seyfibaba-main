import 'dart:convert';

import 'package:http/http.dart' as http;

import '../../../core/data/datasources/network_parser.dart';
import '../../../core/remote_urls.dart';
import '../models/second_hand_models.dart';

class PaginatedSecondHandListings {
  PaginatedSecondHandListings({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    this.conditionOptions = const {},
  });

  final List<SecondHandListing> items;
  final int currentPage;
  final int lastPage;
  final Map<String, String> conditionOptions;

  bool get hasMore => currentPage < lastPage;

  static PaginatedSecondHandListings fromResponse(Map<String, dynamic> json) {
    final block = json['listings'];
    if (block is Map<String, dynamic>) {
      final data = block['data'];
      return PaginatedSecondHandListings(
        items: data is List
            ? data
                .whereType<Map>()
                .map((e) =>
                    SecondHandListing.fromMap(Map<String, dynamic>.from(e)))
                .toList()
            : [],
        currentPage: int.tryParse('${block['current_page'] ?? 1}') ?? 1,
        lastPage: int.tryParse('${block['last_page'] ?? 1}') ?? 1,
        conditionOptions: _parseConditionOptions(json['condition_options']),
      );
    }
    return PaginatedSecondHandListings(
      items: const [],
      currentPage: 1,
      lastPage: 1,
      conditionOptions: _parseConditionOptions(json['condition_options']),
    );
  }
}

class PaginatedConversations {
  PaginatedConversations({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<SecondHandConversation> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  static PaginatedConversations fromResponse(Map<String, dynamic> json) {
    final block = json['conversations'];
    if (block is Map<String, dynamic>) {
      final data = block['data'];
      return PaginatedConversations(
        items: data is List
            ? data
                .whereType<Map>()
                .map((e) => SecondHandConversation.fromMap(
                    Map<String, dynamic>.from(e)))
                .toList()
            : [],
        currentPage: int.tryParse('${block['current_page'] ?? 1}') ?? 1,
        lastPage: int.tryParse('${block['last_page'] ?? 1}') ?? 1,
      );
    }
    return PaginatedConversations(items: const [], currentPage: 1, lastPage: 1);
  }
}

class PaginatedMessages {
  PaginatedMessages({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<SecondHandMessage> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  static PaginatedMessages fromResponse(Map<String, dynamic> json) {
    final block = json['messages'];
    if (block is Map<String, dynamic>) {
      final data = block['data'];
      return PaginatedMessages(
        items: data is List
            ? data
                .whereType<Map>()
                .map((e) =>
                    SecondHandMessage.fromMap(Map<String, dynamic>.from(e)))
                .toList()
            : [],
        currentPage: int.tryParse('${block['current_page'] ?? 1}') ?? 1,
        lastPage: int.tryParse('${block['last_page'] ?? 1}') ?? 1,
      );
    }
    return PaginatedMessages(items: const [], currentPage: 1, lastPage: 1);
  }
}

Map<String, String> _parseConditionOptions(dynamic raw) {
  if (raw is Map) {
    return raw.map((k, v) => MapEntry('$k', '$v'));
  }
  return Map<String, String>.from(secondHandConditionLabels);
}

class SecondHandService {
  SecondHandService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Map<String, String> _jsonHeaders({String? token}) {
    final headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Client-Platform': 'mobile',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  static String listingImageUrl(int imageId) =>
      RemoteUrls.secondHandListingImage(imageId);

  static String resolveListingImageUrl(SecondHandImage image) {
    final u = image.url?.trim();
    if (u != null && u.isNotEmpty) {
      if (u.startsWith('http://') || u.startsWith('https://')) return u;
      return RemoteUrls.imageUrl(u);
    }
    return listingImageUrl(image.id);
  }

  Future<PaginatedSecondHandListings> fetchPublicListings({
    int page = 1,
    String? q,
    String? condition,
    String? province,
    String? district,
    String? categoryId,
    String? subCategoryId,
    String? sort,
  }) async {
    final params = <String, String>{'page': '$page'};
    if (q != null && q.trim().isNotEmpty) params['q'] = q.trim();
    if (condition != null && condition.isNotEmpty) params['condition'] = condition;
    if (province != null && province.isNotEmpty) params['province'] = province;
    if (district != null && district.isNotEmpty) params['district'] = district;
    if (categoryId != null && categoryId.isNotEmpty) {
      params['category_id'] = categoryId;
    }
    if (subCategoryId != null && subCategoryId.isNotEmpty) {
      params['sub_category_id'] = subCategoryId;
    }
    if (sort != null && sort.isNotEmpty) params['sort'] = sort;

    final uri = Uri.parse(RemoteUrls.secondHandListings)
        .replace(queryParameters: params);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders()),
    );
    return PaginatedSecondHandListings.fromResponse(
        Map<String, dynamic>.from(response));
  }

  Future<SecondHandListing> fetchPublicListing(int id) async {
    final uri = Uri.parse(RemoteUrls.secondHandListingShow(id));
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders()),
    );
    final listing = response['listing'];
    if (listing is Map) {
      return SecondHandListing.fromMap(Map<String, dynamic>.from(listing));
    }
    throw Exception('İlan bulunamadı.');
  }

  Future<Map<String, String>> fetchAgreements() async {
    final uri = Uri.parse(RemoteUrls.secondHandAgreements);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders()),
    );
    return {
      'terms_title': '${response['terms_title'] ?? ''}',
      'terms_content': '${response['terms_content'] ?? ''}',
      'privacy_title': '${response['privacy_title'] ?? ''}',
      'privacy_content': '${response['privacy_content'] ?? ''}',
    };
  }

  Future<Map<String, String>> fetchListingRules() async {
    final uri = Uri.parse('${RemoteUrls.legalDocuments}/second-hand-rules');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders()),
    );
    final doc = response is Map ? response['document'] : null;
    if (doc is Map) {
      return {
        'title': '${doc['title'] ?? 'İkinci El İlan Kuralları'}',
        'content': '${doc['content'] ?? ''}',
      };
    }
    return {
      'title': 'İkinci El İlan Kuralları',
      'content': '',
    };
  }

  Future<SecondHandVerification> fetchVerification(String token) async {
    final uri = Uri.parse(RemoteUrls.secondHandUserVerification);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token: token)),
    );
    final verification = response['verification'];
    if (verification is Map) {
      return SecondHandVerification.fromMap(
          Map<String, dynamic>.from(verification));
    }
    return SecondHandVerification(status: 'none');
  }

  Future<String> submitVerification({
    required String token,
    required String businessName,
    required String taxNumber,
    String? barberRegistryNumber,
    required String taxDocumentPath,
    String? barberDocumentPath,
    required bool acceptTerms,
    required bool acceptPrivacy,
  }) async {
    final uri = Uri.parse(RemoteUrls.secondHandUserVerification);
    final request = http.MultipartRequest('POST', uri);
    request.headers.addAll(_jsonHeaders(token: token));
    request.fields['business_name'] = businessName;
    request.fields['tax_number'] = taxNumber;
    if (barberRegistryNumber != null && barberRegistryNumber.trim().isNotEmpty) {
      request.fields['barber_registry_number'] = barberRegistryNumber.trim();
    }
    request.fields['accept_terms'] = acceptTerms ? '1' : '0';
    request.fields['accept_privacy'] = acceptPrivacy ? '1' : '0';

    request.files.add(
      await http.MultipartFile.fromPath('tax_document', taxDocumentPath),
    );
    if (barberDocumentPath != null && barberDocumentPath.isNotEmpty) {
      request.files.add(
        await http.MultipartFile.fromPath('barber_document', barberDocumentPath),
      );
    }

    final streamed = await request.send();
    final response = await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
    return '${response['message'] ?? 'Başvurunuz alındı.'}';
  }

  Future<PaginatedSecondHandListings> fetchMyListings({
    required String token,
    int page = 1,
    String? status,
    String? q,
  }) async {
    final params = <String, String>{'page': '$page'};
    if (status != null && status.isNotEmpty) params['status'] = status;
    if (q != null && q.trim().isNotEmpty) params['q'] = q.trim();

    final uri = Uri.parse(RemoteUrls.secondHandUserListingsMy)
        .replace(queryParameters: params);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token: token)),
    );
    return PaginatedSecondHandListings.fromResponse(
        Map<String, dynamic>.from(response));
  }

  Future<SecondHandListing> createDraft({
    required String token,
    required Map<String, dynamic> body,
  }) async {
    final uri = Uri.parse(RemoteUrls.secondHandUserListings);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(uri,
          headers: _jsonHeaders(token: token), body: jsonEncode(body)),
    );
    final listing = response['listing'];
    if (listing is Map) {
      return SecondHandListing.fromMap(Map<String, dynamic>.from(listing));
    }
    throw Exception('Taslak oluşturulamadı.');
  }

  Future<SecondHandListing> updateDraft({
    required String token,
    required int id,
    required Map<String, dynamic> body,
  }) async {
    final uri = Uri.parse('${RemoteUrls.secondHandUserListings}/$id');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.put(uri,
          headers: _jsonHeaders(token: token), body: jsonEncode(body)),
    );
    final listing = response['listing'];
    if (listing is Map) {
      return SecondHandListing.fromMap(Map<String, dynamic>.from(listing));
    }
    throw Exception('Taslak güncellenemedi.');
  }

  Future<String> publishListing({
    required String token,
    required int id,
  }) async {
    final uri = Uri.parse('${RemoteUrls.secondHandUserListings}/$id/publish');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(uri, headers: _jsonHeaders(token: token)),
    );
    return '${response['message'] ?? 'İlan gönderildi.'}';
  }

  Future<SecondHandListing> uploadListingImage({
    required String token,
    required int listingId,
    required String filePath,
  }) async {
    final uri =
        Uri.parse('${RemoteUrls.secondHandUserListings}/$listingId/images');
    final request = http.MultipartRequest('POST', uri);
    request.headers.addAll(_jsonHeaders(token: token));
    request.files.add(await http.MultipartFile.fromPath('image', filePath));

    final streamed = await request.send();
    final response = await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
    final listing = response['listing'];
    if (listing is Map) {
      return SecondHandListing.fromMap(Map<String, dynamic>.from(listing));
    }
    throw Exception('Fotoğraf yüklenemedi.');
  }

  Future<SecondHandListing> deleteListingImage({
    required String token,
    required int listingId,
    required int imageId,
  }) async {
    final uri = Uri.parse(
        '${RemoteUrls.secondHandUserListings}/$listingId/images/$imageId');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.delete(uri, headers: _jsonHeaders(token: token)),
    );
    final listing = response['listing'];
    if (listing is Map) {
      return SecondHandListing.fromMap(Map<String, dynamic>.from(listing));
    }
    throw Exception('Fotoğraf silinemedi.');
  }

  Future<String> deactivateListing({required String token, required int id}) async {
    final uri =
        Uri.parse('${RemoteUrls.secondHandUserListings}/$id/deactivate');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(uri, headers: _jsonHeaders(token: token)),
    );
    return '${response['message'] ?? 'İlan pasifleştirildi.'}';
  }

  Future<String> activateListing({required String token, required int id}) async {
    final uri = Uri.parse('${RemoteUrls.secondHandUserListings}/$id/activate');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(uri, headers: _jsonHeaders(token: token)),
    );
    return '${response['message'] ?? 'İlan aktifleştirildi.'}';
  }

  Future<String> markSoldListing({required String token, required int id}) async {
    final uri = Uri.parse('${RemoteUrls.secondHandUserListings}/$id/sold');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(uri, headers: _jsonHeaders(token: token)),
    );
    return '${response['message'] ?? 'İlan satıldı olarak işaretlendi.'}';
  }

  Future<PaginatedConversations> fetchInbox({
    required String token,
    int page = 1,
  }) async {
    final uri = Uri.parse(RemoteUrls.secondHandUserMessagesInbox)
        .replace(queryParameters: {'page': '$page'});
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token: token)),
    );
    return PaginatedConversations.fromResponse(
        Map<String, dynamic>.from(response));
  }

  Future<PaginatedMessages> fetchConversationMessages({
    required String token,
    required int conversationId,
    int page = 1,
  }) async {
    final uri =
        Uri.parse('${RemoteUrls.secondHandUserMessagesConversations}$conversationId')
            .replace(queryParameters: {'page': '$page'});
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token: token)),
    );
    return PaginatedMessages.fromResponse(
        Map<String, dynamic>.from(response));
  }

  Future<void> markConversationRead({
    required String token,
    required int conversationId,
  }) async {
    final uri = Uri.parse(
        '${RemoteUrls.secondHandUserMessagesConversations}$conversationId/read');
    await NetworkParser.callClientWithCatchException(
      () => _client.post(uri, headers: _jsonHeaders(token: token)),
    );
  }

  Future<int> sendToListing({
    required String token,
    required int listingId,
    required String body,
  }) async {
    final uri =
        Uri.parse('${RemoteUrls.secondHandUserMessagesListings}$listingId');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(uri,
          headers: _jsonHeaders(token: token),
          body: jsonEncode({'body': body})),
    );
    final conversationId = response['conversation_id'] ?? response['conversation']?['id'];
    return int.tryParse('$conversationId') ?? 0;
  }

  Future<SecondHandMessage> sendToConversation({
    required String token,
    required int conversationId,
    required String body,
  }) async {
    final uri = Uri.parse(
        '${RemoteUrls.secondHandUserMessagesConversations}$conversationId');
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(uri,
          headers: _jsonHeaders(token: token),
          body: jsonEncode({'body': body})),
    );
    final message = response['message'];
    if (message is Map) {
      return SecondHandMessage.fromMap(Map<String, dynamic>.from(message));
    }
    throw Exception('Mesaj gönderilemedi.');
  }

  Future<List<Map<String, dynamic>>> fetchCategories() async {
    final uri = Uri.parse(RemoteUrls.categoryLists);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders()),
    );
    return _mapList(response, const [
      'categories',
      'productCategories',
      'data',
    ]);
  }

  Future<List<Map<String, dynamic>>> fetchSubCategories(String categoryId) async {
    final uri = Uri.parse(RemoteUrls.subCategoryLists(categoryId));
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders()),
    );
    return _mapList(response, const [
      'subCategories',
      'sub_categories',
      'active_sub_categories',
      'data',
    ]);
  }

  Future<List<Map<String, dynamic>>> fetchChildCategories(String subCategoryId) async {
    final uri = Uri.parse(RemoteUrls.childCategoryLists(subCategoryId));
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders()),
    );
    return _mapList(response, const [
      'childCategories',
      'child_categories',
      'active_child_categories',
      'data',
    ]);
  }

  static List<Map<String, dynamic>> nestedList(
    Map<String, dynamic> parent,
    List<String> keys,
  ) {
    for (final key in keys) {
      final raw = parent[key];
      if (raw is List) {
        return raw
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
      }
    }
    return [];
  }

  static List<Map<String, dynamic>> _mapList(
    dynamic response,
    List<String> keys,
  ) {
    if (response is List) {
      return response
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    }
    if (response is Map) {
      return nestedList(Map<String, dynamic>.from(response), keys);
    }
    return [];
  }
}
