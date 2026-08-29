import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../../core/router_name.dart';
import '../../firebase_options.dart';
import '../../modules/authentication/controller/login/login_bloc.dart';
import '../../modules/notification/controller/notification_cubit.dart';
import '../../modules/notification/controller/notification_state_model.dart';
import '../../modules/order/controllers/order/order_cubit.dart';
import '../../modules/salon_crm/services/salon_crm_session.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
}

class PushNotificationService {
  PushNotificationService._();

  static final PushNotificationService instance = PushNotificationService._();

  FirebaseMessaging get _messaging => FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  GlobalKey<NavigatorState>? _navigatorKey;
  bool _initialized = false;

  static const AndroidNotificationChannel _androidChannel =
      AndroidNotificationChannel(
    'seyfibaba_default',
    'Seyfibaba Bildirimleri',
    description: 'Siparis ve mesaj bildirimleri',
    importance: Importance.high,
  );

  Future<void> initialize({
    required GlobalKey<NavigatorState> navigatorKey,
  }) async {
    if (_initialized) {
      return;
    }

    _navigatorKey = navigatorKey;

    // Debug/emülatörde FCM ikinci isolate açıp VM bağlantısını koparıyor.
    if (kDebugMode) {
      _initialized = true;
      debugPrint('Push notifications: debug modda atlandı');
      return;
    }

    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

    const initSettings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/launcher_icon'),
    );

    await _localNotifications.initialize(
      settings: initSettings,
      onDidReceiveNotificationResponse: _onLocalNotificationTap,
    );

    await _localNotifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_androidChannel);

    await _requestPermission();

    FirebaseMessaging.onMessage.listen(_onForegroundMessage);
    FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);
    FirebaseMessaging.instance.onTokenRefresh.listen(_syncToken);

    final initial = await _messaging.getInitialMessage();
    if (initial != null) {
      Future.delayed(const Duration(milliseconds: 800), () {
        _handleNotificationTap(initial);
      });
    }

    _initialized = true;
  }

  Future<void> _requestPermission() async {
    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );
    debugPrint('FCM permission: ${settings.authorizationStatus}');
  }

  Future<void> registerDeviceToken(BuildContext context) async {
    if (kDebugMode) return;
    try {
      final loginBloc = context.read<LoginBloc>();
      if (!loginBloc.isLogedIn) {
        return;
      }

      final token = await _messaging.getToken();
      if (token == null || !context.mounted) {
        return;
      }

      await context
          .read<NotificationCubit>()
          .updateUserForPushNotification(
            NotificationModel(
              userId: loginBloc.userInfo!.user.id.toString(),
              deviceToken: token,
            ),
          )
          .timeout(const Duration(seconds: 8));
    } catch (e) {
      debugPrint('registerDeviceToken ignored: $e');
    }
  }

  Future<void> clearDeviceToken(BuildContext context) async {
    try {
      final loginBloc = context.read<LoginBloc>();
      if (!loginBloc.isLogedIn) {
        return;
      }

      final userId = loginBloc.userInfo!.user.id.toString();
      await context
          .read<NotificationCubit>()
          .updateUserForPushNotification(
            NotificationModel(
              userId: userId,
              deviceToken: '',
            ),
          )
          .timeout(const Duration(seconds: 3));

      if (!context.mounted) {
        return;
      }

      try {
        await _messaging.deleteToken();
      } catch (_) {}
    } catch (e) {
      debugPrint('clearDeviceToken ignored: $e');
    }
  }

  Future<void> _syncToken(String token) async {
    final context = _navigatorKey?.currentContext;
    if (context == null) {
      return;
    }

    final loginBloc = context.read<LoginBloc>();
    if (!loginBloc.isLogedIn) {
      return;
    }

    await context.read<NotificationCubit>().updateUserForPushNotification(
          NotificationModel(
            userId: loginBloc.userInfo!.user.id.toString(),
            deviceToken: token,
          ),
        );
  }

  void _onForegroundMessage(RemoteMessage message) {
    final notification = message.notification;
    final title =
        notification?.title ?? message.data['title']?.toString() ?? 'Seyfibaba';
    final body = notification?.body ?? message.data['body']?.toString() ?? '';

    _localNotifications.show(
      id: message.hashCode,
      title: title,
      body: body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          _androidChannel.id,
          _androidChannel.name,
          channelDescription: _androidChannel.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/launcher_icon',
        ),
      ),
      payload: jsonEncode(message.data),
    );

    final context = _navigatorKey?.currentContext;
    if (context != null) {
      context.read<NotificationCubit>().onPushReceived();
    }
  }

  void _onLocalNotificationTap(NotificationResponse response) {
    final payload = response.payload;
    if (payload == null || payload.isEmpty) {
      return;
    }

    try {
      final data = jsonDecode(payload) as Map<String, dynamic>;
      _navigateFromData(data);
    } catch (e) {
      debugPrint('Notification payload parse error: $e');
    }
  }

  void _handleNotificationTap(RemoteMessage message) {
    _navigateFromData(message.data);
  }

  void _navigateFromData(Map<String, dynamic> data) {
    final context = _navigatorKey?.currentContext;
    if (context == null) {
      return;
    }

    final type = data['type']?.toString() ?? '';

    switch (type) {
      case 'order':
        final orderNumber = data['order_number']?.toString() ?? '';
        if (orderNumber.isNotEmpty) {
          context.read<OrderCubit>().tempTrackOrderId(orderNumber);
          Navigator.of(context).pushNamed(RouteNames.singleOrderScreen);
        } else {
          Navigator.of(context).pushNamed(
            RouteNames.orderScreen,
            arguments: false,
          );
        }
        break;
      case 'seller_new_order':
        Navigator.of(context).pushNamed(RouteNames.sellerPanelScreen);
        break;
      case 'stock_alert':
        final productId =
            int.tryParse(data['product_id']?.toString() ?? '') ?? 0;
        if (productId > 0) {
          Navigator.of(context).pushNamed(
            RouteNames.sellerEditProductScreen,
            arguments: productId,
          );
        } else {
          Navigator.of(context).pushNamed(RouteNames.sellerPanelScreen);
        }
        break;
      case 'kyc_status':
      case 'kyc_reminder':
        Navigator.of(context).pushNamed(RouteNames.sellerKycScreen);
        break;
      case 'second_hand_message':
        final conversationId =
            int.tryParse(data['conversation_id']?.toString() ?? '') ?? 0;
        if (conversationId > 0) {
          Navigator.of(context).pushNamed(
            RouteNames.secondHandConversationScreen,
            arguments: conversationId,
          );
        } else {
          Navigator.of(context).pushNamed(
            RouteNames.secondHandHubScreen,
            arguments: 3,
          );
        }
        break;
      case 'salon_crm_reminder':
        SalonCrmSession.read().then((session) {
          final nav = _navigatorKey?.currentState;
          if (nav == null) return;
          final role = session?['role'] ?? '';
          if (role == 'customer') {
            nav.pushNamed(RouteNames.salonCrmCustomerHomeScreen);
          } else if (session != null) {
            nav.pushNamed(RouteNames.salonCrmAppointmentsScreen);
          } else {
            nav.pushNamed(RouteNames.salonCrmGateScreen);
          }
        });
        break;
      case 'product_view_reminder':
      case 'admin_broadcast':
      case 'campaign':
      case 'discount':
        final productSlug = data['product_slug']?.toString() ?? '';
        if (productSlug.isNotEmpty) {
          Navigator.of(context).pushNamed(
            RouteNames.productDetailsScreen,
            arguments: productSlug,
          );
        } else {
          Navigator.of(context).pushNamed(RouteNames.notificationScreen);
        }
        break;
      default:
        Navigator.of(context).pushNamed(RouteNames.notificationScreen);
    }
  }
}
