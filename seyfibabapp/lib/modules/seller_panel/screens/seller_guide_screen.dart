import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_html/flutter_html.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../services/seller_api_service.dart';

class SellerGuideScreen extends StatefulWidget {
  const SellerGuideScreen({super.key});

  @override
  State<SellerGuideScreen> createState() => _SellerGuideScreenState();
}

class _SellerGuideScreenState extends State<SellerGuideScreen> {
  final _service = SellerApiService();
  bool _loading = true;
  String? _error;
  String _title = 'Satıcı Şartlar ve Tanıtım';
  String _subtitle = '';
  String _hero = '';
  List<_GuideHighlight> _highlights = const [];
  List<_GuideSection> _sections = const [];
  Map<String, dynamic> _contact = const {};

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
      final data = await _service.fetchSellerGuide(_token);
      final highlights = <_GuideHighlight>[];
      final rawHighlights = data['highlights'];
      if (rawHighlights is List) {
        for (final h in rawHighlights.whereType<Map>()) {
          final map = Map<String, dynamic>.from(h);
          highlights.add(
            _GuideHighlight(
              title: '${map['title'] ?? ''}',
              text: '${map['text'] ?? ''}',
            ),
          );
        }
      }

      final sections = <_GuideSection>[];
      final rawSections = data['sections'];
      if (rawSections is List) {
        for (final s in rawSections.whereType<Map>()) {
          final map = Map<String, dynamic>.from(s);
          final bullets = <String>[];
          final rawBullets = map['bullets'];
          if (rawBullets is List) {
            for (final b in rawBullets) {
              bullets.add('$b');
            }
          }
          sections.add(
            _GuideSection(
              title: '${map['title'] ?? ''}',
              body: '${map['body'] ?? ''}',
              bullets: bullets,
            ),
          );
        }
      }

      if (!mounted) return;
      setState(() {
        _title = '${data['title'] ?? _title}';
        _subtitle = '${data['subtitle'] ?? ''}';
        _hero = '${data['hero'] ?? ''}';
        _highlights = highlights;
        _sections = sections;
        _contact = data['contact'] is Map
            ? Map<String, dynamic>.from(data['contact'] as Map)
            : {};
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
        title: Text(_title),
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
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
                      children: [
                        Container(
                          padding: const EdgeInsets.all(18),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF0F172A), Color(0xFF1E3A5F)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius:
                                BorderRadius.circular(HomeTheme.radius),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (_subtitle.isNotEmpty)
                                Text(
                                  _subtitle,
                                  style: const TextStyle(
                                    color: Colors.white70,
                                    fontSize: 13,
                                    height: 1.4,
                                  ),
                                ),
                              if (_hero.isNotEmpty) ...[
                                const SizedBox(height: 10),
                                Text(
                                  _hero,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    height: 1.45,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        const SizedBox(height: 14),
                        ..._highlights.map(
                          (h) => Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: Container(
                              padding: const EdgeInsets.all(14),
                              decoration: HomeTheme.cardDecoration(),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    h.title,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      color: HomeTheme.textDark,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    h.text,
                                    style: const TextStyle(
                                      color: HomeTheme.textMuted,
                                      height: 1.4,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 6),
                        ..._sections.map(
                          (s) => Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: Container(
                              padding: const EdgeInsets.all(16),
                              decoration: HomeTheme.cardDecoration(),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    s.title,
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w800,
                                      color: HomeTheme.textDark,
                                    ),
                                  ),
                                  if (s.body.isNotEmpty) ...[
                                    const SizedBox(height: 6),
                                    Html(
                                      data: s.body,
                                      style: {
                                        'body': Style(
                                          margin: Margins.zero,
                                          padding: HtmlPaddings.zero,
                                          color: HomeTheme.textMuted,
                                          fontSize: FontSize(14),
                                        ),
                                      },
                                    ),
                                  ],
                                  if (s.bullets.isNotEmpty) ...[
                                    const SizedBox(height: 6),
                                    ...s.bullets.map(
                                      (b) => Padding(
                                        padding:
                                            const EdgeInsets.only(bottom: 6),
                                        child: Row(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            const Text('•  '),
                                            Expanded(
                                              child: Html(
                                                data: b,
                                                style: {
                                                  'body': Style(
                                                    margin: Margins.zero,
                                                    padding: HtmlPaddings.zero,
                                                    color: HomeTheme.textDark,
                                                    fontSize: FontSize(13.5),
                                                  ),
                                                },
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ),
                        ),
                        if (_contact.isNotEmpty)
                          Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFFF7ED),
                              borderRadius:
                                  BorderRadius.circular(HomeTheme.radius),
                              border: Border.all(
                                color: const Color(0xFFFED7AA),
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  '${_contact['title'] ?? 'Destek'}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Text('${_contact['text'] ?? ''}'),
                                if ((_contact['phone'] ?? '')
                                    .toString()
                                    .isNotEmpty)
                                  Text('Tel: ${_contact['phone']}'),
                                if ((_contact['email'] ?? '')
                                    .toString()
                                    .isNotEmpty)
                                  Text('E-posta: ${_contact['email']}'),
                              ],
                            ),
                          ),
                      ],
                    ),
            ),
    );
  }
}

class _GuideHighlight {
  const _GuideHighlight({required this.title, required this.text});
  final String title;
  final String text;
}

class _GuideSection {
  const _GuideSection({
    required this.title,
    required this.body,
    required this.bullets,
  });
  final String title;
  final String body;
  final List<String> bullets;
}
