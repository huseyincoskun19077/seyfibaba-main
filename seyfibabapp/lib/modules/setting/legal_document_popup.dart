import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:http/http.dart' as http;

import '../../core/remote_urls.dart';
import '../home/widgets/home_theme.dart';

Future<void> showLegalDocumentPopup(
  BuildContext context, {
  required String slug,
  String? title,
}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) {
      return DraggableScrollableSheet(
        initialChildSize: 0.88,
        minChildSize: 0.45,
        maxChildSize: 0.95,
        builder: (_, scrollController) {
          return _LegalDocumentPopupBody(
            slug: slug,
            title: title,
            scrollController: scrollController,
          );
        },
      );
    },
  );
}

class _LegalDocumentPopupBody extends StatefulWidget {
  const _LegalDocumentPopupBody({
    required this.slug,
    required this.scrollController,
    this.title,
  });

  final String slug;
  final String? title;
  final ScrollController scrollController;

  @override
  State<_LegalDocumentPopupBody> createState() =>
      _LegalDocumentPopupBodyState();
}

class _LegalDocumentPopupBodyState extends State<_LegalDocumentPopupBody> {
  bool _loading = true;
  String? _error;
  String _content = '';
  String _resolvedTitle = '';
  String _version = '';

  @override
  void initState() {
    super.initState();
    _resolvedTitle = widget.title ?? 'Yasal Belge';
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final uri = Uri.parse('${RemoteUrls.legalDocuments}/${widget.slug}');
      final response =
          await http.get(uri, headers: {'Accept': 'application/json'});

      if (response.statusCode != 200) {
        throw Exception('Belge yüklenemedi');
      }

      final body = json.decode(response.body) as Map<String, dynamic>;
      final doc = body['document'] as Map<String, dynamic>? ?? {};

      setState(() {
        _resolvedTitle = '${doc['title'] ?? _resolvedTitle}';
        _content = '${doc['content'] ?? ''}';
        _version = '${doc['version'] ?? ''}';
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _error = 'Belge yüklenemedi veya henüz yayınlanmadı.';
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: HomeTheme.bg,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          const SizedBox(height: 8),
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.black26,
              borderRadius: BorderRadius.circular(99),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 8, 8),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _resolvedTitle,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF04334A),
                        ),
                      ),
                      if (_version.isNotEmpty)
                        Text(
                          'Sürüm $_version',
                          style: const TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.all(24),
                          child: Text(_error!, textAlign: TextAlign.center),
                        ),
                      )
                    : ListView(
                        controller: widget.scrollController,
                        padding: const EdgeInsets.all(16),
                        children: [
                          Html(
                            data: _content.isEmpty
                                ? '<p>İçerik henüz eklenmedi.</p>'
                                : _content,
                          ),
                        ],
                      ),
          ),
        ],
      ),
    );
  }
}
