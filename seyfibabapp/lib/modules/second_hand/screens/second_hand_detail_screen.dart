import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';

import '../../../core/error/exception.dart';
import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../models/second_hand_models.dart';
import '../services/second_hand_service.dart';
import '../widgets/second_hand_ui.dart';

class SecondHandDetailScreen extends StatefulWidget {
  const SecondHandDetailScreen({super.key, required this.listingId});

  final int listingId;

  @override
  State<SecondHandDetailScreen> createState() => _SecondHandDetailScreenState();
}

class _SecondHandDetailScreenState extends State<SecondHandDetailScreen> {
  final _service = SecondHandService();
  final _messageController = TextEditingController();
  final _pageController = PageController();
  int _imageIndex = 0;

  SecondHandListing? _listing;
  bool _loading = true;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _messageController.dispose();
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final listing = await _service.fetchPublicListing(widget.listingId);
      if (!mounted) return;
      setState(() {
        _listing = listing;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _listing = null;
        _loading = false;
      });
      Utils.errorSnackBar(context, _errorMessage(e));
    }
  }

  String _errorMessage(Object e) {
    if (e is ServerException) return e.message;
    if (e is UnauthorisedException) return e.message;
    if (e is BadRequestException) return e.message;
    return 'İlan yüklenemedi.';
  }

  String _priceLabel(num price) {
    return NumberFormat.currency(
      locale: 'tr_TR',
      symbol: '₺',
      decimalDigits: 0,
    ).format(price);
  }

  Future<void> _sendMessage() async {
    if (!_ensureLoggedIn()) return;

    final text = _messageController.text.trim();
    if (text.isEmpty) {
      Utils.errorSnackBar(context, 'Mesaj yazın.');
      return;
    }

    final token = context.read<LoginBloc>().userInfo?.accessToken ?? '';
    if (token.isEmpty) return;

    setState(() => _sending = true);
    try {
      final conversationId = await _service.sendToListing(
        token: token,
        listingId: widget.listingId,
        body: text,
      );
      if (!mounted) return;
      _messageController.clear();
      setState(() => _sending = false);
      if (conversationId > 0) {
        Navigator.pushNamed(
          context,
          RouteNames.secondHandConversationScreen,
          arguments: conversationId,
        );
      } else {
        Utils.showSnackBar(context, 'Mesajınız gönderildi.');
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _sending = false);
      final msg = _errorMessage(e);
      if (msg.contains('doğrulama') || msg.contains('doğrulamanız')) {
        Utils.errorSnackBar(context, msg);
        Navigator.pushNamed(context, RouteNames.secondHandHubScreen);
      } else {
        Utils.errorSnackBar(context, msg);
      }
    }
  }

  bool _ensureLoggedIn() {
    if (Utils.isLoggedIn(context)) return true;
    Utils.errorSnackBar(context, 'Satıcıya mesaj göndermek için giriş yapın.');
    Navigator.pushNamed(context, RouteNames.authenticationScreen);
    return false;
  }

  void _showMessageSheet() {
    if (!_ensureLoggedIn()) return;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: Container(
          decoration: const BoxDecoration(
            color: ShTheme.card,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: ShTheme.border,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Satıcıya mesaj gönder',
                style: TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w700,
                  color: ShTheme.dark,
                ),
              ),
              const SizedBox(height: 6),
              const Text(
                'Mesajlaşma alıcı ile satıcı arasındadır. Seyfibaba aracıdır, anlaşmadan sorumlu değildir.',
                style: TextStyle(fontSize: 13, color: ShTheme.muted),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _messageController,
                maxLines: 4,
                autofocus: true,
                decoration: InputDecoration(
                  hintText: 'Merhaba, ilan hakkında bilgi almak istiyorum…',
                  filled: true,
                  fillColor: ShTheme.bg,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(ShTheme.radiusSm),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
              const SizedBox(height: 16),
              ShPrimaryButton(
                label: 'Gönder',
                icon: Icons.send_rounded,
                loading: _sending,
                onPressed: _sending
                    ? null
                    : () {
                        Navigator.pop(ctx);
                        _sendMessage();
                      },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _metaChip(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: ShTheme.bg,
        borderRadius: BorderRadius.circular(ShTheme.radiusSm),
        border: Border.all(color: ShTheme.border),
      ),
      child: Text(
        text,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: ShTheme.dark,
        ),
      ),
    );
  }

  Widget _locationRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 48,
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: ShTheme.muted,
            ),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: ShTheme.dark,
            ),
          ),
        ),
      ],
    );
  }

  Widget _imageHeader(SecondHandListing listing) {
    if (listing.images.isEmpty) {
      return Container(
        height: 220,
        width: double.infinity,
        color: const Color(0xFFE8E8ED),
        child: const Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.image_outlined, size: 48, color: ShTheme.muted),
            SizedBox(height: 8),
            Text('Fotoğraf yok', style: TextStyle(color: ShTheme.muted)),
          ],
        ),
      );
    }

    return ColoredBox(
      color: const Color(0xFF1A1A1E),
      child: SizedBox(
        height: 320,
        width: double.infinity,
        child: Stack(
          fit: StackFit.expand,
          children: [
            PageView.builder(
              controller: _pageController,
              onPageChanged: (i) => setState(() => _imageIndex = i),
              itemCount: listing.images.length,
              itemBuilder: (context, index) {
                final image = listing.images[index];
                return CachedNetworkImage(
                  imageUrl: SecondHandService.resolveListingImageUrl(image),
                  fit: BoxFit.contain,
                  width: double.infinity,
                  height: 320,
                  memCacheWidth:
                      (MediaQuery.devicePixelRatioOf(context) * 420).round(),
                  fadeInDuration: const Duration(milliseconds: 120),
                  placeholder: (_, __) => const ColoredBox(
                    color: Color(0xFF1A1A1E),
                    child: Center(
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                  ),
                  errorWidget: (_, __, ___) => const ColoredBox(
                    color: Color(0xFF1A1A1E),
                    child: Center(
                      child: Icon(
                        Icons.broken_image_outlined,
                        size: 40,
                        color: Colors.white54,
                      ),
                    ),
                  ),
                );
              },
            ),
            if (listing.images.length > 1)
              Positioned(
                bottom: 12,
                left: 0,
                right: 0,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(
                    listing.images.length,
                    (i) => Container(
                      width: 8,
                      height: 8,
                      margin: const EdgeInsets.symmetric(horizontal: 3),
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: i == _imageIndex
                            ? ShTheme.primary
                            : Colors.white.withValues(alpha: 0.7),
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        backgroundColor: ShTheme.bg,
        appBar: const ShAppBar(title: 'İlan Detayı'),
        body: const ShLoading(),
      );
    }

    final listing = _listing;
    if (listing == null) {
      return Scaffold(
        backgroundColor: ShTheme.bg,
        appBar: const ShAppBar(title: 'İlan Detayı'),
        body: const ShEmptyState(
          icon: Icons.search_off_rounded,
          title: 'İlan bulunamadı',
          subtitle: 'Bu ilan kaldırılmış, satıldı veya yayında değil.',
        ),
      );
    }

    final condition =
        secondHandConditionLabels[listing.condition] ?? listing.condition;

    return Scaffold(
      backgroundColor: ShTheme.bg,
      appBar: ShAppBar(
        title: listing.title.isEmpty ? 'İlan Detayı' : listing.title,
      ),
      // bottomNavigationBar kullanılmıyor: ShPrimaryButton ekranı boyuyordu
      body: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _imageHeader(listing),
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _priceLabel(listing.price),
                          style: const TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.w800,
                            color: ShTheme.dark,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          listing.title,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: ShTheme.dark,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            _metaChip(condition),
                            _metaChip('${listing.viewsCount} görüntülenme'),
                          ],
                        ),
                        if ((listing.province?.trim().isNotEmpty ?? false) ||
                            (listing.district?.trim().isNotEmpty ?? false)) ...[
                          const SizedBox(height: 16),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: ShTheme.card,
                              borderRadius:
                                  BorderRadius.circular(ShTheme.radius),
                              border: Border.all(color: const Color(0xFFE8E8ED)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Row(
                                  children: [
                                    Icon(
                                      Icons.location_on_rounded,
                                      size: 18,
                                      color: ShTheme.primary,
                                    ),
                                    SizedBox(width: 6),
                                    Text(
                                      'Konum',
                                      style: TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w700,
                                        color: ShTheme.dark,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                if (listing.province?.trim().isNotEmpty ??
                                    false)
                                  _locationRow('İl', listing.province!.trim()),
                                if ((listing.province?.trim().isNotEmpty ??
                                        false) &&
                                    (listing.district?.trim().isNotEmpty ??
                                        false))
                                  const SizedBox(height: 8),
                                if (listing.district?.trim().isNotEmpty ??
                                    false)
                                  _locationRow('İlçe', listing.district!.trim()),
                              ],
                            ),
                          ),
                        ],
                        if (listing.description.isNotEmpty) ...[
                          const SizedBox(height: 20),
                          const Text(
                            'Açıklama',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: ShTheme.dark,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            listing.description,
                            style: const TextStyle(
                              fontSize: 14,
                              height: 1.5,
                              color: ShTheme.muted,
                            ),
                          ),
                        ],
                        if ((listing.sellerBusinessName?.trim().isNotEmpty ??
                                false) ||
                            listing.sellerVerified) ...[
                          const SizedBox(height: 20),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.symmetric(
                              horizontal: 14,
                              vertical: 12,
                            ),
                            decoration: ShTheme.cardDecoration(),
                            child: Row(
                              children: [
                                const Icon(
                                  Icons.store_outlined,
                                  size: 20,
                                  color: ShTheme.muted,
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'Satıcı',
                                        style: TextStyle(
                                          fontSize: 11,
                                          color: ShTheme.muted,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        (listing.sellerBusinessName ?? '')
                                                .trim()
                                                .isNotEmpty
                                            ? listing.sellerBusinessName!.trim()
                                            : 'Kuaför satıcı',
                                        style: const TextStyle(
                                          fontSize: 14,
                                          fontWeight: FontWeight.w600,
                                          color: ShTheme.dark,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                if (listing.sellerVerified)
                                  const Row(
                                    children: [
                                      Icon(
                                        Icons.verified_rounded,
                                        size: 18,
                                        color: Color(0xFF166534),
                                      ),
                                      SizedBox(width: 4),
                                      Text(
                                        'Doğrulanmış',
                                        style: TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w800,
                                          color: Color(0xFF166534),
                                        ),
                                      ),
                                    ],
                                  ),
                              ],
                            ),
                          ),
                        ],
                        const SizedBox(height: 12),
                        const ShMarketplaceNotice(),
                        const SizedBox(height: 12),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (_listing?.userId !=
              context.read<LoginBloc>().userInfo?.user.id)
            Material(
              color: ShTheme.card,
              elevation: 6,
              shadowColor: Colors.black26,
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
                  child: ShPrimaryButton(
                    label: 'Satıcıya Mesaj Gönder',
                    icon: Icons.chat_bubble_outline_rounded,
                    onPressed: _showMessageSheet,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
