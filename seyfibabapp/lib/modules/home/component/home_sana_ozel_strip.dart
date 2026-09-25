import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:http/http.dart' as http;

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../../category/component/product_card.dart';
import '../model/product_model.dart';
import '../widgets/home_theme.dart';

/// Stories altı / kategoriler üstü — Sana Özel; yoksa Popüler (sola kayar).
class HomeSanaOzelStrip extends StatefulWidget {
  const HomeSanaOzelStrip({
    super.key,
    this.fallbackProducts = const [],
  });

  /// API boş/hata olursa home popüler ürünleri
  final List<ProductModel> fallbackProducts;

  @override
  State<HomeSanaOzelStrip> createState() => _HomeSanaOzelStripState();
}

class _HomeSanaOzelStripState extends State<HomeSanaOzelStrip> {
  String _title = 'Popüler ürünler';
  List<ProductModel> _products = [];
  bool _loading = true;
  final _scroll = ScrollController();
  Timer? _autoTimer;

  @override
  void initState() {
    super.initState();
    // Önce local fallback ile hemen göster
    if (widget.fallbackProducts.isNotEmpty) {
      _products = widget.fallbackProducts.take(16).toList();
      _title = 'Popüler ürünler';
      _loading = false;
    }
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void didUpdateWidget(covariant HomeSanaOzelStrip oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_products.isEmpty && widget.fallbackProducts.isNotEmpty) {
      setState(() {
        _products = widget.fallbackProducts.take(16).toList();
        _title = 'Popüler ürünler';
        _loading = false;
      });
      _startAutoScroll();
    }
  }

  @override
  void dispose() {
    _autoTimer?.cancel();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      String? token;
      try {
        token = context.read<LoginBloc>().userInfo?.accessToken;
      } catch (_) {}

      final uri = Uri.parse(
        RemoteUrls.personalizedProducts(token: token, limit: 16),
      );
      final headers = <String, String>{'Accept': 'application/json'};
      if (token != null && token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }
      final res = await http
          .get(uri, headers: headers)
          .timeout(const Duration(seconds: 12));

      var products = <ProductModel>[];
      var title = 'Popüler ürünler';

      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        if (body is Map) {
          title = '${body['title'] ?? ''}'.trim();
          final source = '${body['source'] ?? 'popular'}';
          final list = body['products'];
          if (list is List) {
            for (final e in list) {
              if (e is Map) {
                try {
                  products.add(
                    ProductModel.fromMap(Map<String, dynamic>.from(e)),
                  );
                } catch (_) {}
              }
            }
          }
          if (products.isNotEmpty && title.isEmpty) {
            title =
                source == 'personalized' ? 'Sana Özel' : 'Popüler ürünler';
          }
        }
      }

      // Sana özel yok / parse boş → popüler fallback
      if (products.isEmpty) {
        products = widget.fallbackProducts.take(16).toList();
        title = 'Popüler ürünler';
      }

      if (!mounted) return;
      setState(() {
        _title = title;
        _products = products;
        _loading = false;
      });
      _startAutoScroll();
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _products = widget.fallbackProducts.take(16).toList();
        _title = 'Popüler ürünler';
        _loading = false;
      });
      _startAutoScroll();
    }
  }

  void _startAutoScroll() {
    _autoTimer?.cancel();
    if (_products.length < 3) return;
    _autoTimer = Timer.periodic(const Duration(milliseconds: 40), (_) {
      if (!mounted || !_scroll.hasClients) return;
      final max = _scroll.position.maxScrollExtent;
      if (max <= 0) return;
      final next = _scroll.offset + 0.55;
      if (next >= max) {
        _scroll.jumpTo(0);
      } else {
        _scroll.jumpTo(next);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<LoginBloc, LoginModelState>(
      listenWhen: (prev, next) => next.state is LoginStateUpdatedProfile,
      listener: (_, __) => _load(),
      child: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading && _products.isEmpty) {
      return const Padding(
        padding: EdgeInsets.fromLTRB(16, 8, 16, 4),
        child: SizedBox(
          height: 36,
          child: Align(
            alignment: Alignment.centerLeft,
            child: SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          ),
        ),
      );
    }

    if (_products.isEmpty) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.fromLTRB(0, 10, 0, 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    _title,
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                      color: HomeTheme.textDark,
                      letterSpacing: -0.2,
                    ),
                  ),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.pushNamed(
                      context,
                      RouteNames.allPopularProductScreen,
                      arguments: 'popular_category',
                    );
                  },
                  style: TextButton.styleFrom(
                    foregroundColor: const Color(0xFF04334A),
                    backgroundColor: const Color(0xFFFFBB38),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 6),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  child: const Text(
                    'Tümünü gör',
                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 11),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            height: 210,
            child: NotificationListener<ScrollNotification>(
              onNotification: (n) {
                if (n is ScrollStartNotification) {
                  _autoTimer?.cancel();
                } else if (n is ScrollEndNotification) {
                  _startAutoScroll();
                }
                return false;
              },
              child: ListView.separated(
                controller: _scroll,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                scrollDirection: Axis.horizontal,
                itemCount: _products.length,
                separatorBuilder: (_, __) => const SizedBox(width: 10),
                itemBuilder: (context, index) {
                  final p = _products[index];
                  return SizedBox(
                    width: 148,
                    child: ProductCard(productModel: p, width: 148),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
