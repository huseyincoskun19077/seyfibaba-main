import 'dart:convert';

import 'package:http/http.dart' as http;

import '../../../core/data/datasources/network_parser.dart';
import '../../../core/remote_urls.dart';
import '../models/buyer_notification_model.dart';

class BuyerNotificationService {
  BuyerNotificationService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Map<String, String> _jsonHeaders(String token) => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      };

  Future<BuyerNotificationsPage> fetchNotifications(
    String token, {
    int page = 1,
  }) async {
    final uri = Uri.parse(RemoteUrls.userNotifications).replace(
      queryParameters: {
        'token': token,
        'page': '$page',
        'per_page': '30',
      },
    );
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _jsonHeaders(token)),
    );
    return BuyerNotificationsPage.fromMap(Map<String, dynamic>.from(response));
  }

  Future<void> markRead(String token, String id) async {
    final uri = Uri.parse(RemoteUrls.userNotificationRead(id)).replace(
      queryParameters: {'token': token},
    );
    await NetworkParser.callClientWithCatchException(
      () => _client.put(uri, headers: _jsonHeaders(token)),
    );
  }

  Future<void> markAllRead(String token) async {
    final uri = Uri.parse(RemoteUrls.userNotificationsReadAll).replace(
      queryParameters: {'token': token},
    );
    await NetworkParser.callClientWithCatchException(
      () => _client.put(uri, headers: _jsonHeaders(token)),
    );
  }

  Future<void> recordProductView(String token, int productId) async {
    final uri = Uri.parse(RemoteUrls.userProductView).replace(
      queryParameters: {'token': token},
    );
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        uri,
        headers: _jsonHeaders(token),
        body: jsonEncode({'product_id': productId}),
      ),
    );
  }
}
