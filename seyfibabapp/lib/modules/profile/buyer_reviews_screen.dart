import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:http/http.dart' as http;

import '../../core/remote_urls.dart';
import '../../core/router_name.dart';
import '../../utils/constants.dart';
import '../../utils/utils.dart';
import '../../widgets/custom_image.dart';
import '../../widgets/rounded_app_bar.dart';
import '../authentication/controller/login/login_bloc.dart';
import '../home/widgets/home_theme.dart';

/// Web profil “Yorumlarım” — kullanıcının bıraktığı ürün yorumları.
class BuyerReviewsScreen extends StatefulWidget {
  const BuyerReviewsScreen({super.key});

  @override
  State<BuyerReviewsScreen> createState() => _BuyerReviewsScreenState();
}

class _BuyerReviewsScreenState extends State<BuyerReviewsScreen> {
  bool _loading = true;
  String? _error;
  List<_BuyerReviewItem> _items = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final token = context.read<LoginBloc>().userInfo?.accessToken ?? '';
    if (token.isEmpty) {
      setState(() {
        _loading = false;
        _error = 'Giriş gerekli';
      });
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final uri = Uri.parse(RemoteUrls.userReviews(token));
      final res = await http.get(uri, headers: {'Accept': 'application/json'});
      if (!mounted) return;
      if (res.statusCode < 200 || res.statusCode >= 300) {
        setState(() {
          _loading = false;
          _error = 'Yorumlar yüklenemedi';
        });
        return;
      }
      final body = jsonDecode(res.body);
      final reviews = body is Map ? body['reviews'] : null;
      final data = reviews is Map ? reviews['data'] : (reviews is List ? reviews : null);
      final list = <_BuyerReviewItem>[];
      if (data is List) {
        for (final raw in data) {
          if (raw is! Map) continue;
          list.add(_BuyerReviewItem.fromMap(Map<String, dynamic>.from(raw)));
        }
      }
      setState(() {
        _items = list;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: RoundedAppBar(
        titleText: 'Yorumlarım',
        bgColor: HomeTheme.header,
        textColor: HomeTheme.textDark,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_error!, textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: _load,
                          child: const Text('Tekrar dene'),
                        ),
                      ],
                    ),
                  ),
                )
              : _items.isEmpty
                  ? const Center(
                      child: Text(
                        'Henüz yorumunuz yok.\nTeslim edilen siparişlerden ürünlere yorum bırakabilirsiniz.',
                        textAlign: TextAlign.center,
                        style: TextStyle(height: 1.4, color: HomeTheme.textMuted),
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                        itemCount: _items.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 12),
                        itemBuilder: (context, index) {
                          final item = _items[index];
                          return _ReviewCard(item: item);
                        },
                      ),
                    ),
    );
  }
}

class _ReviewCard extends StatelessWidget {
  const _ReviewCard({required this.item});

  final _BuyerReviewItem item;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: whiteColor,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: item.slug.isEmpty
            ? null
            : () => Navigator.pushNamed(
                  context,
                  RouteNames.productDetailsScreen,
                  arguments: item.slug,
                ),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: const Color(0xFF04334A).withValues(alpha: 0.08),
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: SizedBox(
                  width: 72,
                  height: 72,
                  child: CustomImage(
                    path: item.thumb.isEmpty
                        ? null
                        : RemoteUrls.imageUrl(item.thumb),
                    fit: BoxFit.contain,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (item.createdAt.isNotEmpty)
                      Text(
                        item.createdAt,
                        style: const TextStyle(
                          fontSize: 12,
                          color: HomeTheme.textMuted,
                        ),
                      ),
                    const SizedBox(height: 4),
                    RatingBarIndicator(
                      rating: item.rating,
                      itemBuilder: (_, __) =>
                          const Icon(Icons.star, color: yellowColor),
                      itemCount: 5,
                      itemSize: 16,
                      unratedColor: Colors.grey.shade300,
                    ),
                    const SizedBox(height: 6),
                    Text(
                      item.productName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        color: Color(0xFF04334A),
                      ),
                    ),
                    if (item.review.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        item.review,
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 13,
                          height: 1.35,
                          color: const Color(0xFF04334A).withValues(alpha: 0.7),
                        ),
                      ),
                    ],
                    if (item.status == 0) ...[
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFF6DC),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: yellowColor),
                        ),
                        child: const Text(
                          'Onay bekliyor',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF9A7B2F),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _BuyerReviewItem {
  const _BuyerReviewItem({
    required this.id,
    required this.rating,
    required this.status,
    required this.review,
    required this.createdAt,
    required this.productName,
    required this.slug,
    required this.thumb,
  });

  final int id;
  final double rating;
  final int status;
  final String review;
  final String createdAt;
  final String productName;
  final String slug;
  final String thumb;

  factory _BuyerReviewItem.fromMap(Map<String, dynamic> map) {
    final product = map['product'];
    final p = product is Map ? Map<String, dynamic>.from(product) : <String, dynamic>{};
    final created = '${map['created_at'] ?? ''}';
    return _BuyerReviewItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      rating: Utils.toDouble(map['rating']?.toString()),
      status: int.tryParse('${map['status'] ?? 1}') ?? 1,
      review: '${map['review'] ?? ''}',
      createdAt: created.length >= 10 ? created.substring(0, 10) : created,
      productName: '${p['name'] ?? 'Ürün'}',
      slug: '${p['slug'] ?? ''}',
      thumb: '${p['thumb_image'] ?? ''}',
    );
  }
}
