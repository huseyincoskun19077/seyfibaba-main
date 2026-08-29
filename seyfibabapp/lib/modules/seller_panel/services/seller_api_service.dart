import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../core/data/datasources/network_parser.dart';
import '../../../core/error/exception.dart';
import '../../../core/remote_urls.dart';
import '../models/seller_admin_message_model.dart';
import '../models/seller_bulk_import_model.dart';
import '../models/seller_catalog_option.dart';
import '../models/seller_dashboard_model.dart';
import '../models/seller_earnings_model.dart';
import '../models/seller_kyc_model.dart';
import '../models/seller_notification_model.dart';
import '../models/seller_order_model.dart';
import '../models/seller_product_model.dart';
import '../models/seller_return_model.dart';

class SellerApiService {
  SellerApiService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Map<String, String> _jsonHeaders(String token) => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      };

  Map<String, String> _authHeaders(String token) => {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };

  Future<SellerDashboardModel> fetchDashboard(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerDashboard),
        headers: _jsonHeaders(token),
      ),
    );
    return SellerDashboardModel.fromMap(Map<String, dynamic>.from(response));
  }

  Future<List<SellerProductModel>> fetchProducts(String token) async {
    final page = await fetchProductsPage(token: token, page: 1, perPage: 50);
    return page.products;
  }

  Future<SellerProductsPage> fetchProductsPage({
    required String token,
    required int page,
    int perPage = 20,
    String q = '',
    String filter = 'all',
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(
          RemoteUrls.sellerProductsLight(
            page: page,
            perPage: perPage,
            q: q,
            filter: filter,
          ),
        ),
        headers: _jsonHeaders(token),
      ),
    );
    final products = response['products'];
    final meta = response['meta'];
    final list = products is List
        ? products
            .whereType<Map>()
            .map((e) => SellerProductModel.fromMap(Map<String, dynamic>.from(e)))
            .toList()
        : <SellerProductModel>[];
    final metaMap = meta is Map ? Map<String, dynamic>.from(meta) : const {};
    return SellerProductsPage(
      products: list,
      currentPage: int.tryParse('${metaMap['current_page'] ?? page}') ?? page,
      lastPage: int.tryParse('${metaMap['last_page'] ?? 1}') ?? 1,
      total: int.tryParse('${metaMap['total'] ?? list.length}') ?? list.length,
    );
  }

  Future<void> toggleProductStatus(String token, int productId) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.put(
        Uri.parse(RemoteUrls.sellerProductStatus(productId)),
        headers: _jsonHeaders(token),
      ),
    );
  }

  Future<SellerProductCreateMeta> fetchCreateMeta(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerProductCreateMeta),
        headers: _jsonHeaders(token),
      ),
    );
    return SellerProductCreateMeta.fromMap(Map<String, dynamic>.from(response));
  }

  Future<List<SellerCatalogOption>> fetchSubcategories(
    String token,
    int categoryId,
  ) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerSubcategories(categoryId)),
        headers: _jsonHeaders(token),
      ),
    );
    final list = response['subCategories'];
    if (list is! List) return const [];
    return list
        .whereType<Map>()
        .map((e) => SellerCatalogOption.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<List<SellerCatalogOption>> fetchChildCategories(
    String token,
    int subCategoryId,
  ) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerChildCategories(subCategoryId)),
        headers: _jsonHeaders(token),
      ),
    );
    final list = response['childCategories'];
    if (list is! List) return const [];
    return list
        .whereType<Map>()
        .map((e) => SellerCatalogOption.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<Map<String, dynamic>> fetchProductEdit(
    String token,
    int productId,
  ) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerProductEdit(productId)),
        headers: _jsonHeaders(token),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> quickCreateProduct({
    required String token,
    required String name,
    required int quantity,
    required double price,
    double? offerPrice,
    required int categoryId,
    int? subCategoryId,
    int? childCategoryId,
    int? brandId,
    String? brandName,
    String? shortDescription,
    String? longDescription,
    String? shortName,
    String? tags,
    String? sku,
    String? weight,
    String? seoTitle,
    String? seoDescription,
    int saleUnitQty = 1,
    required String thumbImagePath,
    List<String> galleryImagePaths = const [],
    List<Map<String, String>> colors = const [],
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.sellerProductQuickCreate),
    );
    request.headers.addAll(_authHeaders(token));
    request.fields['name'] = name.trim();
    request.fields['quantity'] = '$quantity';
    request.fields['price'] = '$price';
    request.fields['category_id'] = '$categoryId';
    request.fields['sale_unit_qty'] = '${saleUnitQty < 1 ? 1 : saleUnitQty}';
    if (offerPrice != null && offerPrice > 0) {
      request.fields['offer_price'] = '$offerPrice';
    }
    if (subCategoryId != null && subCategoryId > 0) {
      request.fields['sub_category_id'] = '$subCategoryId';
    }
    if (childCategoryId != null && childCategoryId > 0) {
      request.fields['child_category_id'] = '$childCategoryId';
    }
    if (brandId != null && brandId > 0) {
      request.fields['brand_id'] = '$brandId';
    }
    if (brandName != null && brandName.trim().isNotEmpty) {
      request.fields['brand_name'] = brandName.trim();
    }
    if (shortDescription != null && shortDescription.trim().isNotEmpty) {
      request.fields['short_description'] = shortDescription.trim();
    }
    if (longDescription != null && longDescription.trim().isNotEmpty) {
      request.fields['long_description'] = longDescription.trim();
    }
    if (shortName != null && shortName.trim().isNotEmpty) {
      request.fields['short_name'] = shortName.trim();
    }
    if (tags != null && tags.trim().isNotEmpty) {
      request.fields['tags'] = tags.trim();
    }
    if (sku != null && sku.trim().isNotEmpty) {
      request.fields['sku'] = sku.trim();
    }
    if (weight != null && weight.trim().isNotEmpty) {
      request.fields['weight'] = weight.trim();
    }
    if (seoTitle != null && seoTitle.trim().isNotEmpty) {
      request.fields['seo_title'] = seoTitle.trim();
    }
    if (seoDescription != null && seoDescription.trim().isNotEmpty) {
      request.fields['seo_description'] = seoDescription.trim();
    }
    request.files.add(await _imagePart('thumb_image', thumbImagePath));
    for (final path in galleryImagePaths) {
      if (path.trim().isEmpty) continue;
      request.files.add(await _imagePart('gallery_images[]', path));
    }
    for (var i = 0; i < colors.length; i++) {
      final color = colors[i];
      final colorName = (color['name'] ?? '').trim();
      if (colorName.isEmpty) continue;
      request.fields['colors[$i][name]'] = colorName;
      if ((color['price'] ?? '').trim().isNotEmpty) {
        request.fields['colors[$i][price]'] = color['price']!.trim();
      }
      if ((color['qty'] ?? '').trim().isNotEmpty) {
        request.fields['colors[$i][qty]'] = color['qty']!.trim();
      }
      final imagePath = (color['image'] ?? '').trim();
      if (imagePath.isNotEmpty) {
        request.files.add(await _imagePart('colors[$i][image]', imagePath));
      }
    }

    final streamed = await request.send();
    final response = await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<void> updateProduct({
    required String token,
    required int productId,
    required Map<String, String> fields,
    String? thumbImagePath,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.sellerUpdateProduct(productId)),
    );
    request.headers.addAll(_authHeaders(token));
    request.fields.addAll(fields);
    if (thumbImagePath != null && thumbImagePath.isNotEmpty) {
      request.files.add(await _imagePart('thumb_image', thumbImagePath));
    }
    final streamed = await request.send();
    await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
  }

  Future<Map<String, dynamic>> generateAiContent({
    required String token,
    required String productName,
    String? categoryName,
    String action = 'full',
    Map<String, dynamic>? existingContent,
  }) async {
    final body = <String, dynamic>{
      'action': action,
      'content_type': 'product',
      'product_name': productName,
      if (categoryName != null && categoryName.isNotEmpty)
        'category_name': categoryName,
      if (existingContent != null) 'existing_content': existingContent,
    };
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerAiGenerateContent),
        headers: _jsonHeaders(token),
        body: jsonEncode(body),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<List<SellerBulkImportModel>> fetchBulkImports(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerBulkImports),
        headers: _jsonHeaders(token),
      ),
    );
    final imports = response['imports'];
    if (imports is Map && imports['data'] is List) {
      return (imports['data'] as List)
          .whereType<Map>()
          .map(
            (e) => SellerBulkImportModel.fromMap(Map<String, dynamic>.from(e)),
          )
          .toList();
    }
    if (imports is List) {
      return imports
          .whereType<Map>()
          .map(
            (e) => SellerBulkImportModel.fromMap(Map<String, dynamic>.from(e)),
          )
          .toList();
    }
    return const [];
  }

  Future<SellerBulkImportModel> fetchBulkImport(String token, int id) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerBulkImportShow(id)),
        headers: _jsonHeaders(token),
      ),
    );
    final import = response['import'];
    if (import is Map) {
      return SellerBulkImportModel.fromMap(Map<String, dynamic>.from(import));
    }
    throw Exception('İçe aktarma kaydı bulunamadı');
  }

  /// Upload may return HTTP 422 while still queuing successfully (pending).
  Future<Map<String, dynamic>> uploadBulkImport({
    required String token,
    required String filePath,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.sellerBulkImportUpload),
    );
    request.headers.addAll(_authHeaders(token));
    request.files.add(
      await http.MultipartFile.fromPath(
        'import_file',
        filePath,
        filename: _basename(filePath),
      ),
    );
    final streamed = await request.send();
    final response = await http.Response.fromStream(streamed);
    final decoded = json.decode(response.body);
    if (decoded is Map<String, dynamic>) {
      if (response.statusCode == 200 ||
          response.statusCode == 201 ||
          (response.statusCode == 422 && decoded['import'] != null)) {
        return decoded;
      }
      final message = '${decoded['message'] ?? 'Yükleme başarısız'}';
      throw Exception(message);
    }
    throw Exception('Yükleme başarısız (${response.statusCode})');
  }

  Future<File> downloadBulkTemplate(String token, {bool sample = false}) async {
    final uri = Uri.parse(
      sample
          ? RemoteUrls.sellerBulkImportSample
          : RemoteUrls.sellerBulkImportTemplate,
    );
    final response = await _client.get(uri, headers: _authHeaders(token));
    if (response.statusCode != 200) {
      throw Exception('Şablon indirilemedi (${response.statusCode})');
    }
    final ext = sample ? 'xlsx' : 'csv';
    final file = File(
      '${Directory.systemTemp.path}/seyfibaba-urun-${sample ? 'ornek' : 'sablon'}.$ext',
    );
    await file.writeAsBytes(response.bodyBytes);
    return file;
  }

  Future<PaginatedSellerOrders> fetchOrders(
    String token, {
    String filter = 'all',
    int page = 1,
  }) async {
    final base = switch (filter) {
      'pending' => RemoteUrls.sellerPendingOrders,
      'progress' => RemoteUrls.sellerProgressOrders,
      'delivered' => RemoteUrls.sellerDeliveredOrders,
      'completed' => RemoteUrls.sellerCompletedOrders,
      'declined' => RemoteUrls.sellerDeclinedOrders,
      _ => RemoteUrls.sellerOrders,
    };
    final uri = Uri.parse(base).replace(queryParameters: {'page': '$page'});
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token)),
    );
    return PaginatedSellerOrders.fromResponse(
      Map<String, dynamic>.from(response),
    );
  }

  Future<SellerOrderDetail> fetchOrderDetail(String token, int orderId) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerOrderShow(orderId)),
        headers: _jsonHeaders(token),
      ),
    );
    return SellerOrderDetail.fromMap(Map<String, dynamic>.from(response));
  }

  Future<void> manualShipOrder(
    String token,
    int orderId, {
    required String carrierName,
    required String trackingNumber,
    String? trackingUrl,
  }) async {
    final cargoBody = <String, dynamic>{
      'carrier_name': carrierName.trim(),
      'tracking_number': trackingNumber.trim(),
      if (trackingUrl != null && trackingUrl.trim().isNotEmpty)
        'tracking_url': trackingUrl.trim(),
    };

    try {
      await NetworkParser.callClientWithCatchException(
        () => _client.post(
          Uri.parse(RemoteUrls.sellerManualShip(orderId)),
          headers: _jsonHeaders(token),
          body: jsonEncode(cargoBody),
        ),
      );
      return;
    } on UnauthorisedException catch (e) {
      if (e.statusCode != 404) rethrow;
    }

    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerUpdateOrderStatus(orderId)),
        headers: _jsonHeaders(token),
        body: jsonEncode({
          'order_status': 2,
          ...cargoBody,
        }),
      ),
    );
  }

  Future<void> updateOrderStatus(
    String token,
    int orderId, {
    required int orderStatus,
    String? carrierName,
    String? trackingNumber,
    String? trackingUrl,
  }) async {
    final body = <String, dynamic>{
      'order_status': orderStatus,
      if (carrierName != null && carrierName.trim().isNotEmpty)
        'carrier_name': carrierName.trim(),
      if (trackingNumber != null && trackingNumber.trim().isNotEmpty)
        'tracking_number': trackingNumber.trim(),
      if (trackingUrl != null && trackingUrl.trim().isNotEmpty)
        'tracking_url': trackingUrl.trim(),
    };
    await NetworkParser.callClientWithCatchException(
      () => _client.put(
        Uri.parse(RemoteUrls.sellerUpdateOrderStatus(orderId)),
        headers: _jsonHeaders(token),
        body: jsonEncode(body),
      ),
    );
  }

  Future<SellerEarningsSummary> fetchEarningsSummary(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerEarnings),
        headers: _jsonHeaders(token),
      ),
    );
    return SellerEarningsSummary.fromMap(Map<String, dynamic>.from(response));
  }

  Future<List<SellerEarningOrderItem>> fetchEarningOrders(
    String token, {
    int page = 1,
  }) async {
    final uri = Uri.parse(RemoteUrls.sellerEarningsOrders)
        .replace(queryParameters: {'page': '$page', 'per_page': '20'});
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token)),
    );
    final orders = response['orders'];
    final data = orders is Map ? orders['data'] : orders;
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map(
          (e) => SellerEarningOrderItem.fromMap(Map<String, dynamic>.from(e)),
        )
        .toList();
  }

  Future<SellerWithdrawBundle> fetchWithdrawBundle(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerWithdraws),
        headers: _jsonHeaders(token),
      ),
    );
    return SellerWithdrawBundle.fromMap(Map<String, dynamic>.from(response));
  }

  Future<List<SellerWithdrawMethod>> fetchWithdrawMethods(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerWithdrawCreateMeta),
        headers: _jsonHeaders(token),
      ),
    );
    final methods = response['methods'];
    if (methods is! List) return const [];
    return methods
        .whereType<Map>()
        .map((e) => SellerWithdrawMethod.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<String> createWithdrawRequest({
    required String token,
    required int methodId,
    required double amount,
    required String accountInfo,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerWithdraws),
        headers: _jsonHeaders(token),
        body: jsonEncode({
          'method_id': methodId,
          'withdraw_amount': amount,
          'account_info': accountInfo,
        }),
      ),
    );
    return '${response['notification'] ?? response['message'] ?? 'Talep gönderildi'}';
  }

  Future<List<SellerReturnRequest>> fetchReturnRequests(
    String token, {
    int? status,
    int page = 1,
  }) async {
    final params = <String, String>{'page': '$page', 'per_page': '20'};
    if (status != null) params['status'] = '$status';
    final uri = Uri.parse(RemoteUrls.sellerReturnRequests)
        .replace(queryParameters: params);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token)),
    );
    final returns = response['returns'];
    final data = returns is Map ? returns['data'] : returns;
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((e) => SellerReturnRequest.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<SellerReturnRequest> fetchReturnRequest(String token, int id) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerReturnRequestShow(id)),
        headers: _jsonHeaders(token),
      ),
    );
    final item = response['return'];
    if (item is Map) {
      return SellerReturnRequest.fromMap(Map<String, dynamic>.from(item));
    }
    throw Exception('İade talebi bulunamadı');
  }

  Future<String> approveReturnRequest(
    String token,
    int id, {
    String? sellerNote,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.put(
        Uri.parse(RemoteUrls.sellerReturnRequestApprove(id)),
        headers: _jsonHeaders(token),
        body: jsonEncode({
          if (sellerNote != null && sellerNote.trim().isNotEmpty)
            'seller_note': sellerNote.trim(),
        }),
      ),
    );
    return '${response['message'] ?? 'İade onaylandı'}';
  }

  Future<String> rejectReturnRequest(
    String token,
    int id, {
    required String reason,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.put(
        Uri.parse(RemoteUrls.sellerReturnRequestReject(id)),
        headers: _jsonHeaders(token),
        body: jsonEncode({'rejected_reason': reason.trim()}),
      ),
    );
    return '${response['message'] ?? 'İade reddedildi'}';
  }

  Future<Map<String, dynamic>> fetchShopProfile(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerShopProfile),
        headers: _jsonHeaders(token),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<String> updateShopProfile({
    required String token,
    required Map<String, String> fields,
    String? logoPath,
    String? bannerPath,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.sellerUpdateShop),
    );
    request.headers.addAll(_authHeaders(token));
    request.fields.addAll(fields);
    if (logoPath != null && logoPath.isNotEmpty) {
      request.files.add(await _imagePart('logo', logoPath));
    }
    if (bannerPath != null && bannerPath.isNotEmpty) {
      request.files.add(await _imagePart('banner_image', bannerPath));
    }
    final streamed = await request.send();
    final response = await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
    return '${response['notification'] ?? response['message'] ?? 'Güncellendi'}';
  }

  Future<List<Map<String, dynamic>>> fetchProductGallery(
    String token,
    int productId,
  ) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerProductGallery(productId)),
        headers: _jsonHeaders(token),
      ),
    );
    final gallery = response['gallery'];
    if (gallery is! List) return const [];
    return gallery
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<void> uploadProductGallery({
    required String token,
    required int productId,
    required List<String> imagePaths,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.sellerStoreProductGallery),
    );
    request.headers.addAll(_authHeaders(token));
    request.fields['product_id'] = '$productId';
    for (final path in imagePaths) {
      request.files.add(await _imagePart('images[]', path));
    }
    final streamed = await request.send();
    await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
  }

  Future<void> deleteProductGalleryImage(String token, int imageId) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.delete(
        Uri.parse(RemoteUrls.sellerDeleteProductImage(imageId)),
        headers: _jsonHeaders(token),
      ),
    );
  }

  Future<List<Map<String, dynamic>>> fetchProductVariants(
    String token,
    int productId,
  ) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerProductVariants(productId)),
        headers: _jsonHeaders(token),
      ),
    );
    final variants = response['variants'];
    if (variants is! List) return const [];
    return variants
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<void> createProductVariant({
    required String token,
    required int productId,
    required String name,
    int status = 1,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerStoreProductVariant),
        headers: _jsonHeaders(token),
        body: jsonEncode({
          'product_id': productId,
          'name': name,
          'status': status,
        }),
      ),
    );
  }

  Future<void> deleteProductVariant(String token, int variantId) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.delete(
        Uri.parse(RemoteUrls.sellerDeleteProductVariant(variantId)),
        headers: _jsonHeaders(token),
      ),
    );
  }

  Future<List<Map<String, dynamic>>> fetchVariantItems({
    required String token,
    required int productId,
    required int variantId,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(
          RemoteUrls.sellerProductVariantItems(
            productId: productId,
            variantId: variantId,
          ),
        ),
        headers: _jsonHeaders(token),
      ),
    );
    final items = response['variantItems'];
    if (items is! List) return const [];
    return items
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<void> createVariantItem({
    required String token,
    required int productId,
    required int variantId,
    required String name,
    required double price,
    int status = 1,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerStoreProductVariantItem),
        headers: _jsonHeaders(token),
        body: jsonEncode({
          'product_id': productId,
          'variant_id': variantId,
          'name': name,
          'price': price,
          'status': status,
        }),
      ),
    );
  }

  Future<void> deleteVariantItem(String token, int itemId) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.delete(
        Uri.parse(RemoteUrls.sellerDeleteProductVariantItem(itemId)),
        headers: _jsonHeaders(token),
      ),
    );
  }

  Future<String> fetchKycStatus(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerKycStatus),
        headers: _jsonHeaders(token),
      ),
    );
    final status = response['status'];
    if (status is Map) {
      return '${status['kyc_status'] ?? 'not_submitted'}';
    }
    return '${response['kyc_status'] ?? 'not_submitted'}';
  }

  Future<SellerKycBundle> fetchKycDocuments(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerKycDocuments),
        headers: _jsonHeaders(token),
      ),
    );
    return SellerKycBundle.fromMap(Map<String, dynamic>.from(response));
  }

  Future<SellerKycBundle> uploadKycDocument({
    required String token,
    required String documentType,
    required String filePath,
    String? iban,
    String? taxNumber,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.sellerKycUpload),
    );
    request.headers.addAll(_authHeaders(token));
    request.fields['document_type'] = documentType;
    if (iban != null && iban.trim().isNotEmpty) {
      request.fields['iban'] = iban.trim();
    }
    if (taxNumber != null && taxNumber.trim().isNotEmpty) {
      request.fields['tax_number'] = taxNumber.trim();
    }
    request.files.add(
      await http.MultipartFile.fromPath(
        'document',
        filePath,
        filename: _basename(filePath),
      ),
    );
    final streamed = await request.send();
    final response = await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
    return SellerKycBundle.fromMap(Map<String, dynamic>.from(response));
  }

  Future<void> deleteKycDocument(String token, int id) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.delete(
        Uri.parse(RemoteUrls.sellerKycDelete(id)),
        headers: _jsonHeaders(token),
      ),
    );
  }

  Future<SellerNotificationsPage> fetchSellerNotifications(
    String token, {
    int page = 1,
  }) async {
    final uri = Uri.parse(RemoteUrls.sellerNotifications).replace(
      queryParameters: {'page': '$page', 'per_page': '30'},
    );
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token)),
    );
    return SellerNotificationsPage.fromMap(Map<String, dynamic>.from(response));
  }

  Future<void> markSellerNotificationRead(String token, String id) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.put(
        Uri.parse(RemoteUrls.sellerNotificationRead(id)),
        headers: _jsonHeaders(token),
      ),
    );
  }

  Future<void> markAllSellerNotificationsRead(String token) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.put(
        Uri.parse(RemoteUrls.sellerNotificationsReadAll),
        headers: _jsonHeaders(token),
      ),
    );
  }

  Future<List<SellerAdminMessage>> fetchAdminMessages(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerContactAdmin),
        headers: _jsonHeaders(token),
      ),
    );
    final messages = response['messages'];
    if (messages is! List) return const [];
    return messages
        .whereType<Map>()
        .map((e) => SellerAdminMessage.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<String> sendAdminMessage({
    required String token,
    required String subject,
    required String message,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerContactAdmin),
        headers: _jsonHeaders(token),
        body: jsonEncode({
          'subject': subject.trim(),
          'message': message.trim(),
        }),
      ),
    );
    return '${response['message'] ?? 'Mesajınız admin\'e iletildi.'}';
  }

  Future<Map<String, dynamic>> updateKycInfo({
    required String token,
    required Map<String, String> fields,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerKycUpdateInfo),
        headers: _jsonHeaders(token),
        body: jsonEncode(fields),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<List<Map<String, dynamic>>> fetchInventory(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerInventory),
        headers: _jsonHeaders(token),
      ),
    );
    final products = response['products'];
    if (products is! List) return const [];
    return products
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<List<Map<String, dynamic>>> fetchStockout(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerStockoutProducts),
        headers: _jsonHeaders(token),
      ),
    );
    final products = response['products'];
    if (products is! List) return const [];
    return products
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<List<Map<String, dynamic>>> fetchLowStock(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerLowStock),
        headers: _jsonHeaders(token),
      ),
    );
    final products = response['products'];
    final data = products is Map ? products['data'] : products;
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<Map<String, dynamic>> fetchStockHistory(
    String token,
    int productId,
  ) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerStockHistory(productId)),
        headers: _jsonHeaders(token),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<void> addStock({
    required String token,
    required int productId,
    required int stockIn,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerAddStock),
        headers: _jsonHeaders(token),
        body: jsonEncode({
          'product_id': productId,
          'stock_in': stockIn,
        }),
      ),
    );
  }

  Future<Map<String, dynamic>> fetchBrands(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerBrands),
        headers: _jsonHeaders(token),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<void> createBrand({
    required String token,
    required String name,
    required String logoPath,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.sellerBrands),
    );
    request.headers.addAll(_authHeaders(token));
    request.fields['name'] = name.trim();
    request.files.add(await _imagePart('logo', logoPath));
    final streamed = await request.send();
    await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
  }

  Future<void> deleteBrand(String token, int id) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.delete(
        Uri.parse(RemoteUrls.sellerBrandDelete(id)),
        headers: _jsonHeaders(token),
      ),
    );
  }

  Future<List<Map<String, dynamic>>> fetchReviews(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerProductReviews),
        headers: _jsonHeaders(token),
      ),
    );
    final reviews = response['reviews'];
    if (reviews is! List) return const [];
    return reviews
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<Map<String, dynamic>> fetchSellerFaq(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerFaq),
        headers: _jsonHeaders(token),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> fetchSellerGuide(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.sellerGuide),
        headers: _jsonHeaders(token),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> chatAiAssistant({
    required String token,
    required String message,
    List<Map<String, String>> history = const [],
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.sellerAiAssistantChat),
        headers: _jsonHeaders(token),
        body: jsonEncode({
          'message': message,
          'history': history,
        }),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<void> createFullProduct({
    required String token,
    required Map<String, String> fields,
    required String thumbImagePath,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.sellerProducts),
    );
    request.headers.addAll(_authHeaders(token));
    request.fields.addAll(fields);
    request.files.add(await _imagePart('thumb_image', thumbImagePath));
    final streamed = await request.send();
    await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
  }

  Future<http.MultipartFile> _imagePart(String field, String path) async {
    var filename = _basename(path);
    if (!filename.contains('.')) {
      filename = '$filename.jpg';
    }
    return http.MultipartFile.fromPath(field, path, filename: filename);
  }

  String _basename(String path) {
    final normalized = path.replaceAll('\\', '/');
    final parts = normalized.split('/');
    return parts.isEmpty || parts.last.isEmpty ? 'file' : parts.last;
  }
}
