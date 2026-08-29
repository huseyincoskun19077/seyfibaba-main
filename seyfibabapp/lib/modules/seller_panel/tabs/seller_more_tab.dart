import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../services/seller_api_service.dart';

class SellerMoreTab extends StatelessWidget {
  const SellerMoreTab({super.key});

  @override
  Widget build(BuildContext context) {
    final items = <_MoreItem>[
      _MoreItem(
        Icons.verified_user_outlined,
        'KYC Doğrulama',
        'Vergi no, IBAN, belge ve doğrulama durumu',
        () => Navigator.pushNamed(context, RouteNames.sellerKycScreen),
      ),
      _MoreItem(
        Icons.reviews_outlined,
        'Yorumlar',
        'Ürün yorumlarını görüntüle',
        () => Navigator.pushNamed(context, RouteNames.sellerReviewsScreen),
      ),
      _MoreItem(
        Icons.branding_watermark_outlined,
        'Markalar',
        'Marka ekle ve logo yükle',
        () => Navigator.pushNamed(context, RouteNames.sellerBrandsScreen),
      ),
      _MoreItem(
        Icons.notifications_outlined,
        'Bildirimler',
        'Satıcı bildirimleri ve okundu işaretleme',
        () =>
            Navigator.pushNamed(context, RouteNames.sellerNotificationsScreen),
      ),
      _MoreItem(
        Icons.upload_file_outlined,
        'Excel Toplu Yükleme',
        'CSV/XLSX şablon, yükleme ve durum takibi',
        () => Navigator.pushNamed(context, RouteNames.sellerBulkImportScreen),
      ),
      _MoreItem(
        Icons.add_box_outlined,
        'Hızlı Ürün Ekle',
        'Fotoğraf + AI ile hızlı yayın',
        () => Navigator.pushNamed(context, RouteNames.sellerQuickProductScreen),
      ),
      _MoreItem(
        Icons.payments_outlined,
        'Kazanç / Çekim',
        'Özet, sipariş kazançları ve çekim talebi',
        () => Navigator.pushNamed(context, RouteNames.sellerEarningsScreen),
      ),
      _MoreItem(
        Icons.assignment_return_outlined,
        'İade Talepleri',
        'Listele, onayla veya reddet',
        () => Navigator.pushNamed(context, RouteNames.sellerReturnsScreen),
      ),
      _MoreItem(
        Icons.store_mall_directory_outlined,
        'Mağaza Profili',
        'Logo, banner, saat ve iletişim',
        () =>
            Navigator.pushNamed(context, RouteNames.sellerShopProfileScreen),
      ),
      _MoreItem(
        Icons.menu_book_outlined,
        'Şartlar ve Tanıtım',
        'Komisyon, kargo, sipariş, iade ve hakediş özeti',
        () => Navigator.pushNamed(context, RouteNames.sellerGuideScreen),
      ),
      _MoreItem(
        Icons.help_outline,
        'SSS',
        'Satıcı sıkça sorulan sorular',
        () => Navigator.pushNamed(context, RouteNames.sellerFaqScreen),
      ),
      _MoreItem(
        Icons.gavel_outlined,
        'Yasal Belgeler',
        'Mesafeli satış, KVKK ve diğer belgeler',
        () => Navigator.pushNamed(context, RouteNames.legalDocumentsHubScreen),
      ),
      _MoreItem(
        Icons.support_agent_outlined,
        'Admin Talebi',
        'Konulu mesaj gönder, geçmişi gör',
        () =>
            Navigator.pushNamed(context, RouteNames.sellerAdminContactScreen),
      ),
    ];

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      children: [
        ...items.map((item) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: ListTile(
              tileColor: HomeTheme.card,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(HomeTheme.radius),
                side: BorderSide(color: HomeTheme.border.withValues(alpha: 0.6)),
              ),
              leading: Icon(item.icon, color: HomeTheme.textDark),
              title: Text(
                item.title,
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
              subtitle: Text(
                item.subtitle,
                style: const TextStyle(fontSize: 12, color: HomeTheme.textMuted),
              ),
              trailing: item.title == 'Bildirimler'
                  ? const _SellerUnreadBadge()
                  : const Icon(Icons.chevron_right),
              onTap: item.onTap,
            ),
          );
        }),
        const SizedBox(height: 8),
        OutlinedButton(
          onPressed: () {
            Navigator.pushNamedAndRemoveUntil(
              context,
              RouteNames.mainPage,
              (route) => false,
            );
          },
          child: const Text('Alışverişe Dön'),
        ),
      ],
    );
  }
}

class _MoreItem {
  const _MoreItem(this.icon, this.title, this.subtitle, this.onTap);
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
}

class _SellerUnreadBadge extends StatefulWidget {
  const _SellerUnreadBadge();

  @override
  State<_SellerUnreadBadge> createState() => _SellerUnreadBadgeState();
}

class _SellerUnreadBadgeState extends State<_SellerUnreadBadge> {
  int _unread = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    try {
      final token = context.read<LoginBloc>().userInfo?.accessToken ?? '';
      if (token.isEmpty) return;
      final page = await SellerApiService().fetchSellerNotifications(token);
      if (!mounted) return;
      setState(() => _unread = page.unreadCount);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (_unread > 0)
          Container(
            constraints: const BoxConstraints(minWidth: 22),
            margin: const EdgeInsets.only(right: 6),
            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
            decoration: BoxDecoration(
              color: const Color(0xFFE53935),
              borderRadius: BorderRadius.circular(11),
            ),
            child: Text(
              _unread > 99 ? '99+' : '$_unread',
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 11,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        const Icon(Icons.chevron_right),
      ],
    );
  }
}
