import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/error/exception.dart';
import '../../authentication/controller/login/login_bloc.dart';

class SellerAuthHelper {
  static Future<T> withAuthRetry<T>(
    BuildContext context,
    Future<T> Function(String token) request,
  ) async {
    final loginBloc = context.read<LoginBloc>();
    var token = loginBloc.userInfo?.accessToken ?? '';
    if (token.isEmpty) {
      throw const UnauthorisedException('UnAuthenticated', 401);
    }

    try {
      return await request(token);
    } on UnauthorisedException catch (e) {
      if (e.statusCode != 401) rethrow;
      final refreshed = await loginBloc.refreshAccessToken();
      if (refreshed == null || refreshed.isEmpty) rethrow;
      return request(refreshed);
    }
  }
}
