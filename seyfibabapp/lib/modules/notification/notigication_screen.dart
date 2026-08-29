import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/rounded_app_bar.dart';
import '../authentication/controller/login/login_bloc.dart';
import '../home/widgets/home_theme.dart';
import 'controller/notification_cubit.dart';
import 'models/buyer_notification_model.dart';
import 'services/buyer_notification_service.dart';
import 'component/empty_notification.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  final _service = BuyerNotificationService();
  List<BuyerNotificationItem> _items = const [];
  int _unread = 0;
  int _total = 0;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  String? get _token {
    final loginBloc = context.read<LoginBloc>();
    if (!loginBloc.isLogedIn) {
      return null;
    }
    return loginBloc.userInfo!.accessToken;
  }

  Future<void> _load() async {
    final token = _token;
    if (token == null) {
      setState(() {
        _loading = false;
        _items = const [];
        _unread = 0;
        _total = 0;
        _error = null;
      });
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final page = await _service.fetchNotifications(token);
      if (!mounted) return;
      setState(() {
        _items = page.items;
        _unread = page.unreadCount;
        _total = page.totalCount;
        _loading = false;
      });
      context.read<NotificationCubit>().refreshUnreadCount();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  Future<void> _onTap(BuyerNotificationItem item) async {
    switch (item.type) {
      case 'order':
        if (item.orderNumber.isNotEmpty) {
          await Navigator.pushNamed(
            context,
            RouteNames.singleOrderScreen,
          );
        } else {
          await Navigator.pushNamed(
            context,
            RouteNames.orderScreen,
            arguments: false,
          );
        }
        break;
      case 'product_view_reminder':
      case 'admin_broadcast':
        if (item.productSlug.isNotEmpty) {
          await Navigator.pushNamed(
            context,
            RouteNames.productDetailsScreen,
            arguments: item.productSlug,
          );
        }
        break;
      case 'campaign':
      case 'discount':
        if (item.productSlug.isNotEmpty) {
          await Navigator.pushNamed(
            context,
            RouteNames.productDetailsScreen,
            arguments: item.productSlug,
          );
        }
        break;
    }

    if (!mounted) return;
    await _markOne(item);
  }

  Future<void> _markOne(BuyerNotificationItem item) async {
    if (!item.isUnread) return;
    final token = _token;
    if (token == null) return;

    try {
      await _service.markRead(token, item.id);
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _markAll() async {
    final token = _token;
    if (token == null) return;

    try {
      Utils.loadingDialog(context);
      await _service.markAllRead(token);
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Tüm bildirimler okundu');
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final loggedIn = context.watch<LoginBloc>().isLogedIn;

    return Scaffold(
      appBar: RoundedAppBar(
        bgColor: Colors.white,
        titleText: Language.notifications,
        textColor: blackColor,
        options: loggedIn && _unread > 0
            ? [
                TextButton(
                  onPressed: _markAll,
                  child: const Text('Tümünü oku'),
                ),
              ]
            : null,
      ),
      body: !loggedIn
          ? const EmptyNotification()
          : _loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _error != null
                      ? ListView(
                          children: [
                            const SizedBox(height: 80),
                            Center(child: Text(_error!)),
                            const SizedBox(height: 12),
                            Center(
                              child: FilledButton(
                                onPressed: _load,
                                child: const Text('Tekrar Dene'),
                              ),
                            ),
                          ],
                        )
                      : _items.isEmpty
                          ? const EmptyNotification()
                          : ListView.separated(
                              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                              itemCount: _items.length + 1,
                              separatorBuilder: (_, __) =>
                                  const SizedBox(height: 10),
                              itemBuilder: (context, index) {
                                if (index == 0) {
                                  return _NotificationSummaryBar(
                                    total: _total,
                                    unread: _unread,
                                  );
                                }
                                final item = _items[index - 1];
                                return _BuyerNotificationCard(
                                  item: item,
                                  onTap: () => _onTap(item),
                                );
                              },
                            ),
                ),
    );
  }
}

class _NotificationSummaryBar extends StatelessWidget {
  const _NotificationSummaryBar({
    required this.total,
    required this.unread,
  });

  final int total;
  final int unread;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _CountChip(
          label: 'Gelen',
          value: total,
          color: HomeTheme.textDark,
        ),
        const SizedBox(width: 8),
        _CountChip(
          label: 'Okunmamış',
          value: unread,
          color: const Color(0xFFE53935),
        ),
      ],
    );
  }
}

class _CountChip extends StatelessWidget {
  const _CountChip({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final int value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        '$label $value',
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
      ),
    );
  }
}

class _BuyerNotificationCard extends StatelessWidget {
  const _BuyerNotificationCard({required this.item, required this.onTap});

  final BuyerNotificationItem item;
  final VoidCallback onTap;

  IconData get _icon => switch (item.type) {
        'order' => Icons.local_shipping_outlined,
        'campaign' || 'discount' => Icons.local_offer_outlined,
        'product_view_reminder' => Icons.visibility_outlined,
        'admin_broadcast' => Icons.campaign_outlined,
        _ => Icons.notifications_outlined,
      };

  Color get _iconColor => switch (item.type) {
        'order' => const Color(0xFF34A853),
        'campaign' || 'discount' => const Color(0xFFE67E22),
        'admin_broadcast' => const Color(0xFF5B8DEF),
        _ => HomeTheme.brandYellow,
      };

  String get _timeLabel {
    final raw = item.createdAt;
    final parsed = DateTime.tryParse(raw);
    if (parsed == null) {
      return raw.length >= 16 ? raw.substring(0, 16).replaceAll('T', ' ') : raw;
    }
    final local = parsed.toLocal();
    final now = DateTime.now();
    final diff = now.difference(local);
    if (diff.inMinutes < 1) return 'Şimdi';
    if (diff.inHours < 1) return '${diff.inMinutes} dk önce';
    if (diff.inHours < 24 && now.day == local.day) {
      return '${diff.inHours} sa önce';
    }
    return '${local.day.toString().padLeft(2, '0')}.${local.month.toString().padLeft(2, '0')}.${local.year} ${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(HomeTheme.radius),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: HomeTheme.cardDecoration(
            color: item.isUnread
                ? HomeTheme.brandYellow.withValues(alpha: 0.12)
                : null,
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: _iconColor.withValues(alpha: 0.16),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(_icon, color: _iconColor, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            item.title,
                            style: TextStyle(
                              fontWeight: item.isUnread
                                  ? FontWeight.w800
                                  : FontWeight.w600,
                              color: HomeTheme.textDark,
                              fontSize: 15,
                            ),
                          ),
                        ),
                        if (item.isUnread)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFFE53935),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Text(
                              'Yeni',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ),
                      ],
                    ),
                    if (item.body.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(
                        item.body,
                        style: const TextStyle(
                          color: HomeTheme.textMuted,
                          fontSize: 13,
                          height: 1.35,
                        ),
                      ),
                    ],
                    const SizedBox(height: 8),
                    Text(
                      _timeLabel,
                      style: const TextStyle(
                        fontSize: 11,
                        color: HomeTheme.textMuted,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
