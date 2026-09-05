import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_notification_model.dart';
import '../services/seller_api_service.dart';

class SellerNotificationsScreen extends StatefulWidget {
  const SellerNotificationsScreen({super.key});

  @override
  State<SellerNotificationsScreen> createState() =>
      _SellerNotificationsScreenState();
}

class _SellerNotificationsScreenState extends State<SellerNotificationsScreen> {
  final _service = SellerApiService();
  List<SellerNotificationItem> _items = const [];
  int _unread = 0;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final page = await _service.fetchSellerNotifications(_token);
      if (!mounted) return;
      setState(() {
        _items = page.items;
        _unread = page.unreadCount;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  Future<void> _onTap(SellerNotificationItem item) async {
    if (item.type == 'stock_alert') {
      if (item.productId != null && item.productId! > 0) {
        await Navigator.pushNamed(
          context,
          RouteNames.sellerEditProductScreen,
          arguments: item.productId,
        );
      } else {
        await Navigator.pushNamed(context, RouteNames.sellerPanelScreen);
      }
    } else if (item.type == 'kyc_status' || item.type == 'kyc_reminder') {
      await Navigator.pushNamed(context, RouteNames.sellerKycScreen);
    } else if (item.type == 'seller_new_order') {
      await Navigator.pushNamed(context, RouteNames.sellerPanelScreen);
    } else if (item.type == 'seller_withdraw_approved') {
      await Navigator.pushNamed(context, RouteNames.sellerEarningsScreen);
    }

    if (!mounted) return;
    await _markOne(item);
  }

  Future<void> _markOne(SellerNotificationItem item) async {
    if (!item.isUnread) return;
    try {
      await _service.markSellerNotificationRead(_token, item.id);
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _markAll() async {
    try {
      Utils.loadingDialog(context);
      await _service.markAllSellerNotificationsRead(_token);
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
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Bildirimler'),
            if (_items.isNotEmpty)
              Text(
                '${_items.length} gelen · $_unread okunmamış',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: HomeTheme.textMuted,
                ),
              ),
          ],
        ),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
        actions: [
          if (_unread > 0)
            TextButton(
              onPressed: _markAll,
              child: const Text('Tümünü oku'),
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              color: HomeTheme.brandYellow,
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
                      ? ListView(
                          children: const [
                            SizedBox(height: 120),
                            Center(
                              child: Text(
                                'Bildirim yok',
                                style: TextStyle(color: HomeTheme.textMuted),
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                          itemCount: _items.length,
                          separatorBuilder: (_, __) =>
                              const SizedBox(height: 10),
                          itemBuilder: (context, index) {
                            final item = _items[index];
                            return InkWell(
                              borderRadius:
                                  BorderRadius.circular(HomeTheme.radius),
                              onTap: () => _onTap(item),
                              child: Container(
                                padding: const EdgeInsets.all(14),
                                decoration: HomeTheme.cardDecoration(
                                  color: item.isUnread
                                      ? HomeTheme.brandYellow
                                          .withValues(alpha: 0.12)
                                      : null,
                                ),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      width: 44,
                                      height: 44,
                                      decoration: BoxDecoration(
                                        color: HomeTheme.brandYellow
                                            .withValues(alpha: 0.2),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Icon(
                                        item.type == 'seller_new_order'
                                            ? Icons.shopping_bag_outlined
                                            : item.type == 'stock_alert'
                                                ? Icons.inventory_2_outlined
                                                : item.type ==
                                                        'seller_withdraw_approved'
                                                    ? Icons
                                                        .account_balance_outlined
                                                    : Icons
                                                        .notifications_outlined,
                                        color: HomeTheme.textDark,
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
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
                                                  ),
                                                ),
                                              ),
                                              if (item.isUnread)
                                                Container(
                                                  padding:
                                                      const EdgeInsets.symmetric(
                                                    horizontal: 8,
                                                    vertical: 3,
                                                  ),
                                                  decoration: BoxDecoration(
                                                    color:
                                                        const Color(0xFFE53935),
                                                    borderRadius:
                                                        BorderRadius.circular(
                                                      10,
                                                    ),
                                                  ),
                                                  child: const Text(
                                                    'Yeni',
                                                    style: TextStyle(
                                                      color: Colors.white,
                                                      fontSize: 10,
                                                      fontWeight:
                                                          FontWeight.w800,
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
                                          const SizedBox(height: 6),
                                          Text(
                                            item.createdAt.length >= 16
                                                ? item.createdAt
                                                    .substring(0, 16)
                                                    .replaceAll('T', ' ')
                                                : item.createdAt,
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
                            );
                          },
                        ),
            ),
    );
  }
}
