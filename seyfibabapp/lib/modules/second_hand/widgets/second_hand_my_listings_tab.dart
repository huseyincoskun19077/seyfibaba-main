import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../models/second_hand_models.dart';
import '../services/second_hand_service.dart';
import '../widgets/second_hand_ui.dart';

class SecondHandMyListingsTab extends StatefulWidget {
  const SecondHandMyListingsTab({
    super.key,
    required this.service,
    required this.token,
    required this.errorMessage,
    required this.onAddListing,
    required this.onNeedVerification,
  });

  final SecondHandService service;
  final String token;
  final String Function(Object) errorMessage;
  final VoidCallback onAddListing;
  final VoidCallback onNeedVerification;

  @override
  State<SecondHandMyListingsTab> createState() =>
      _SecondHandMyListingsTabState();
}

class _SecondHandMyListingsTabState extends State<SecondHandMyListingsTab> {
  final List<SecondHandListing> _items = [];
  bool _loading = true;
  bool _needsVerification = false;
  String? _statusFilter;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (widget.token.isEmpty) {
      setState(() {
        _loading = false;
        _items.clear();
      });
      return;
    }

    setState(() => _loading = true);
    try {
      final result = await widget.service.fetchMyListings(
        token: widget.token,
        status: _statusFilter,
      );
      if (!mounted) return;
      setState(() {
        _items
          ..clear()
          ..addAll(result.items);
        _loading = false;
        _needsVerification = false;
      });
    } catch (e) {
      if (!mounted) return;
      final msg = widget.errorMessage(e);
      setState(() {
        _loading = false;
        _needsVerification =
            msg.contains('doğrulama') || msg.contains('doğrulamanız');
      });
      if (!_needsVerification) {
        Utils.errorSnackBar(context, msg);
      }
    }
  }

  Future<void> _action(Future<String> Function() fn) async {
    try {
      final msg = await fn();
      if (!mounted) return;
      Utils.showSnackBar(context, msg);
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  Future<void> _publishDraft(int listingId) async {
    Map<String, String> rules = {};
    try {
      rules = await widget.service.fetchListingRules();
    } catch (_) {}
    if (!mounted) return;
    final ok = await showSecondHandTermsDialog(
      context,
      title: (rules['title']?.isNotEmpty ?? false)
          ? rules['title']!
          : 'İkinci El İlan Kuralları',
      content: rules['content'] ?? '',
      acceptLabel: 'İkinci El İlan Kuralları’nı okudum ve kabul ediyorum',
      confirmLabel: 'Kabul Et ve Yayına Gönder',
    );
    if (!ok || !mounted) return;
    await _action(() => widget.service.publishListing(
          token: widget.token,
          id: listingId,
        ));
  }

  String _priceLabel(num price) {
    return NumberFormat.currency(
      locale: 'tr_TR',
      symbol: '₺',
      decimalDigits: 0,
    ).format(price);
  }

  @override
  Widget build(BuildContext context) {
    if (widget.token.isEmpty) {
      return ShEmptyState(
        icon: Icons.login_rounded,
        title: 'Giriş yapın',
        subtitle: 'İlanlarınızı görmek için hesabınıza giriş yapmalısınız.',
        action: ShPrimaryButton(
          label: 'Giriş Yap',
          onPressed: () {
            Utils.showSnackBarWithLogin(
              context,
              'İlanlarınız için giriş yapın',
              () => Navigator.pushNamed(
                context,
                RouteNames.authenticationScreen,
              ),
            );
          },
        ),
      );
    }

    if (_needsVerification) {
      return ShEmptyState(
        icon: Icons.verified_user_outlined,
        title: 'Önce doğrulama gerekli',
        subtitle: 'İlanlarınızı görmek için kuaför doğrulamasını tamamlayın.',
        action: ShPrimaryButton(
          label: 'Doğrulamaya Git',
          onPressed: widget.onNeedVerification,
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (_statusFilter == 'sold')
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: Text(
              'Satılan ilanlar yalnızca sizde görünür; diğer kullanıcılar göremez.',
              style: TextStyle(
                fontSize: 12,
                color: ShTheme.muted.withValues(alpha: 0.9),
                height: 1.4,
              ),
            ),
          ),
        SizedBox(
          height: 44,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            children: [
              _statusChip('Tümü', null),
              _statusChip('Satılanlar', 'sold'),
              _statusChip('Yayında', 'active'),
              _statusChip('Pasif', 'inactive'),
              _statusChip('Taslak', 'draft'),
              _statusChip('Onay bekliyor', 'pending'),
              _statusChip('Reddedildi', 'rejected'),
            ],
          ),
        ),
        Expanded(
          child: _loading
              ? const ShLoading()
              : _items.isEmpty
                  ? ShEmptyState(
                      icon: Icons.inventory_2_outlined,
                      title: 'Henüz ilanınız yok',
                      subtitle: 'İlk ilanınızı ekleyerek satışa başlayın.',
                      action: ShPrimaryButton(
                        label: 'İlan Ekle',
                        icon: Icons.add_rounded,
                        onPressed: widget.onAddListing,
                      ),
                    )
                  : RefreshIndicator(
                      color: ShTheme.primary,
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: _items.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 10),
                        itemBuilder: (context, index) {
                          final listing = _items[index];
                          final statusLabel =
                              secondHandStatusLabels[listing.status] ??
                                  listing.status;
                          final imageId = listing.images.isNotEmpty
                              ? listing.images.first.id
                              : null;
                          return Container(
                            decoration: ShTheme.cardDecoration(),
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      if (imageId != null)
                                        ClipRRect(
                                          borderRadius: BorderRadius.circular(
                                            ShTheme.radiusSm,
                                          ),
                                          child: ColoredBox(
                                            color: const Color(0xFFF0F0F3),
                                            child: CachedNetworkImage(
                                              width: 64,
                                              height: 64,
                                              fit: BoxFit.contain,
                                              imageUrl: SecondHandService
                                                  .resolveListingImageUrl(
                                                listing.images.first,
                                              ),
                                              errorWidget: (_, __, ___) =>
                                                  Container(
                                                color: ShTheme.bg,
                                                child: const Icon(
                                                  Icons.broken_image_outlined,
                                                  color: ShTheme.muted,
                                                ),
                                              ),
                                            ),
                                          ),
                                        )
                                      else
                                        Container(
                                          width: 64,
                                          height: 64,
                                          decoration: BoxDecoration(
                                            color: ShTheme.bg,
                                            borderRadius: BorderRadius.circular(
                                              ShTheme.radiusSm,
                                            ),
                                          ),
                                          child: const Icon(
                                            Icons.image_outlined,
                                            color: ShTheme.muted,
                                          ),
                                        ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              listing.title,
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                              style: const TextStyle(
                                                fontWeight: FontWeight.w600,
                                                fontSize: 14,
                                              ),
                                            ),
                                            const SizedBox(height: 4),
                                            Text(
                                              _priceLabel(listing.price),
                                              style: const TextStyle(
                                                fontWeight: FontWeight.w800,
                                                fontSize: 16,
                                              ),
                                            ),
                                            const SizedBox(height: 6),
                                            ShStatusBadge(
                                              label: statusLabel,
                                              status: listing.status,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                      if (listing.status == 'sold')
                                        const Padding(
                                          padding: EdgeInsets.only(top: 4),
                                          child: Text(
                                            'Bu ilan artık herkese açık listede görünmez.',
                                            style: TextStyle(
                                              fontSize: 11,
                                              color: ShTheme.muted,
                                            ),
                                          ),
                                        ),
                                      const SizedBox(height: 10),
                                  Wrap(
                                    spacing: 8,
                                    runSpacing: 6,
                                    children: [
                                      if (listing.status == 'draft')
                                        ShOutlineButton(
                                          small: true,
                                          label: 'Yayına Gönder',
                                          onPressed: () => _publishDraft(listing.id),
                                        ),
                                      if (listing.status == 'active')
                                        ShOutlineButton(
                                          small: true,
                                          label: 'Pasifleştir',
                                          onPressed: () => _action(() =>
                                              widget.service.deactivateListing(
                                                token: widget.token,
                                                id: listing.id,
                                              )),
                                        ),
                                      if (listing.status == 'inactive')
                                        ShOutlineButton(
                                          small: true,
                                          label: 'Aktifleştir',
                                          onPressed: () => _action(() =>
                                              widget.service.activateListing(
                                                token: widget.token,
                                                id: listing.id,
                                              )),
                                        ),
                                      if (listing.status == 'active' ||
                                          listing.status == 'inactive')
                                        ShOutlineButton(
                                          small: true,
                                          label: 'Satıldı',
                                          onPressed: () => _action(() =>
                                              widget.service.markSoldListing(
                                                token: widget.token,
                                                id: listing.id,
                                              )),
                                        ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _statusChip(String label, String? value) {
    final selected = _statusFilter == value;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ShFilterChip(
        label: label,
        selected: selected,
        onTap: () {
          setState(() => _statusFilter = value);
          _load();
        },
      ),
    );
  }
}
