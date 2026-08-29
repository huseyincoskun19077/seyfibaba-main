import 'package:flutter_svg/flutter_svg.dart';

import '../../../core/router_name.dart';
import '../../../state_packages_names.dart';
import '../../../utils/k_images.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../animated_splash_screen/controller/translate_cubit/translate_cubit.dart';
import '../../animated_splash_screen/controller/translate_cubit/translate_state_model.dart';
import '../../home/component/home_app_bar.dart';
import '../../home/widgets/home_theme.dart';
import '../main_controller.dart';

class MyBottomNavigationBar extends StatelessWidget {
  const MyBottomNavigationBar({super.key});

  static const _items = [
    _NavItemType.hub,
    _NavItemType.shop,
    _NavItemType.cart,
    _NavItemType.order,
    _NavItemType.profile,
  ];

  static int? _pageIndex(_NavItemType type) {
    switch (type) {
      case _NavItemType.hub:
        return 0;
      case _NavItemType.shop:
        return 1;
      case _NavItemType.cart:
        return null;
      case _NavItemType.order:
        return 2;
      case _NavItemType.profile:
        return 3;
    }
  }

  static void _openCart(BuildContext context) {
    if (Utils.isLoggedIn(context)) {
      Navigator.pushNamed(context, RouteNames.cartScreen);
    } else {
      Navigator.pushNamed(context, RouteNames.authenticationScreen);
    }
  }

  @override
  Widget build(BuildContext context) {
    final controller = MainController();
    return BlocBuilder<TranslateCubit, TranslateStateModel>(
      builder: (context, state) {
        final labels = <_NavItemType, String>{
          _NavItemType.hub: 'Menü',
          _NavItemType.shop: state.bottomText['home'] ?? Language.home,
          _NavItemType.cart: Language.cart,
          _NavItemType.order: state.bottomText['order'] ?? Language.order,
          _NavItemType.profile: state.bottomText['profile'] ?? Language.profile,
        };

        return StreamBuilder<int>(
          initialData: 0,
          stream: controller.naveListener.stream,
          builder: (_, AsyncSnapshot<int> index) {
            final selectedIndex = index.data ?? 0;

            return SafeArea(
              top: false,
              minimum: const EdgeInsets.only(bottom: 8),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 6),
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color: HomeTheme.header,
                    borderRadius: BorderRadius.circular(28),
                    border: Border.all(
                      color: HomeTheme.border.withValues(alpha: 0.85),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 24,
                        offset: const Offset(0, 8),
                      ),
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.03),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(28),
                    child: SizedBox(
                      height: 64,
                      child: Row(
                        children: List.generate(_items.length, (i) {
                          final type = _items[i];
                          final pageIndex = _pageIndex(type);
                          return _NavTile(
                            type: type,
                            label: labels[type] ?? '',
                            selected: pageIndex != null &&
                                selectedIndex == pageIndex,
                            onTap: () {
                              if (type == _NavItemType.cart) {
                                _openCart(context);
                                return;
                              }
                              controller.naveListener.sink.add(pageIndex!);
                            },
                          );
                        }),
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }
}

enum _NavItemType { hub, shop, cart, order, profile }

class _NavTile extends StatelessWidget {
  const _NavTile({
    required this.type,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final _NavItemType type;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(22),
          splashColor: HomeTheme.brandYellow.withValues(alpha: 0.18),
          highlightColor: HomeTheme.brandYellow.withValues(alpha: 0.08),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 6),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  curve: Curves.easeOutCubic,
                  width: selected ? 52 : 40,
                  height: 34,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: selected
                        ? HomeTheme.brandYellow.withValues(alpha: 0.28)
                        : Colors.transparent,
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: _NavIcon(type: type, selected: selected),
                ),
                const SizedBox(height: 3),
                AnimatedDefaultTextStyle(
                  duration: const Duration(milliseconds: 200),
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                    color: selected ? HomeTheme.textDark : HomeTheme.textMuted,
                    height: 1.1,
                  ),
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _NavIcon extends StatelessWidget {
  const _NavIcon({
    required this.type,
    required this.selected,
  });

  final _NavItemType type;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    final iconColor = selected ? HomeTheme.textDark : HomeTheme.textMuted;
    switch (type) {
      case _NavItemType.hub:
        return Icon(
          selected ? Icons.apps_rounded : Icons.apps_outlined,
          size: 22,
          color: iconColor,
        );
      case _NavItemType.shop:
        return SvgPicture.asset(
          selected ? Kimages.homeActive : Kimages.homeIcon,
          width: 22,
          height: 22,
        );
      case _NavItemType.cart:
        if (!Utils.isLoggedIn(context)) {
          return CartBadge(iconColor: iconColor, count: '0');
        }
        return BlocBuilder<CartCubit, CartState>(
          builder: (context, _) {
            return CartBadge(
              iconColor: iconColor,
              count: context.read<CartCubit>().cartCount.toString(),
            );
          },
        );
      case _NavItemType.order:
        return SvgPicture.asset(
          selected ? Kimages.orderActive : Kimages.orderIcon,
          width: 22,
          height: 22,
        );
      case _NavItemType.profile:
        return SvgPicture.asset(
          selected ? Kimages.profileActive : Kimages.profileIcon,
          width: 22,
          height: 22,
        );
    }
  }
}
