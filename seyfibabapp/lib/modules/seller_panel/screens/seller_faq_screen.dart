import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_html/flutter_html.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../services/seller_api_service.dart';

class SellerFaqScreen extends StatefulWidget {
  const SellerFaqScreen({super.key});

  @override
  State<SellerFaqScreen> createState() => _SellerFaqScreenState();
}

class _SellerFaqScreenState extends State<SellerFaqScreen> {
  final _service = SellerApiService();
  String _intro = '';
  List<_FaqSection> _sections = const [];
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
      final data = await _service.fetchSellerFaq(_token);
      final intro = '${data['intro'] ?? ''}';
      final rawSections = data['sections'];
      final sections = <_FaqSection>[];
      if (rawSections is List) {
        for (final s in rawSections.whereType<Map>()) {
          final map = Map<String, dynamic>.from(s);
          final items = <_FaqItem>[];
          final rawItems = map['items'];
          if (rawItems is List) {
            for (final i in rawItems.whereType<Map>()) {
              final item = Map<String, dynamic>.from(i);
              items.add(
                _FaqItem(
                  q: '${item['q'] ?? item['question'] ?? ''}',
                  a: '${item['a'] ?? item['answer'] ?? ''}',
                ),
              );
            }
          }
          sections.add(
            _FaqSection(
              title: '${map['title'] ?? 'SSS'}',
              items: items,
            ),
          );
        }
      }
      if (!mounted) return;
      setState(() {
        _intro = intro;
        _sections = sections;
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
      appBar: AppBar(
        title: const Text('Satıcı SSS'),
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
                  : ListView(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
                      children: [
                        if (_intro.isNotEmpty)
                          Container(
                            padding: const EdgeInsets.all(14),
                            decoration: HomeTheme.cardDecoration(),
                            child: Text(
                              _intro,
                              style: const TextStyle(
                                height: 1.4,
                                color: HomeTheme.textMuted,
                              ),
                            ),
                          ),
                        if (_intro.isNotEmpty) const SizedBox(height: 16),
                        if (_sections.isEmpty)
                          const Padding(
                            padding: EdgeInsets.only(top: 80),
                            child: Center(
                              child: Text(
                                'SSS içeriği bulunamadı',
                                style: TextStyle(color: HomeTheme.textMuted),
                              ),
                            ),
                          )
                        else
                          ..._sections.map((section) {
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: Container(
                                decoration: HomeTheme.cardDecoration(),
                                child: Theme(
                                  data: Theme.of(context).copyWith(
                                    dividerColor: Colors.transparent,
                                  ),
                                  child: ExpansionTile(
                                    initiallyExpanded: true,
                                    title: Text(
                                      section.title,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                    children: section.items.map((item) {
                                      return ExpansionTile(
                                        title: Text(
                                          item.q,
                                          style: const TextStyle(
                                            fontSize: 14,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        childrenPadding:
                                            const EdgeInsets.fromLTRB(
                                          16,
                                          0,
                                          16,
                                          12,
                                        ),
                                        children: [
                                          Html(data: item.a),
                                        ],
                                      );
                                    }).toList(),
                                  ),
                                ),
                              ),
                            );
                          }),
                      ],
                    ),
            ),
    );
  }
}

class _FaqSection {
  const _FaqSection({required this.title, required this.items});
  final String title;
  final List<_FaqItem> items;
}

class _FaqItem {
  const _FaqItem({required this.q, required this.a});
  final String q;
  final String a;
}
