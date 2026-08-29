import 'package:badges/badges.dart' as badges;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../authentication/controller/login/login_bloc.dart';
import '../../cart/controllers/cart/cart_cubit.dart';
import '../../notification/controller/notification_cubit.dart';
import '../../profile/controllers/updated_info/updated_info_cubit.dart';
import '../../salon_crm/services/salon_crm_service.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/k_strings.dart';
import '../../../utils/k_images.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../widgets/home_theme.dart';
import 'search_field.dart';

class HomeAppBar extends StatelessWidget {
  const HomeAppBar({super.key});

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.dark.copyWith(
        statusBarColor: Colors.transparent,
      ),
      child: DecoratedBox(
        decoration: const BoxDecoration(
          color: HomeTheme.header,
          border: Border(
            bottom: BorderSide(color: HomeTheme.headerBorder, width: 1),
          ),
        ),
        child: SafeArea(
          bottom: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 10, 12, 10),
                child: Row(
                  children: [
                    const Expanded(child: _HomeIdentity()),
                    BlocBuilder<LoginBloc, LoginModelState>(
                      builder: (context, _) {
                        final isSeller = Utils.isSeller(context);
                        return _HeaderIconButton(
                          label: isSeller
                              ? Language.sellerPanelEnter
                              : Language.secondHand,
                          onTap: () {
                            if (isSeller) {
                              Navigator.pushNamed(
                                context,
                                RouteNames.sellerPanelScreen,
                              );
                              return;
                            }
                            Navigator.pushNamed(
                              context,
                              RouteNames.secondHandListScreen,
                            );
                          },
                        );
                      },
                    ),
                    const SizedBox(width: 8),
                    _HeaderActionIcon(
                      onTap: () {
                        if (Utils.isLoggedIn(context)) {
                          Navigator.pushNamed(
                            context,
                            RouteNames.notificationScreen,
                          ).then((_) {
                            context
                                .read<NotificationCubit>()
                                .refreshUnreadCount();
                          });
                        } else {
                          Navigator.pushNamed(
                            context,
                            RouteNames.authenticationScreen,
                          );
                        }
                      },
                      child: const _HomeNotificationBell(),
                    ),
                    const SizedBox(width: 8),
                    _HeaderActionIcon(
                      onTap: () {
                        if (Utils.isLoggedIn(context)) {
                          Navigator.pushNamed(context, RouteNames.cartScreen);
                        } else {
                          Navigator.pushNamed(
                            context,
                            RouteNames.authenticationScreen,
                          );
                        }
                      },
                      child: Utils.isLoggedIn(context)
                          ? BlocBuilder<CartCubit, CartState>(
                              builder: (context, _) {
                                return CartBadge(
                                  iconColor: HomeTheme.textDark,
                                  count: context
                                      .read<CartCubit>()
                                      .cartCount
                                      .toString(),
                                );
                              },
                            )
                          : const CartBadge(
                              iconColor: HomeTheme.textDark,
                              count: '0',
                            ),
                    ),
                  ],
                ),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(16, 0, 16, 12),
                child: SearchField(),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HeaderIconButton extends StatelessWidget {
  const _HeaderIconButton({
    required this.label,
    required this.onTap,
  });

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: HomeTheme.brandYellow,
      borderRadius: BorderRadius.circular(12),
      elevation: 0,
      shadowColor: Colors.black.withValues(alpha: 0.08),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.06),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Text(
            label,
            style: TextStyle(
              color: HomeTheme.textDark,
              fontSize: label.length > 12 ? 10 : 12,
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
      ),
    );
  }
}

class _HeaderActionIcon extends StatelessWidget {
  const _HeaderActionIcon({
    required this.onTap,
    required this.child,
  });

  final VoidCallback onTap;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: whiteColor,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: HomeTheme.headerBorder),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 4,
                offset: const Offset(0, 1),
              ),
            ],
          ),
          alignment: Alignment.center,
          child: child,
        ),
      ),
    );
  }
}

class _HomeIdentity extends StatefulWidget {
  const _HomeIdentity();

  @override
  State<_HomeIdentity> createState() => _HomeIdentityState();
}

class _HomeIdentityState extends State<_HomeIdentity> {
  final _service = SalonCrmService();
  String? _salonName;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadSalon());
  }

  Future<void> _loadSalon() async {
    if (!Utils.isLoggedIn(context)) return;
    final token = context.read<LoginBloc>().userInfo?.accessToken ?? '';
    if (token.isEmpty) return;
    try {
      final status = await _service.fetchStatus(token);
      if (!mounted) return;
      setState(() => _salonName = status.salon?.name);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    context.watch<LoginBloc>();
    context.watch<UserProfileInfoCubit>();

    final isLoggedIn = Utils.isLoggedIn(context);
    final loginBloc = context.read<LoginBloc>();
    final profileCubit = context.read<UserProfileInfoCubit>();
    final userName = isLoggedIn
        ? (profileCubit.updatedInfo?.updateUserInfo.name ??
            loginBloc.userInfo?.user.name)
        : null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        RichText(
          text: const TextSpan(
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.3,
              height: 1.1,
            ),
            children: [
              TextSpan(
                text: 'Seyfibaba',
                style: TextStyle(color: HomeTheme.textDark),
              ),
              TextSpan(
                text: '.com',
                style: TextStyle(color: HomeTheme.brandYellow),
              ),
            ],
          ),
        ),
        const SizedBox(height: 3),
        if (isLoggedIn && (userName?.trim().isNotEmpty ?? false)) ...[
          Text(
            userName!.trim(),
            style: const TextStyle(
              color: HomeTheme.textDark,
              fontSize: 13,
              fontWeight: FontWeight.w700,
            ),
          ),
          if (_salonName != null && _salonName!.trim().isNotEmpty)
            Text(
              _salonName!.trim(),
              style: const TextStyle(
                color: HomeTheme.textMuted,
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
            ),
        ] else
          Text(
            KStrings.splashTitle,
            style: const TextStyle(
              color: HomeTheme.textMuted,
              fontSize: 11,
              fontWeight: FontWeight.w500,
            ),
          ),
      ],
    );
  }
}

class CartBadge extends StatelessWidget {
  const CartBadge({super.key, required this.count, required this.iconColor});

  final String? count;
  final Color iconColor;

  @override
  Widget build(BuildContext context) {
    return badges.Badge(
      badgeStyle: badges.BadgeStyle(
        badgeColor: HomeTheme.brandYellow,
        padding: const EdgeInsets.all(4),
      ),
      badgeContent: CustomText(
        isTranslate: false,
        text: count?.isNotEmpty ?? false ? count ?? '0' : '0',
        fontSize: 9,
        color: blackColor,
      ),
      child: CustomImage(path: Kimages.shoppingIcon, color: iconColor),
    );
  }
}

class _HomeNotificationBell extends StatefulWidget {
  const _HomeNotificationBell();

  @override
  State<_HomeNotificationBell> createState() => _HomeNotificationBellState();
}

class _HomeNotificationBellState extends State<_HomeNotificationBell> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.read<NotificationCubit>().refreshUnreadCount();
    });
  }

  @override
  Widget build(BuildContext context) {
    context.watch<LoginBloc>();
    final unread = context.watch<NotificationCubit>().unreadCount;
    final showBadge = Utils.isLoggedIn(context) && unread > 0;
    return badges.Badge(
      showBadge: showBadge,
      badgeStyle: const badges.BadgeStyle(
        badgeColor: Color(0xFFE53935),
        padding: EdgeInsets.all(4),
      ),
      badgeContent: CustomText(
        isTranslate: false,
        text: unread > 99 ? '99+' : '$unread',
        fontSize: 9,
        color: whiteColor,
      ),
      child: const Icon(
        Icons.notifications_outlined,
        color: HomeTheme.textDark,
        size: 22,
      ),
    );
  }
}
