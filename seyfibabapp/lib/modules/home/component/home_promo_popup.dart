import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../setting/model/announcement_modal_model.dart';
import '../widgets/home_theme.dart';
import 'home_promo_store.dart';
import 'story_actions.dart';

/// Anasayfa giriş kampanya popup'ı (oturumda bir kez).
class HomePromoPopup {
  HomePromoPopup._();

  static Future<void> maybeShow(
    BuildContext context,
    AnnouncementModalModel? modal,
  ) async {
    if (!context.mounted) return;
    final show = await HomePromoStore.shouldShow(modal);
    if (!show || modal == null || !context.mounted) return;

    HomePromoStore.markSessionShown();

    await showGeneralDialog<void>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Kampanya',
      barrierColor: Colors.black54,
      transitionDuration: const Duration(milliseconds: 220),
      pageBuilder: (ctx, anim, secondary) {
        return _HomePromoDialog(modal: modal);
      },
      transitionBuilder: (ctx, anim, secondary, child) {
        return FadeTransition(
          opacity: anim,
          child: ScaleTransition(
            scale: Tween<double>(begin: 0.94, end: 1).animate(anim),
            child: child,
          ),
        );
      },
    );
  }
}

class _HomePromoDialog extends StatelessWidget {
  const _HomePromoDialog({required this.modal});

  final AnnouncementModalModel modal;

  Future<void> _openLink(BuildContext context) async {
    final raw = modal.effectiveLink;
    if (raw.isEmpty) return;

    final lower = raw.toLowerCase().trim();
    String? slug;
    if (lower.contains('/urun/')) {
      slug = lower.split('/urun/').last.split('?').first.split('/').first;
    } else if (!lower.startsWith('http') && !lower.contains('/')) {
      slug = lower;
    }

    Navigator.of(context).pop();
    if (slug != null && slug.isNotEmpty) {
      Navigator.pushNamed(
        context,
        RouteNames.productDetailsScreen,
        arguments: slug,
      );
      return;
    }
    await openStoryLink(context, raw);
  }

  @override
  Widget build(BuildContext context) {
    final imageUrl = RemoteUrls.imageUrl(modal.image);
    final cta = (modal.ctaText ?? '').trim().isNotEmpty
        ? modal.ctaText!.trim()
        : (modal.effectiveLink.isNotEmpty ? 'İncele' : '');
    final hasText = modal.title.trim().isNotEmpty ||
        modal.description.trim().isNotEmpty ||
        cta.isNotEmpty;

    return Center(
      child: Material(
        color: Colors.transparent,
        child: Container(
          width: MediaQuery.of(context).size.width * 0.88,
          constraints: const BoxConstraints(maxWidth: 400),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Stack(
                children: [
                  GestureDetector(
                    onTap: modal.effectiveLink.isEmpty
                        ? null
                        : () => _openLink(context),
                    child: AspectRatio(
                      aspectRatio: 4 / 5,
                      child: Image.network(
                        imageUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          color: HomeTheme.bg,
                          alignment: Alignment.center,
                          child: const Icon(Icons.image_not_supported_outlined),
                        ),
                      ),
                    ),
                  ),
                  Positioned(
                    top: 10,
                    right: 10,
                    child: Material(
                      color: Colors.black54,
                      shape: const CircleBorder(),
                      child: InkWell(
                        customBorder: const CircleBorder(),
                        onTap: () => Navigator.of(context).pop(),
                        child: const SizedBox(
                          width: 36,
                          height: 36,
                          child: Icon(Icons.close, color: Colors.white, size: 18),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              if (hasText)
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 8),
                  child: Column(
                    children: [
                      if (modal.title.trim().isNotEmpty)
                        Text(
                          modal.title,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.w700,
                            color: HomeTheme.textDark,
                          ),
                        ),
                      if (modal.description.trim().isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Text(
                          modal.description,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13,
                            height: 1.35,
                            color: HomeTheme.textMuted,
                          ),
                        ),
                      ],
                      if (cta.isNotEmpty && modal.effectiveLink.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          height: 44,
                          child: ElevatedButton(
                            onPressed: () => _openLink(context),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: HomeTheme.brandYellow,
                              foregroundColor: HomeTheme.textDark,
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                            child: Text(cta),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              const Divider(height: 1),
              TextButton(
                onPressed: () async {
                  await HomePromoStore.dismissForDays(modal.expiredDate);
                  if (context.mounted) Navigator.of(context).pop();
                },
                child: Text(
                  'Bir daha gösterme',
                  style: TextStyle(
                    fontSize: 13,
                    color: HomeTheme.textMuted,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
