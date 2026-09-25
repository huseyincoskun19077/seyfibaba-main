import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../model/story_model.dart';
import '../widgets/home_theme.dart';
import 'story_actions.dart';
import 'story_progress_store.dart';
import 'story_viewer.dart';

/// Uygulama marka mavisi (izlenmiş story halkası)
const Color _storyWatchedBlue = Color(0xFF04334A);

class HomeStoriesStrip extends StatefulWidget {
  const HomeStoriesStrip({super.key});

  @override
  State<HomeStoriesStrip> createState() => _HomeStoriesStripState();
}

class _HomeStoriesStripState extends State<HomeStoriesStrip> {
  /// storyId → izlenmemiş mi (sarı halka)
  Map<int, bool> _unwatched = {};
  bool _loaded = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _refreshRings());
  }

  Future<void> _refreshRings() async {
    final stories =
        (context.read<AppSettingCubit>().settingModel?.stories ?? const [])
            .where((s) => s.showOnMobile)
            .toList();
    final map = <int, bool>{};

    await Future.wait(stories.map((s) async {
      if (!s.isProductFeed) {
        map[s.id] = await StoryProgressStore.isUnwatched(s.id);
        return;
      }
      try {
        final feed = (s.feed ?? 'popular').trim();
        final items =
            await fetchStoryProducts(feed.isEmpty ? 'popular' : feed);
        final fp = StoryProgressStore.fingerprint(
          items.map((e) => e.id).toList(),
        );
        // Yeni ürün seti → sarı; aynı set izlendiyse mavi
        map[s.id] = await StoryProgressStore.isUnwatched(
          s.id,
          productFingerprint: fp,
        );
      } catch (_) {
        map[s.id] = await StoryProgressStore.isUnwatched(s.id);
      }
    }));

    if (!mounted) return;
    setState(() {
      _unwatched = map;
      _loaded = true;
    });
  }

  Future<void> _onStoryTap(StoryModel story) async {
    final stories =
        (context.read<AppSettingCubit>().settingModel?.stories ?? const [])
            .where((s) => s.showOnMobile)
            .toList();
    final startIndex = stories.indexWhere((s) => s.id == story.id);

    if (story.isProductFeed || story.type == 'link') {
      if (story.isProductFeed) {
        await Navigator.of(context).push(
          PageRouteBuilder(
            opaque: false,
            barrierColor: Colors.black.withValues(alpha: 0.72),
            pageBuilder: (_, __, ___) => StoryViewerScreen(
              stories: stories,
              initialIndex: startIndex < 0 ? 0 : startIndex,
            ),
            transitionsBuilder: (_, anim, __, child) {
              final curved =
                  CurvedAnimation(parent: anim, curve: Curves.easeOutCubic);
              return FadeTransition(
                opacity: curved,
                child: ScaleTransition(
                  scale: Tween(begin: 0.96, end: 1.0).animate(curved),
                  child: child,
                ),
              );
            },
          ),
        );
        if (!mounted) return;
        await _refreshRings();
        return;
      }
      await openStoryLink(context, story.effectiveMobileHref);
      await StoryProgressStore.markLinkWatched(story.id);
      if (!mounted) return;
      await _refreshRings();
      return;
    }
  }

  @override
  Widget build(BuildContext context) {
    final stories =
        (context.watch<AppSettingCubit>().settingModel?.stories ?? const [])
            .where((s) => s.showOnMobile)
            .toList();
    if (stories.isEmpty) return const SizedBox.shrink();

    if (!_loaded && _unwatched.isEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _refreshRings());
    }

    return Padding(
      padding: const EdgeInsets.fromLTRB(0, 4, 0, 8),
      child: SizedBox(
        height: 108,
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          scrollDirection: Axis.horizontal,
          itemCount: stories.length,
          separatorBuilder: (_, __) => const SizedBox(width: 12),
          itemBuilder: (context, index) {
            final story = stories[index];
            final unwatched = _unwatched[story.id] ?? true;
            return _StoryAvatar(
              story: story,
              unwatched: unwatched,
              onTap: () => _onStoryTap(story),
            );
          },
        ),
      ),
    );
  }
}

class _StoryAvatar extends StatelessWidget {
  const _StoryAvatar({
    required this.story,
    required this.unwatched,
    required this.onTap,
  });

  final StoryModel story;
  final bool unwatched;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final src =
        story.image.trim().isEmpty ? '' : RemoteUrls.imageUrl(story.image);
    final initial =
        story.title.trim().isEmpty ? '?' : story.title.trim()[0].toUpperCase();

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(40),
      child: SizedBox(
        width: 76,
        child: Column(
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              width: 68,
              height: 68,
              padding: const EdgeInsets.all(2.5),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: unwatched ? const Color(0xFFFFBB38) : _storyWatchedBlue,
                gradient: unwatched
                    ? const LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [
                          Color(0xFFFFD56A),
                          Color(0xFFFFBB38),
                          Color(0xFFE8A010),
                        ],
                      )
                    : null,
                boxShadow: unwatched
                    ? [
                        BoxShadow(
                          color: const Color(0xFFFFBB38).withValues(alpha: 0.35),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ]
                    : null,
              ),
              child: Container(
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white,
                ),
                padding: const EdgeInsets.all(2),
                child: Container(
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white,
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: src.isEmpty
                      ? Center(
                          child: Text(
                            initial,
                            style: const TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.w800,
                              color: _storyWatchedBlue,
                            ),
                          ),
                        )
                      : Image.network(
                          src,
                          fit: BoxFit.cover,
                          width: double.infinity,
                          height: double.infinity,
                          errorBuilder: (_, __, ___) => Center(
                            child: Text(
                              initial,
                              style: const TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.w800,
                                color: _storyWatchedBlue,
                              ),
                            ),
                          ),
                        ),
                ),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              story.title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 11,
                fontWeight: unwatched ? FontWeight.w700 : FontWeight.w600,
                height: 1.15,
                color: HomeTheme.textDark,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
