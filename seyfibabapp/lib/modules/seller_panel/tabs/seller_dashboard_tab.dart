import 'package:flutter/material.dart';

import '../../../core/error/exception.dart';
import '../../../core/router_name.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_dashboard_model.dart';
import '../services/seller_api_service.dart';
import '../services/seller_auth_helper.dart';

class SellerDashboardTab extends StatefulWidget {
  const SellerDashboardTab({super.key, required this.token});

  final String token;

  @override
  State<SellerDashboardTab> createState() => _SellerDashboardTabState();
}

class _SellerDashboardTabState extends State<SellerDashboardTab> {
  final _service = SellerApiService();
  late Future<SellerDashboardModel> _future;

  @override
  void initState() {
    super.initState();
    _future = _loadDashboard();
  }

  Future<SellerDashboardModel> _loadDashboard() {
    return SellerAuthHelper.withAuthRetry(
      context,
      (token) => _service.fetchDashboard(token),
    );
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _loadDashboard();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<SellerDashboardModel>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return _ErrorState(
            error: snapshot.error,
            onRetry: _refresh,
            onLogin: () {
              Navigator.pushNamed(context, RouteNames.authenticationScreen);
            },
          );
        }
        final data = snapshot.data!;
        return RefreshIndicator(
          onRefresh: _refresh,
          color: HomeTheme.brandYellow,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            children: [
              if (data.shopName.isNotEmpty) ...[
                Text(
                  data.shopName,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: HomeTheme.textDark,
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Mağaza özeti',
                  style: TextStyle(color: HomeTheme.textMuted, fontSize: 13),
                ),
                const SizedBox(height: 16),
              ],
              _StatGrid(
                items: [
                  _StatItem('Bugün sipariş', '${data.todayTotalOrder}'),
                  _StatItem(
                    'Bugün kazanç',
                    Utils.formatPrice(data.todayEarning, context),
                  ),
                  _StatItem('Bu ay sipariş', '${data.monthlyTotalOrder}'),
                  _StatItem(
                    'Bu ay kazanç',
                    Utils.formatPrice(data.thisMonthEarning, context),
                  ),
                  _StatItem('Toplam sipariş', '${data.totalOrder}'),
                  _StatItem('Tamamlanan', '${data.totalCompleteOrder}'),
                  _StatItem('Ürün sayısı', '${data.totalProduct}'),
                  _StatItem(
                    'Toplam kazanç',
                    Utils.formatPrice(data.totalEarning, context),
                  ),
                  _StatItem(
                    'Çekilen',
                    Utils.formatPrice(data.totalWithdraw, context),
                  ),
                  _StatItem('Yorum', '${data.reviews}'),
                  _StatItem('Rapor', '${data.reports}'),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}

class _StatItem {
  const _StatItem(this.label, this.value);
  final String label;
  final String value;
}

class _StatGrid extends StatelessWidget {
  const _StatGrid({required this.items});

  final List<_StatItem> items;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final width = (constraints.maxWidth - 12) / 2;
        return Wrap(
          spacing: 12,
          runSpacing: 12,
          children: items.map((item) {
            return SizedBox(
              width: width,
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: HomeTheme.cardDecoration(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.label,
                      style: const TextStyle(
                        color: HomeTheme.textMuted,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      item.value,
                      style: const TextStyle(
                        color: HomeTheme.textDark,
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        );
      },
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({
    required this.error,
    required this.onRetry,
    required this.onLogin,
  });

  final Object? error;
  final VoidCallback onRetry;
  final VoidCallback onLogin;

  @override
  Widget build(BuildContext context) {
    final message = '$error';
    final isUnauthorized = error is UnauthorisedException &&
        (error as UnauthorisedException).statusCode == 401;
    final isForbidden = !isUnauthorized &&
        (message.contains('403') ||
            message.toLowerCase().contains('seller') ||
            message.toLowerCase().contains('inactive'));
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              isUnauthorized
                  ? 'Oturumunuz sona erdi. Lütfen tekrar giriş yapın.'
                  : isForbidden
                      ? 'Satıcı hesabınız aktif değil veya erişim yok.'
                      : 'Özet yüklenemedi.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: HomeTheme.textDark,
                fontWeight: FontWeight.w700,
              ),
            ),
            if (!isUnauthorized) ...[
              const SizedBox(height: 8),
              Text(
                message,
                textAlign: TextAlign.center,
                maxLines: 4,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: HomeTheme.textMuted, fontSize: 12),
              ),
            ],
            const SizedBox(height: 16),
            if (isUnauthorized)
              FilledButton(
                onPressed: onLogin,
                style: FilledButton.styleFrom(
                  backgroundColor: HomeTheme.brandYellow,
                  foregroundColor: HomeTheme.textDark,
                ),
                child: const Text('Giriş Yap'),
              )
            else
              FilledButton(
                onPressed: onRetry,
                style: FilledButton.styleFrom(
                  backgroundColor: HomeTheme.brandYellow,
                  foregroundColor: HomeTheme.textDark,
                ),
                child: const Text('Tekrar Dene'),
              ),
          ],
        ),
      ),
    );
  }
}
