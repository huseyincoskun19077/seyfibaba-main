import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '/utils/language_string.dart';
import '/utils/utils.dart';
import '/widgets/capitalized_word.dart';
import '../../core/push/push_notification_service.dart';
import '../../core/router_name.dart';
import '../../utils/k_images.dart';
import '../../utils/constants.dart';
import '../../widgets/confirm_dialog.dart';
import '../authentication/controller/login/login_bloc.dart';
import '../home/widgets/home_theme.dart';
import '../notification/controller/notification_cubit.dart';
import 'component/profile_app_bar.dart';
import 'controllers/delete_user/delete_user_cubit.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.dark.copyWith(
        statusBarColor: Colors.transparent,
      ),
      child: Scaffold(
        backgroundColor: HomeTheme.bg,
        body: CustomScrollView(
          slivers: [
            const SliverToBoxAdapter(child: ProfileHeader()),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  _ProfileSection(
                    title: 'Hesabım',
                    children: [
                      BlocBuilder<LoginBloc, LoginModelState>(
                        builder: (context, _) {
                          if (!Utils.isSeller(context)) {
                            return const SizedBox.shrink();
                          }
                          return ProfileMenuTile(
                            title: Language.sellerPanelEnter,
                            icon: Icons.storefront,
                            iconColor: HomeTheme.brandYellow,
                            onTap: () => Navigator.pushNamed(
                              context,
                              RouteNames.sellerPanelScreen,
                            ),
                          );
                        },
                      ),
                      ProfileMenuTile(
                        title: Language.secondHand,
                        icon: Icons.storefront_outlined,
                        iconColor: HomeTheme.brandYellow,
                        onTap: () => Navigator.pushNamed(
                          context,
                          RouteNames.secondHandHubScreen,
                        ),
                      ),
                      BlocBuilder<NotificationCubit, NotificationState>(
                        builder: (context, _) {
                          final unread =
                              context.read<NotificationCubit>().unreadCount;
                          return ProfileMenuTile(
                            title: Language.notifications,
                            icon: Icons.notifications_outlined,
                            iconColor: const Color(0xFF5B8DEF),
                            badgeCount: unread,
                            onTap: () => Navigator.pushNamed(
                              context,
                              RouteNames.notificationScreen,
                            ).then((_) {
                              context
                                  .read<NotificationCubit>()
                                  .refreshUnreadCount();
                            }),
                          );
                        },
                      ),
                      ProfileMenuTile(
                        title: Language.yourAddress.capitalizeByWord(),
                        icon: Icons.location_on_outlined,
                        iconColor: const Color(0xFF34A853),
                        onTap: () => Navigator.pushNamed(
                          context,
                          RouteNames.addressScreen,
                        ),
                      ),
                      ProfileMenuTile(
                        title: Language.allCategories.capitalizeByWord(),
                        icon: Icons.grid_view_rounded,
                        iconColor: const Color(0xFF5B8DEF),
                        onTap: () => Navigator.pushNamed(
                          context,
                          RouteNames.allCategoryListScreen,
                        ),
                        showDivider: false,
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _ProfileSection(
                    title: 'Yasal Belgeler',
                    children: [
                      ProfileMenuTile(
                        title: 'Yasal Belgeler',
                        icon: Icons.gavel_outlined,
                        requiresAuth: false,
                        onTap: () => Navigator.pushNamed(
                          context,
                          RouteNames.legalDocumentsHubScreen,
                        ),
                        showDivider: false,
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _ProfileSection(
                    title: 'Destek',
                    children: [
                      ProfileMenuTile(
                        title: 'Destek Talebi',
                        icon: Icons.support_agent_outlined,
                        iconColor: HomeTheme.brandYellow,
                        requiresAuth: false,
                        onTap: () => Navigator.pushNamed(
                          context,
                          RouteNames.contactUsScreen,
                        ),
                      ),
                      ProfileMenuTile(
                        title: Language.faq,
                        icon: Icons.help_outline_rounded,
                        requiresAuth: false,
                        onTap: () =>
                            Navigator.pushNamed(context, RouteNames.faqScreen),
                      ),
                      ProfileMenuTile(
                        title: Language.aboutUs.capitalizeByWord(),
                        icon: Icons.info_outline_rounded,
                        requiresAuth: false,
                        onTap: () => Navigator.pushNamed(
                          context,
                          RouteNames.aboutUsScreen,
                        ),
                      ),
                      ProfileMenuTile(
                        title: Language.contactUs.capitalizeByWord(),
                        icon: Icons.mail_outline_rounded,
                        requiresAuth: false,
                        onTap: () => Navigator.pushNamed(
                          context,
                          RouteNames.contactUsScreen,
                        ),
                      ),
                      ProfileMenuTile(
                        title: Language.appInfo.capitalizeByWord(),
                        icon: Icons.smartphone_outlined,
                        requiresAuth: false,
                        onTap: () => Utils.appInfoDialog(context),
                        showDivider: false,
                      ),
                    ],
                  ),
                  if (Utils.isLoggedIn(context)) ...[
                  const SizedBox(height: 16),
                  _ProfileSection(
                    title: 'Hesap işlemleri',
                    children: [
                      BlocListener<DeleteUserCubit, DeleteUserState>(
                        listener: (context, state) {
                          if (state is DeleteUserLoading) {
                            Utils.loadingDialog(context);
                          } else {
                            Utils.closeDialog(context);
                            if (state is DeleteUserError) {
                              Utils.errorSnackBar(context, state.message);
                            } else if (state is DeleteUserLoaded) {
                              Navigator.pushNamedAndRemoveUntil(
                                context,
                                RouteNames.authenticationScreen,
                                (route) => false,
                              );
                            }
                          }
                        },
                        child: ProfileMenuTile(
                          title: Language.deleteAccount,
                          icon: Icons.delete_outline_rounded,
                          isDestructive: true,
                          onTap: () {
                            showDialog(
                              context: context,
                              barrierDismissible: false,
                              builder: (context) => ConfirmDialog(
                                icon: Kimages.deleteIcon2,
                                message:
                                    'Hesabınızı silmek\nistediğinize emin misiniz?',
                                confirmText: 'Evet, Sil',
                                cancelText: 'İptal',
                                onTap: () {
                                  Navigator.of(context).pop();
                                  context
                                      .read<DeleteUserCubit>()
                                      .deleteUserAccount();
                                },
                              ),
                            );
                          },
                        ),
                      ),
                      BlocListener<LoginBloc, LoginModelState>(
                        listener: (context, state) {
                          final logout = state.state;
                          if (logout is LoginStateLogOutLoading) {
                            Utils.loadingDialog(context);
                          } else {
                            Utils.closeDialog(context);
                            if (logout is LoginStateSignOutError) {
                              Utils.errorSnackBar(context, logout.errorMsg);
                            } else if (logout is LoginStateLogOut) {
                              Navigator.pushNamedAndRemoveUntil(
                                context,
                                RouteNames.authenticationScreen,
                                (route) => false,
                              );
                              Utils.showSnackBar(context, logout.msg);
                            }
                          }
                        },
                        child: ProfileMenuTile(
                          title: Language.logout.capitalizeByWord(),
                          icon: Icons.logout_rounded,
                          isDestructive: true,
                          showDivider: false,
                          onTap: () {
                            showDialog(
                              context: context,
                              barrierDismissible: false,
                              builder: (dialogContext) => ConfirmDialog(
                                icon: Kimages.logout2,
                                message: 'Çıkış yapmak\nistediğinize emin misiniz?',
                                confirmText: 'Evet, Çık',
                                cancelText: 'İptal',
                                onTap: () {
                                  Navigator.of(dialogContext).pop();
                                  final loginBloc = context.read<LoginBloc>();
                                  // FCM temizliği çıkışı engellemesin
                                  PushNotificationService.instance
                                      .clearDeviceToken(context)
                                      .whenComplete(() {
                                    loginBloc.add(const LoginEventLogout());
                                  });
                                },
                              ),
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                  ],
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ProfileSection extends StatelessWidget {
  const _ProfileSection({
    required this.title,
    required this.children,
  });

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 8),
          child: Text(
            title.toUpperCase(),
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: HomeTheme.textMuted,
              letterSpacing: 0.6,
            ),
          ),
        ),
        Container(
          decoration: HomeTheme.cardDecoration(),
          clipBehavior: Clip.antiAlias,
          child: Column(children: children),
        ),
      ],
    );
  }
}

class ProfileMenuTile extends StatelessWidget {
  const ProfileMenuTile({
    super.key,
    required this.title,
    required this.icon,
    required this.onTap,
    this.iconColor,
    this.requiresAuth = true,
    this.isDestructive = false,
    this.showDivider = true,
    this.badgeCount = 0,
  });

  final String title;
  final IconData icon;
  final VoidCallback onTap;
  final Color? iconColor;
  final bool requiresAuth;
  final bool isDestructive;
  final bool showDivider;
  final int badgeCount;

  @override
  Widget build(BuildContext context) {
    final effectiveIconColor = isDestructive
        ? redColor
        : (iconColor ?? HomeTheme.textDark);

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: () {
              if (requiresAuth && !Utils.isLoggedIn(context)) {
                Utils.showSnackBarWithLogin(
                  context,
                  'Devam etmek için giriş yapın',
                  () => Navigator.pushNamedAndRemoveUntil(
                    context,
                    RouteNames.authenticationScreen,
                    (route) => false,
                  ),
                );
                return;
              }
              onTap();
            },
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
              child: Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: isDestructive
                          ? redColor.withValues(alpha: 0.08)
                          : (iconColor ?? HomeTheme.brandYellow)
                              .withValues(alpha: 0.14),
                      borderRadius: BorderRadius.circular(11),
                    ),
                    child: Icon(
                      icon,
                      size: 20,
                      color: effectiveIconColor,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      title,
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w500,
                        color: isDestructive ? redColor : HomeTheme.textDark,
                      ),
                    ),
                  ),
                  if (badgeCount > 0) ...[
                    Container(
                      constraints: const BoxConstraints(minWidth: 22),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 7,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE53935),
                        borderRadius: BorderRadius.circular(11),
                      ),
                      child: Text(
                        badgeCount > 99 ? '99+' : '$badgeCount',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    const SizedBox(width: 6),
                  ],
                  Icon(
                    Icons.chevron_right_rounded,
                    size: 22,
                    color: HomeTheme.textMuted.withValues(alpha: 0.7),
                  ),
                ],
              ),
            ),
          ),
        ),
        if (showDivider)
          Divider(
            height: 1,
            indent: 64,
            endIndent: 14,
            color: HomeTheme.headerBorder.withValues(alpha: 0.9),
          ),
      ],
    );
  }
}
