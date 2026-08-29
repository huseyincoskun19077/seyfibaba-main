import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../services/seller_api_service.dart';

class SellerReviewsScreen extends StatefulWidget {
  const SellerReviewsScreen({super.key});

  @override
  State<SellerReviewsScreen> createState() => _SellerReviewsScreenState();
}

class _SellerReviewsScreenState extends State<SellerReviewsScreen> {
  final _service = SellerApiService();
  List<Map<String, dynamic>> _reviews = const [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final reviews = await _service.fetchReviews(_token);
      if (!mounted) return;
      setState(() {
        _reviews = reviews;
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

  String _productName(Map<String, dynamic> review) {
    final product = review['product'];
    if (product is Map) return '${product['name'] ?? ''}';
    return '${review['product_name'] ?? ''}';
  }

  String _userName(Map<String, dynamic> review) {
    final user = review['user'];
    if (user is Map) {
      final name = '${user['name'] ?? ''}'.trim();
      if (name.isNotEmpty) return name;
      return '${user['email'] ?? 'Kullanıcı'}';
    }
    return '${review['user_name'] ?? 'Kullanıcı'}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Ürün Yorumları'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              color: HomeTheme.brandYellow,
              child: _error != null
                  ? ListView(
                      children: [
                        const SizedBox(height: 80),
                        Center(child: Text(_error!)),
                        const SizedBox(height: 12),
                        Center(
                          child: FilledButton(
                            onPressed: _load,
                            child: const Text('Tekrar Dene'),
                          ),
                        ),
                      ],
                    )
                  : _reviews.isEmpty
                      ? ListView(
                          children: const [
                            SizedBox(height: 120),
                            Center(
                              child: Text(
                                'Henüz yorum yok',
                                style: TextStyle(color: HomeTheme.textMuted),
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.all(16),
                          itemCount: _reviews.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            final r = _reviews[index];
                            final rating =
                                int.tryParse('${r['rating'] ?? 0}') ?? 0;
                            final status =
                                int.tryParse('${r['status'] ?? 0}') ?? 0;
                            final active = status == 1;
                            final review =
                                '${r['review'] ?? r['comment'] ?? ''}'.trim();
                            return Container(
                              padding: const EdgeInsets.all(14),
                              decoration: HomeTheme.cardDecoration(),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          _productName(r).isEmpty
                                              ? 'Ürün'
                                              : _productName(r),
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w800,
                                          ),
                                        ),
                                      ),
                                      Text(
                                        active ? 'Yayında' : 'Gizli',
                                        style: TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w700,
                                          color: active
                                              ? const Color(0xFF34A853)
                                              : Colors.redAccent,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Row(
                                    children: [
                                      ...List.generate(
                                        5,
                                        (i) => Icon(
                                          i < rating
                                              ? Icons.star
                                              : Icons.star_border,
                                          size: 16,
                                          color: HomeTheme.brandYellow,
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          _userName(r),
                                          style: const TextStyle(
                                            fontSize: 12,
                                            color: HomeTheme.textMuted,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  if (review.isNotEmpty) ...[
                                    const SizedBox(height: 8),
                                    Text(
                                      review,
                                      style: const TextStyle(
                                        fontSize: 13,
                                        height: 1.35,
                                      ),
                                    ),
                                  ],
                                  const SizedBox(height: 8),
                                  const Text(
                                    'Yorum görünürlüğünü yalnızca yönetici değiştirebilir.',
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: HomeTheme.textMuted,
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
            ),
    );
  }
}
