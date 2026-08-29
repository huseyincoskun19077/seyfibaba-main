import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/capitalized_word.dart';
import '../../../widgets/custom_image.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../../home/widgets/home_theme.dart';
import '../controllers/updated_info/updated_info_cubit.dart';

class ProfileHeader extends StatelessWidget {
  const ProfileHeader({super.key});

  @override
  Widget build(BuildContext context) {
    context.watch<LoginBloc>();
    context.watch<UserProfileInfoCubit>();

    final loginBloc = context.read<LoginBloc>();
    final profileCubit = context.read<UserProfileInfoCubit>();
    final isLoggedIn = Utils.isLoggedIn(context);

    final name = !isLoggedIn
        ? 'Misafir'
        : (profileCubit.updatedInfo?.updateUserInfo.name ??
            loginBloc.userInfo?.user.name ??
            'Kullanıcı');

    final user = loginBloc.userInfo?.user;
    final phone = user?.phone?.trim();
    final subtitle = !isLoggedIn
        ? 'Giriş yaparak hesabınızı yönetin'
        : ((phone != null && phone.isNotEmpty) ? phone : (user?.email ?? ''));

    final rawAvatar = !isLoggedIn
        ? ''
        : (profileCubit.updatedInfo?.updateUserInfo.image ??
            profileCubit.updatedInfo?.defaultImage?.image ??
            loginBloc.userInfo?.user.image ??
            '');

    final avatarUrl = rawAvatar.isEmpty
        ? ''
        : CustomImage.resolveNetworkUrl(rawAvatar);

    void requireLogin(VoidCallback action) {
      if (isLoggedIn) {
        action();
      } else {
        Utils.showSnackBarWithLogin(
          context,
          'Devam etmek için giriş yapın',
          () => Navigator.pushNamedAndRemoveUntil(
            context,
            RouteNames.authenticationScreen,
            (route) => false,
          ),
        );
      }
    }

    return Container(
      color: HomeTheme.header,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Profil',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.w800,
                      color: HomeTheme.textDark,
                      letterSpacing: -0.3,
                    ),
                  ),
                  const SizedBox(height: 16),
                  _UserCard(
                    name: name,
                    subtitle: subtitle,
                    avatarUrl: avatarUrl,
                    isLoggedIn: isLoggedIn,
                    onEdit: () => requireLogin(
                      () => Navigator.pushNamed(
                        context,
                        RouteNames.profileEditScreen,
                      ),
                    ),
                    onLogin: () => Navigator.pushNamedAndRemoveUntil(
                      context,
                      RouteNames.authenticationScreen,
                      (route) => false,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: _QuickActions(
              onOrder: () => requireLogin(
                () => Navigator.pushNamed(
                  context,
                  RouteNames.orderScreen,
                  arguments: false,
                ),
              ),
              onCart: () =>
                  Navigator.pushNamed(context, RouteNames.guestCartScreen),
              onOffers: () =>
                  Navigator.pushNamed(context, RouteNames.flashScreen),
              onWishlist: () => requireLogin(
                () => Navigator.pushNamed(
                  context,
                  RouteNames.wishlistOfferScreen,
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          const Divider(height: 1, color: HomeTheme.headerBorder),
        ],
      ),
    );
  }
}

class _UserCard extends StatelessWidget {
  const _UserCard({
    required this.name,
    required this.subtitle,
    required this.avatarUrl,
    required this.isLoggedIn,
    required this.onEdit,
    required this.onLogin,
  });

  final String name;
  final String subtitle;
  final String avatarUrl;
  final bool isLoggedIn;
  final VoidCallback onEdit;
  final VoidCallback onLogin;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: HomeTheme.cardDecoration(),
      child: Row(
        children: [
          _Avatar(url: avatarUrl, isLoggedIn: isLoggedIn),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w700,
                    color: HomeTheme.textDark,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  subtitle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 12,
                    color: HomeTheme.textMuted,
                    fontWeight: FontWeight.w400,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          if (isLoggedIn)
            _ActionChip(
              label: 'Düzenle',
              icon: Icons.edit_outlined,
              onTap: onEdit,
            )
          else
            _ActionChip(
              label: 'Giriş',
              icon: Icons.login_rounded,
              filled: true,
              onTap: onLogin,
            ),
        ],
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.url, required this.isLoggedIn});

  final String url;
  final bool isLoggedIn;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(
          color: HomeTheme.brandYellow.withValues(alpha: 0.85),
          width: 2.5,
        ),
      ),
      child: CircleAvatar(
        radius: 30,
        backgroundColor: HomeTheme.bg,
        backgroundImage: url.isNotEmpty ? NetworkImage(url) : null,
        child: url.isEmpty
            ? Icon(
                isLoggedIn ? Icons.person_rounded : Icons.person_outline_rounded,
                size: 32,
                color: HomeTheme.textMuted,
              )
            : null,
      ),
    );
  }
}

class _ActionChip extends StatelessWidget {
  const _ActionChip({
    required this.label,
    required this.icon,
    required this.onTap,
    this.filled = false,
  });

  final String label;
  final IconData icon;
  final VoidCallback onTap;
  final bool filled;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: filled ? HomeTheme.brandYellow : Colors.transparent,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            border: filled
                ? null
                : Border.all(color: HomeTheme.headerBorder),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                icon,
                size: 16,
                color: HomeTheme.textDark,
              ),
              const SizedBox(width: 4),
              Text(
                label,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: HomeTheme.textDark,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QuickActions extends StatelessWidget {
  const _QuickActions({
    required this.onOrder,
    required this.onCart,
    required this.onOffers,
    required this.onWishlist,
  });

  final VoidCallback onOrder;
  final VoidCallback onCart;
  final VoidCallback onOffers;
  final VoidCallback onWishlist;

  @override
  Widget build(BuildContext context) {
    final items = [
      (
        Icons.receipt_long_outlined,
        Language.order.capitalizeByWord(),
        onOrder,
      ),
      (
        Icons.shopping_bag_outlined,
        Language.cart.capitalizeByWord(),
        onCart,
      ),
      (
        Icons.local_offer_outlined,
        Language.offers.capitalizeByWord(),
        onOffers,
      ),
      (
        Icons.favorite_border_rounded,
        Language.wishlist.capitalizeByWord(),
        onWishlist,
      ),
    ];

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
      decoration: HomeTheme.cardDecoration(),
      child: Row(
        children: items.map((item) {
          return Expanded(
            child: InkWell(
              onTap: item.$3,
              borderRadius: BorderRadius.circular(12),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: HomeTheme.brandYellow.withValues(alpha: 0.16),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(
                      item.$1,
                      size: 22,
                      color: HomeTheme.textDark,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    item.$2,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: HomeTheme.textDark,
                    ),
                  ),
                ],
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

typedef UpdatedProfileAppBar = ProfileHeader;
