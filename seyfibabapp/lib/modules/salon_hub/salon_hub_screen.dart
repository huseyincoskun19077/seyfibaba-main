import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../core/remote_urls.dart';
import '../../core/router_name.dart';
import '../../widgets/custom_image.dart';
import '../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../home/model/setting_model.dart';
import '../home/widgets/home_theme.dart';
import '../main_page/main_controller.dart';
import 'widgets/user_salon_header.dart';

Color _hex(String? hex, Color fallback) {
  if (hex == null || hex.isEmpty) return fallback;
  var h = hex.replaceAll('#', '').trim();
  if (h.length == 6) h = 'FF$h';
  final v = int.tryParse(h, radix: 16);
  return v != null ? Color(v) : fallback;
}

/// Ana menü hub — kart görselleri admin panelden yüklenir.
class SalonHubScreen extends StatelessWidget {
  const SalonHubScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AppSettingCubit, AppSettingState>(
      builder: (context, state) {
        final SettingModel? setting =
            context.read<AppSettingCubit>().settingModel?.setting;
        final bgTop = _hex(setting?.mobileHubBgTop, const Color(0xFFFAFAF8));
        final bgBottom = _hex(setting?.mobileHubBgBottom, HomeTheme.bg);
        final featStart =
            _hex(setting?.mobileHubFeatureStart, const Color(0xFFFFF8E8));
        final featEnd = _hex(setting?.mobileHubFeatureEnd, HomeTheme.brandYellow);

        return Scaffold(
          backgroundColor: bgBottom,
          body: Column(
            children: [
              const UserSalonHeader(),
              Expanded(
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [bgTop, bgBottom, bgBottom],
                      stops: const [0, 0.25, 1],
                    ),
                  ),
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 110),
                    children: [
                      Text(
                        _greeting(),
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                          color: HomeTheme.textMuted,
                        ),
                      ),
                      const SizedBox(height: 6),
                      const Text(
                        'Bugün ne yapmak istersin?',
                        style: TextStyle(
                          fontSize: 26,
                          fontWeight: FontWeight.w700,
                          color: HomeTheme.textDark,
                          letterSpacing: -0.5,
                          height: 1.15,
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Kuaför ve güzellik salonları için toptan market, CRM ve ikinci el.',
                        style: TextStyle(
                          fontSize: 14,
                          height: 1.4,
                          color: HomeTheme.textMuted,
                        ),
                      ),
                      const SizedBox(height: 22),
                      _HubPhotoCard(
                        height: 204,
                        imagePath: setting?.mobileHubShopImage,
                        fallbackStart: featStart,
                        fallbackEnd: featEnd,
                        fallbackIcon: Icons.shopping_bag_outlined,
                        title: 'Toptan Alışveriş',
                        subtitle:
                            'Kuaför ve güzellik salonlarına toptan satış. Ürün, cihaz ve sarf malzemeyi salon fiyatına alın.',
                        badge: 'Salonlara özel toptan',
                        onTap: () => MainController().naveListener.sink.add(1),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: _HubPhotoCard(
                              height: 198,
                              imagePath: setting?.mobileHubCrmImage,
                              fallbackStart: Colors.white,
                              fallbackEnd: const Color(0xFFF3F1EA),
                              fallbackIcon: Icons.event_note_rounded,
                              title: 'Salon Paneli',
                              subtitle: 'Randevu, personel ve müşteri takibi',
                              onTap: () => Navigator.pushNamed(
                                context,
                                RouteNames.salonCrmGateScreen,
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _HubPhotoCard(
                              height: 198,
                              imagePath: setting?.mobileHubSecondhandImage,
                              fallbackStart: Colors.white,
                              fallbackEnd: const Color(0xFFF3F1EA),
                              fallbackIcon: Icons.storefront_outlined,
                              title: 'İkinci El',
                              subtitle: 'Kullanılmış ekipmanı al ve sat',
                              onTap: () => Navigator.pushNamed(
                                context,
                                RouteNames.secondHandListScreen,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Günaydın';
    if (hour < 18) return 'İyi günler';
    return 'İyi akşamlar';
  }
}

class _HubPhotoCard extends StatelessWidget {
  const _HubPhotoCard({
    required this.height,
    required this.imagePath,
    required this.fallbackStart,
    required this.fallbackEnd,
    required this.fallbackIcon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    this.badge,
  });

  final double height;
  final String? imagePath;
  final Color fallbackStart;
  final Color fallbackEnd;
  final IconData fallbackIcon;
  final String title;
  final String subtitle;
  final String? badge;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final hasImage = imagePath != null && imagePath!.trim().isNotEmpty;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          height: height,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            gradient: hasImage
                ? null
                : LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [fallbackStart, fallbackEnd],
                  ),
            border: hasImage
                ? null
                : Border.all(color: HomeTheme.border.withValues(alpha: 0.7)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: hasImage ? 0.12 : 0.06),
                blurRadius: 18,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: Stack(
              fit: StackFit.expand,
              children: [
                if (hasImage)
                  CustomImage(
                    path: RemoteUrls.imageUrl(imagePath!),
                    fit: BoxFit.cover,
                  )
                else
                  Align(
                    alignment: Alignment.topLeft,
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Icon(fallbackIcon, size: 26, color: HomeTheme.textDark),
                    ),
                  ),
                if (badge != null && badge!.isNotEmpty)
                  Align(
                    alignment: Alignment.topRight,
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          color: HomeTheme.brandYellow,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 5,
                          ),
                          child: Text(
                            badge!,
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: HomeTheme.textDark,
                              height: 1.1,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                Align(
                  alignment: Alignment.bottomCenter,
                  child: Container(
                    width: double.infinity,
                    color: Colors.black,
                    padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          style: TextStyle(
                            fontSize: height > 190 ? 19 : 15,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                            height: 1.15,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          subtitle,
                          maxLines: 3,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                            color: Colors.white,
                            height: 1.3,
                          ),
                        ),
                      ],
                    ),
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
