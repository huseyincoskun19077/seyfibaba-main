import 'package:equatable/equatable.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../../authentication/repository/auth_repository.dart';
import '../services/buyer_notification_service.dart';
import 'notification_state_model.dart';

part 'notification_state.dart';

class NotificationCubit extends Cubit<NotificationState> {
  final AuthRepository _repository;
  final LoginBloc _loginBloc;
  final BuyerNotificationService _buyerNotifications =
      BuyerNotificationService();

  int unreadCount = 0;
  int totalCount = 0;

  NotificationCubit(
      {required AuthRepository repository, required LoginBloc loginBloc})
      : _repository = repository,
        _loginBloc = loginBloc,
        super(const NotificationInitial());

  Future<void> updateUserForPushNotification(NotificationModel model) async {
    emit(NotificationLoading());
    final accessToken = _loginBloc.userInfo!.accessToken;
    final uri = Uri.parse(RemoteUrls.updateUserForPushNotification(accessToken))
        .replace(queryParameters: {
      'token': accessToken,
      'user_id': model.userId,
      'device_token': model.deviceToken,
    });
    debugPrint('notification-url $uri');
    final result = await _repository.updateUserForPushNotification(uri);
    result.fold(
      (failure) {
        emit(NotificationError(failure.message, failure.statusCode));
      },
      (success) {
        emit(NotificationLoaded(success));
      },
    );
  }

  Future<void> refreshUnreadCount() async {
    if (!_loginBloc.isLogedIn) {
      unreadCount = 0;
      totalCount = 0;
      emit(const NotificationUnread(0, 0));
      return;
    }

    try {
      final page = await _buyerNotifications.fetchNotifications(
        _loginBloc.userInfo!.accessToken,
        page: 1,
      );
      unreadCount = page.unreadCount;
      totalCount = page.totalCount;
      emit(NotificationUnread(unreadCount, totalCount));
    } catch (_) {}
  }

  void onPushReceived() {
    unreadCount += 1;
    totalCount += 1;
    emit(NotificationUnread(unreadCount, totalCount));
    refreshUnreadCount();
  }
}
