import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import 'services/seller_api_service.dart';
import 'services/seller_auth_helper.dart';
import 'tabs/seller_dashboard_tab.dart';
import 'tabs/seller_more_tab.dart';
import 'tabs/seller_orders_tab.dart';
import 'tabs/seller_products_tab.dart';
import 'widgets/seller_ai_fab.dart';
import 'widgets/seller_kyc_banner.dart';

class SellerPanelScreen extends StatefulWidget {
  const SellerPanelScreen({super.key});

  @override
  State<SellerPanelScreen> createState() => _SellerPanelScreenState();
}

class _SellerPanelScreenState extends State<SellerPanelScreen> {
  final _service = SellerApiService();
  int _index = 0;
  String _kycStatus = 'approved';
  bool _sessionReady = false;
  String _token = '';

  static const _titles = ['Özet', 'Ürünler', 'Siparişler', 'Daha fazla'];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _prepareSession());
  }

  Future<void> _prepareSession() async {
    if (!mounted) return;
    if (!Utils.isLoggedIn(context) || !Utils.isSeller(context)) {
      setState(() => _sessionReady = true);
      return;
    }

    final loginBloc = context.read<LoginBloc>();
    var token = loginBloc.userInfo?.accessToken ?? '';
    final refreshed = await loginBloc.refreshAccessToken();
    if (refreshed != null && refreshed.isNotEmpty) {
      token = refreshed;
    }

    if (!mounted) return;
    setState(() {
      _token = token;
      _sessionReady = true;
    });
    _loadKyc(token);
  }

  Future<void> _loadKyc(String token) async {
    if (token.isEmpty) return;
    try {
      final status = await SellerAuthHelper.withAuthRetry(
        context,
        (authToken) => _service.fetchKycStatus(authToken),
      );
      if (!mounted) return;
      setState(() => _kycStatus = status);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    if (!Utils.isLoggedIn(context) || !Utils.isSeller(context)) {
      return Scaffold(
        backgroundColor: HomeTheme.bg,
        appBar: AppBar(
          title: Text(Language.sellerPanel),
          backgroundColor: HomeTheme.header,
          foregroundColor: HomeTheme.textDark,
          elevation: 0,
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              Language.sellerInactiveHint,
              textAlign: TextAlign.center,
              style: const TextStyle(color: HomeTheme.textMuted, height: 1.4),
            ),
          ),
        ),
      );
    }

    if (!_sessionReady) {
      return Scaffold(
        backgroundColor: HomeTheme.bg,
        appBar: AppBar(
          title: Text(Language.sellerPanel),
          backgroundColor: HomeTheme.header,
          foregroundColor: HomeTheme.textDark,
          elevation: 0,
        ),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    final token = _token.isNotEmpty
        ? _token
        : (context.read<LoginBloc>().userInfo?.accessToken ?? '');
    final pages = [
      SellerDashboardTab(token: token),
      SellerProductsTab(token: token, kycStatus: _kycStatus),
      SellerOrdersTab(token: token),
      const SellerMoreTab(),
    ];

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.dark.copyWith(
        statusBarColor: Colors.transparent,
      ),
      child: Scaffold(
        backgroundColor: HomeTheme.bg,
        appBar: AppBar(
          backgroundColor: HomeTheme.header,
          foregroundColor: HomeTheme.textDark,
          elevation: 0,
          title: Text(
            _titles[_index],
            style: const TextStyle(
              fontWeight: FontWeight.w800,
              color: HomeTheme.textDark,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).maybePop(),
              child: Text(
                Language.sellerBackToShop,
                style: const TextStyle(
                  color: HomeTheme.textDark,
                  fontWeight: FontWeight.w700,
                  fontSize: 12,
                ),
              ),
            ),
          ],
        ),
        body: Column(
          children: [
            SellerKycBanner(kycStatus: _kycStatus),
            Expanded(child: IndexedStack(index: _index, children: pages)),
          ],
        ),
        floatingActionButton: SellerAiFab(token: token),
        bottomNavigationBar: NavigationBar(
          selectedIndex: _index,
          onDestinationSelected: (i) => setState(() => _index = i),
          backgroundColor: HomeTheme.header,
          indicatorColor: HomeTheme.brandYellow.withValues(alpha: 0.35),
          destinations: [
            NavigationDestination(
              icon: const Icon(Icons.dashboard_outlined),
              selectedIcon: const Icon(Icons.dashboard),
              label: Language.sellerDashboard,
            ),
            NavigationDestination(
              icon: const Icon(Icons.inventory_2_outlined),
              selectedIcon: const Icon(Icons.inventory_2),
              label: Language.sellerProducts,
            ),
            NavigationDestination(
              icon: const Icon(Icons.receipt_long_outlined),
              selectedIcon: const Icon(Icons.receipt_long),
              label: Language.sellerOrders,
            ),
            NavigationDestination(
              icon: const Icon(Icons.more_horiz),
              selectedIcon: const Icon(Icons.more_horiz),
              label: Language.sellerMore,
            ),
          ],
        ),
      ),
    );
  }
}
