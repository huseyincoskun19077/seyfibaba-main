import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../core/data/datasources/network_parser.dart';
import '../../../core/remote_urls.dart';
import '../model/buyer_return_model.dart';

class BuyerReturnService {
  BuyerReturnService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Map<String, String> _authHeaders(String token) => {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };

  Future<Map<int, BuyerReturnableItem>> fetchReturnableItems({
    required String token,
    required String orderId,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.userReturnableItems(orderId, token)),
        headers: _authHeaders(token),
      ),
    );

    final items = response['items'];
    if (items is! List) return {};

    final map = <int, BuyerReturnableItem>{};
    for (final raw in items.whereType<Map>()) {
      final item = BuyerReturnableItem.fromMap(Map<String, dynamic>.from(raw));
      if (item.orderProductId > 0) {
        map[item.orderProductId] = item;
      }
    }
    return map;
  }

  Future<List<BuyerReturnRequest>> fetchReturnRequests({
    required String token,
    int? status,
  }) async {
    final params = <String, String>{'per_page': '30'};
    if (status != null) params['status'] = '$status';

    final uri = Uri.parse(RemoteUrls.userReturnRequests(token))
        .replace(queryParameters: {
      ...Uri.parse(RemoteUrls.userReturnRequests(token)).queryParameters,
      ...params,
    });

    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _authHeaders(token)),
    );

    final returns = response['returns'];
    final data = returns is Map ? returns['data'] : returns;
    if (data is! List) return const [];

    return data
        .whereType<Map>()
        .map((e) => BuyerReturnRequest.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<String> createReturnRequest({
    required String token,
    required int orderId,
    required int orderProductId,
    required String reason,
    required String details,
    required int qty,
    List<File> images = const [],
  }) async {
    final uri = Uri.parse(RemoteUrls.userCreateReturnRequest(token));
    final request = http.MultipartRequest('POST', uri);
    request.headers.addAll(_authHeaders(token));
    request.fields['order_id'] = '$orderId';
    request.fields['order_product_id'] = '$orderProductId';
    request.fields['reason'] = reason;
    request.fields['details'] = details;
    request.fields['qty'] = '$qty';

    for (final image in images) {
      request.files.add(
        await http.MultipartFile.fromPath('images[]', image.path),
      );
    }

    final streamed = await request.send();
    final response = await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );

    return '${response['message'] ?? 'İade talebi alındı'}';
  }

  Future<String> cancelReturnRequest({
    required String token,
    required int id,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.put(
        Uri.parse(RemoteUrls.userCancelReturnRequest(id, token)),
        headers: _authHeaders(token),
      ),
    );
    return '${response['message'] ?? 'İade talebi iptal edildi'}';
  }

  Future<String> submitReturnTracking({
    required String token,
    required int id,
    required String trackingNumber,
    String? carrier,
    String? trackingUrl,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.put(
        Uri.parse(RemoteUrls.userSubmitReturnTracking(id, token)),
        headers: {
          ..._authHeaders(token),
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'buyer_return_tracking_number': trackingNumber,
          if (carrier != null && carrier.trim().isNotEmpty)
            'buyer_return_carrier': carrier.trim(),
          if (trackingUrl != null && trackingUrl.trim().isNotEmpty)
            'buyer_return_tracking_url': trackingUrl.trim(),
        }),
      ),
    );
    return '${response['message'] ?? 'Takip bilgisi kaydedildi'}';
  }
}
