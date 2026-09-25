import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../model/story_model.dart';
import 'story_actions.dart';
import 'story_progress_store.dart';
import 'story_view_tracker.dart';

class StoryViewerScreen extends StatefulWidget {
  const StoryViewerScreen({
    super.key,
    required this.stories,
    this.initialIndex = 0,
  });

  /// Mobil story sırası (product_feed + link).
  final List<StoryModel> stories;
  final int initialIndex;

  @override
  State<StoryViewerScreen> createState() => _StoryViewerScreenState();
}

class _StoryViewerScreenState extends State<StoryViewerScreen>
    with TickerProviderStateMixin {
  late int _storyIndex;
  List<StoryProductItem> _products = [];
  bool _loading = true;
  String? _error;
  int _index = 0;
  String _fp = '';
  late final AnimationController _progress;
  bool _holding = false;
  bool _progressReady = false;
  bool _busy = false;
  bool _closing = false;
  bool _dragMoved = false;

  static const _pageDuration = Duration(milliseconds: 2500);

  StoryModel get _story => widget.stories[_storyIndex];

  @override
  void initState() {
    super.initState();
    _storyIndex = widget.initialIndex.clamp(0, widget.stories.length - 1);
    _progress = AnimationController(vsync: this, duration: _pageDuration)
      ..addStatusListener((status) {
        if (status == AnimationStatus.completed && !_holding && !_busy) {
          _next();
        }
      });
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    _loadCurrentStory();
  }

  Future<void> _loadCurrentStory({
    bool resume = true,
    bool persistStart = true,
  }) async {
    setState(() {
      _loading = true;
      _error = null;
      _progressReady = false;
      _products = [];
      _index = 0;
    });
    _progress.stop();
    _progress.value = 0;

    final story = _story;

    if (!story.isProductFeed) {
      await StoryProgressStore.markLinkWatched(story.id);
      final next = _nextProductFeedIndex(_storyIndex + 1);
      if (next == null) {
        await _closeViewer();
        return;
      }
      if (!mounted) return;
      setState(() => _storyIndex = next);
      await _loadCurrentStory(resume: true);
      return;
    }

    try {
      final feed = (story.feed ?? 'popular').trim();
      final items = await fetchStoryProducts(feed.isEmpty ? 'popular' : feed);
      if (!mounted) return;

      final fp = StoryProgressStore.fingerprint(
        items.map((e) => e.id).toList(),
      );
      final watch = await StoryProgressStore.getState(
        story.id,
        productFingerprint: fp,
      );

      var start = 0;
      // Tamamlanmamış story: ürün id / index ile kaldığı yerden devam
      if (resume && !watch.completed && items.isNotEmpty) {
        if (watch.lastProductId > 0) {
          final byId =
              items.indexWhere((p) => p.id == watch.lastProductId);
          if (byId >= 0) {
            start = byId;
          } else if (watch.index > 0) {
            start = watch.index.clamp(0, items.length - 1);
          }
        } else if (watch.index > 0) {
          start = watch.index.clamp(0, items.length - 1);
        }
      }

      setState(() {
        _products = items;
        _fp = fp;
        _index = start;
        _loading = false;
        _error = items.isEmpty ? 'Ürün bulunamadı' : null;
        _progressReady = items.isNotEmpty;
      });

      if (items.isEmpty) {
        await StoryProgressStore.markCompleted(
          storyId: story.id,
          index: 0,
          lastProductId: 0,
          productFingerprint: fp.isEmpty ? 'empty' : fp,
        );
        await _goNextStory();
        return;
      }

      if (persistStart && !watch.completed) {
        await _saveCursor(start);
      }
      // Önce mevcut ürün görseli, sonra 2 ileri — geçişte boşluk olmasın
      await _precacheAt(start);
      if (!mounted) return;
      _startProgress();
      unawaited(_precacheAhead(start));
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = 'Ürünler yüklenemedi';
      });
    }
  }

  String? _accessToken() {
    try {
      return context.read<LoginBloc>().userInfo?.accessToken;
    } catch (_) {
      return null;
    }
  }

  Future<void> _trackView({
    required int productIndex,
    required bool completed,
  }) async {
    await StoryViewTracker.track(
      storyId: _story.id,
      platform: 'mobile',
      productIndex: productIndex,
      productsTotal: _products.length,
      completed: completed,
      accessToken: _accessToken(),
    );
  }

  Future<void> _saveCursor(int index, {bool completed = false}) async {
    if (_products.isEmpty || index < 0 || index >= _products.length) return;
    await StoryProgressStore.saveProgress(
      storyId: _story.id,
      index: index,
      lastProductId: _products[index].id,
      completed: completed,
      productFingerprint: _fp,
    );
  }

  Future<void> _precacheAt(int index) async {
    if (!mounted || _products.isEmpty) return;
    if (index < 0 || index >= _products.length) return;
    final raw = _products[index].thumb.trim();
    if (raw.isEmpty) return;
    final url = RemoteUrls.imageUrl(raw);
    if (url.isEmpty) return;
    try {
      await precacheImage(NetworkImage(url), context);
    } catch (_) {}
  }

  /// Mevcut + sonraki 2 ürün görselini önbelleğe al.
  Future<void> _precacheAhead(int index) async {
    if (!mounted || _products.isEmpty) return;
    for (final i in [index + 1, index + 2]) {
      await _precacheAt(i);
    }
  }

  Future<void> _precacheAround(int index) async {
    await _precacheAt(index);
    unawaited(_precacheAhead(index));
  }

  int? _nextProductFeedIndex(int from) {
    for (var i = from; i < widget.stories.length; i++) {
      if (widget.stories[i].isProductFeed ||
          widget.stories[i].type == 'link') {
        return i;
      }
    }
    return null;
  }

  int? _prevProductFeedIndex(int from) {
    for (var i = from; i >= 0; i--) {
      if (widget.stories[i].isProductFeed) return i;
    }
    return null;
  }

  Future<void> _goNextStory() async {
    // Oturum sonu kaydı: bu story'den çıkarken 1 görüntülenme
    if (_products.isNotEmpty) {
      final isLast = _index >= _products.length - 1;
      if (isLast) {
        await StoryProgressStore.markCompleted(
          storyId: _story.id,
          index: _products.length - 1,
          lastProductId: _products.last.id,
          productFingerprint: _fp,
        );
      }
      await _trackView(productIndex: _index, completed: isLast);
    }
    final next = _nextProductFeedIndex(_storyIndex + 1);
    if (next == null) {
      await _closeViewer(markDoneIfLast: true, skipTrack: true);
      return;
    }
    if (!mounted || _closing) return;
    setState(() => _storyIndex = next);
    await _loadCurrentStory(resume: true);
  }

  Future<void> _goPrevStory() async {
    final prev = _prevProductFeedIndex(_storyIndex - 1);
    if (prev == null) {
      _startProgress();
      return;
    }
    if (!mounted || _closing) return;
    setState(() => _storyIndex = prev);
    await _loadCurrentStory(resume: false, persistStart: false);
    if (_products.isNotEmpty && mounted && !_closing) {
      final last = _products.length - 1;
      setState(() => _index = last);
      await _saveCursor(last);
      await _precacheAt(last);
      if (!mounted || _closing) return;
      _startProgress();
      unawaited(_precacheAhead(last));
    }
  }

  void _startProgress() {
    if (!_progressReady || !mounted || _closing) return;
    _progress.duration = _pageDuration;
    if (_holding) {
      _progress.value = 0;
      return;
    }
    _progress.forward(from: 0);
    setState(() {});
  }

  void _pause() {
    _holding = true;
    _progress.stop();
  }

  void _resume() {
    if (_closing) return;
    _holding = false;
    if (_progressReady) {
      _progress.forward();
    }
  }

  Future<void> _next() async {
    if (!mounted || _busy || _closing) return;
    _busy = true;
    try {
      if (_products.isEmpty) {
        await _goNextStory();
        return;
      }
      if (_index >= _products.length - 1) {
        await StoryProgressStore.markCompleted(
          storyId: _story.id,
          index: _products.length - 1,
          lastProductId: _products.last.id,
          productFingerprint: _fp,
        );
        await _goNextStory();
        return;
      }
      final next = _index + 1;
      setState(() => _index = next);
      await _saveCursor(next);
      unawaited(_precacheAround(next));
      _startProgress();
    } finally {
      _busy = false;
    }
  }

  Future<void> _prev() async {
    if (!mounted || _busy || _closing) return;
    _busy = true;
    try {
      if (_index <= 0) {
        await _goPrevStory();
        return;
      }
      final prev = _index - 1;
      setState(() => _index = prev);
      await _saveCursor(prev);
      unawaited(_precacheAround(prev));
      _startProgress();
    } finally {
      _busy = false;
    }
  }

  Future<void> _closeViewer({
    bool markDoneIfLast = false,
    bool skipTrack = false,
  }) async {
    if (_closing) return;
    _closing = true;
    _progress.stop();
    try {
      if (_products.isNotEmpty &&
          _index >= 0 &&
          _index < _products.length) {
        final isLast = _index >= _products.length - 1;
        if (markDoneIfLast || isLast) {
          await StoryProgressStore.markCompleted(
            storyId: _story.id,
            index: _index,
            lastProductId: _products[_index].id,
            productFingerprint: _fp,
          );
        } else {
          await _saveCursor(_index);
        }
        if (!skipTrack) {
          await _trackView(
            productIndex: _index,
            completed: markDoneIfLast || isLast,
          );
        }
      }
    } catch (_) {}
    if (!mounted) return;
    Navigator.of(context).pop(true);
  }

  void _openProduct(StoryProductItem p) async {
    if (p.slug.isEmpty || _closing) return;
    final slug = p.slug;
    _closing = true;
    _progress.stop();
    try {
      await _saveCursor(_index);
    } catch (_) {}
    if (!mounted) return;
    final nav = Navigator.of(context);
    nav.pop(true);
    nav.pushNamed(
      RouteNames.productDetailsScreen,
      arguments: slug,
    );
  }

  void _onPanEnd(DragEndDetails d) {
    if (_closing) return;
    final v = d.velocity.pixelsPerSecond;
    if (v.dy.abs() >= v.dx.abs()) {
      // Dikey: aşağı çık, yukarı ürün
      if (v.dy > 400) {
        _closeViewer();
        return;
      }
      if (v.dy < -400 && _products.isNotEmpty) {
        _openProduct(_products[_index]);
        return;
      }
    } else if (v.dx.abs() > 400) {
      // Yatay story geçişi: sağa → sonraki, sola → önceki
      if (v.dx > 0) {
        unawaited(_goNextStory());
      } else {
        unawaited(_goPrevStory());
      }
      return;
    }
    _resume();
  }

  @override
  void dispose() {
    _progress.dispose();
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final pad = MediaQuery.paddingOf(context);

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        await _closeViewer();
      },
      child: Material(
        color: Colors.transparent,
        child: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Color(0xFF0A1F2C),
              Color(0xFF04334A),
              Color(0xFF021820),
            ],
          ),
        ),
        child: Stack(
          children: [
            if (_loading)
              const Center(
                child: CircularProgressIndicator(color: yellowColor),
              )
            else if (_error != null)
              Center(
                child: Padding(
                  padding: const EdgeInsets.all(28),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.auto_stories_outlined,
                          color: Colors.white54, size: 48),
                      const SizedBox(height: 14),
                      Text(
                        _error!,
                        style: const TextStyle(
                            color: Colors.white70, fontSize: 16),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 18),
                      FilledButton(
                        onPressed: () => _goNextStory(),
                        style: FilledButton.styleFrom(
                          backgroundColor: yellowColor,
                          foregroundColor: const Color(0xFF04334A),
                        ),
                        child: const Text('Sonraki story',
                            style: TextStyle(fontWeight: FontWeight.w800)),
                      ),
                      TextButton(
                        onPressed: () => _closeViewer(),
                        child: const Text('Kapat',
                            style: TextStyle(color: Colors.white70)),
                      ),
                    ],
                  ),
                ),
              )
            else ...[
              // Üst bar jest alanının dışında — X tıklanabilir
              Positioned(
                left: 0,
                right: 0,
                top: 0,
                child: Material(
                  color: Colors.transparent,
                  child: Padding(
                    padding: EdgeInsets.fromLTRB(12, pad.top + 8, 8, 8),
                    child: Column(
                      children: [
                        Row(
                          children: List.generate(_products.length, (i) {
                            return Expanded(
                              child: Padding(
                                padding:
                                    const EdgeInsets.symmetric(horizontal: 1.5),
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(4),
                                  child: SizedBox(
                                    height: 3,
                                    child: i < _index
                                        ? const ColoredBox(color: yellowColor)
                                        : i > _index
                                            ? ColoredBox(
                                                color: Colors.white
                                                    .withValues(alpha: 0.28),
                                              )
                                            : AnimatedBuilder(
                                                animation: _progress,
                                                builder: (_, __) =>
                                                    LinearProgressIndicator(
                                                  value: _progress.value,
                                                  backgroundColor: Colors.white
                                                      .withValues(alpha: 0.28),
                                                  color: yellowColor,
                                                  minHeight: 3,
                                                ),
                                              ),
                                  ),
                                ),
                              ),
                            );
                          }),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Container(
                              width: 36,
                              height: 36,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                border:
                                    Border.all(color: yellowColor, width: 2),
                                color: Colors.white,
                              ),
                              clipBehavior: Clip.antiAlias,
                              child: _storyThumb(),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _story.title,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w800,
                                      fontSize: 15,
                                      letterSpacing: -0.2,
                                    ),
                                  ),
                                  Text(
                                    'Story ${_storyIndex + 1}/${widget.stories.length}',
                                    style: TextStyle(
                                      color: Colors.white
                                          .withValues(alpha: 0.55),
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            TextButton(
                              onPressed: () async {
                                if (_closing) return;
                                final story = _story;
                                final nav = Navigator.of(context);
                                await _saveCursor(_index);
                                if (!mounted) return;
                                _closing = true;
                                nav.pop(true);
                                openStorySeeAll(nav.context, story);
                              },
                              style: TextButton.styleFrom(
                                foregroundColor: const Color(0xFF04334A),
                                backgroundColor: yellowColor,
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 8),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                minimumSize: Size.zero,
                                tapTargetSize:
                                    MaterialTapTargetSize.shrinkWrap,
                              ),
                              child: const Text(
                                'Tümü',
                                style: TextStyle(
                                    fontWeight: FontWeight.w800, fontSize: 12),
                              ),
                            ),
                            const SizedBox(width: 4),
                            IconButton(
                              tooltip: 'Kapat',
                              style: IconButton.styleFrom(
                                foregroundColor: Colors.white,
                                backgroundColor:
                                    Colors.black.withValues(alpha: 0.35),
                                minimumSize: const Size(44, 44),
                                tapTargetSize: MaterialTapTargetSize.padded,
                              ),
                              onPressed: () {
                                unawaited(_closeViewer());
                              },
                              icon: const Icon(Icons.close_rounded, size: 26),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              // Jest alanı sadece içerik — üst barı kaplamaz
              Positioned(
                left: 0,
                right: 0,
                top: pad.top + 88,
                bottom: 0,
                child: GestureDetector(
                  behavior: HitTestBehavior.opaque,
                  onTapDown: (_) {
                    _dragMoved = false;
                    _pause();
                  },
                  onTapUp: (details) {
                    if (_dragMoved || _closing) {
                      _resume();
                      return;
                    }
                    _resume();
                    final w = MediaQuery.sizeOf(context).width;
                    if (details.localPosition.dx < w * 0.33) {
                      _prev();
                    } else {
                      _next();
                    }
                  },
                  onTapCancel: _resume,
                  onLongPressStart: (_) => _pause(),
                  onLongPressEnd: (_) => _resume(),
                  onPanStart: (_) {
                    _dragMoved = false;
                    _pause();
                  },
                  onPanUpdate: (d) {
                    if (d.delta.distance > 6) _dragMoved = true;
                  },
                  onPanCancel: () {
                    _dragMoved = false;
                    _resume();
                  },
                  onPanEnd: (d) {
                    final moved = _dragMoved;
                    _dragMoved = false;
                    if (!moved) {
                      _resume();
                      return;
                    }
                    _onPanEnd(d);
                  },
                  child: AnimatedSwitcher(
                    duration: const Duration(milliseconds: 220),
                    switchInCurve: Curves.easeOut,
                    switchOutCurve: Curves.easeIn,
                    child: _ProductSlide(
                      key: ValueKey('${_story.id}-${_products[_index].id}'),
                      product: _products[_index],
                      index: _index,
                      total: _products.length,
                      onOpen: () => _openProduct(_products[_index]),
                    ),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    ),
    );
  }

  Widget _storyThumb() {
    final src = _story.image.trim().isEmpty
        ? ''
        : RemoteUrls.imageUrl(_story.image);
    final initial = _story.title.trim().isEmpty
        ? '?'
        : _story.title.trim()[0].toUpperCase();
    if (src.isEmpty) {
      return Center(
        child: Text(
          initial,
          style: const TextStyle(
            fontWeight: FontWeight.w800,
            color: Color(0xFF04334A),
          ),
        ),
      );
    }
    return Image.network(
      src,
      fit: BoxFit.cover,
      width: double.infinity,
      height: double.infinity,
      alignment: Alignment.center,
      errorBuilder: (_, __, ___) => Center(child: Text(initial)),
    );
  }
}

class _ProductSlide extends StatelessWidget {
  const _ProductSlide({
    super.key,
    required this.product,
    required this.index,
    required this.total,
    required this.onOpen,
  });

  final StoryProductItem product;
  final int index;
  final int total;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    final img = product.thumb.trim().isEmpty
        ? ''
        : RemoteUrls.imageUrl(product.thumb);
    final pad = MediaQuery.paddingOf(context);
    final size = MediaQuery.sizeOf(context);
    // Tüm ürünlerde aynı sabit görsel alanı (farklı oranlar cover ile dolar)
    final imageH = (size.height * 0.46).clamp(240.0, 420.0);

    return Padding(
      padding: EdgeInsets.fromLTRB(16, 8, 16, pad.bottom + 16),
      child: Column(
        children: [
          SizedBox(
            width: double.infinity,
            height: imageH,
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: const Color(0xFFF4F6F8),
                borderRadius: BorderRadius.circular(22),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.35),
                    blurRadius: 28,
                    offset: const Offset(0, 12),
                  ),
                ],
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(22),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    if (img.isEmpty)
                      const ColoredBox(
                        color: Color(0xFFF4F6F8),
                        child: Icon(Icons.image_not_supported_outlined,
                            size: 48, color: Colors.black26),
                      )
                    else
                      Image.network(
                        img,
                        fit: BoxFit.cover,
                        alignment: Alignment.center,
                        width: double.infinity,
                        height: double.infinity,
                        filterQuality: FilterQuality.medium,
                        gaplessPlayback: true,
                        errorBuilder: (_, __, ___) => const ColoredBox(
                          color: Color(0xFFF4F6F8),
                          child: Center(
                            child: Icon(Icons.broken_image_outlined),
                          ),
                        ),
                      ),
                    Positioned(
                      top: 12,
                      right: 12,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.45),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          '${index + 1}/$total',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),
          Expanded(
            child: Align(
              alignment: Alignment.topCenter,
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(18),
                  border:
                      Border.all(color: Colors.white.withValues(alpha: 0.12)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      product.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w700,
                        height: 1.25,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      Utils.formatPrice(
                        product.hasOffer ? product.offerPrice : product.price,
                        context,
                      ),
                      style: const TextStyle(
                        color: yellowColor,
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    if (product.hasOffer) ...[
                      const SizedBox(height: 4),
                      Text(
                        Utils.formatPrice(product.price, context),
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.45),
                          fontSize: 14,
                          decoration: TextDecoration.lineThrough,
                        ),
                      ),
                    ],
                    const SizedBox(height: 14),
                    SizedBox(
                      height: 50,
                      child: ElevatedButton(
                        onPressed: onOpen,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: yellowColor,
                          foregroundColor: const Color(0xFF04334A),
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        child: const Text(
                          'Ürüne git',
                          style: TextStyle(
                              fontWeight: FontWeight.w800, fontSize: 15),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
