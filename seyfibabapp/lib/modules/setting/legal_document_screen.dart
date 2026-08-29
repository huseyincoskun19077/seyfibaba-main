import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:http/http.dart' as http;

import '../../core/remote_urls.dart';
import '../../widgets/rounded_app_bar.dart';
import '../home/widgets/home_theme.dart';

class LegalDocumentScreen extends StatefulWidget {
  final String slug;
  final String? title;

  const LegalDocumentScreen({
    super.key,
    required this.slug,
    this.title,
  });

  @override
  State<LegalDocumentScreen> createState() => _LegalDocumentScreenState();
}

class _LegalDocumentScreenState extends State<LegalDocumentScreen> {
  bool _loading = true;
  String? _error;
  String _content = '';
  String _resolvedTitle = '';
  String _version = '';
  String? _updatedAt;

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
      final response = await http.get(uri, headers: {'Accept': 'application/json'});

      if (response.statusCode != 200) {
        throw Exception('Belge yüklenemedi');
      }

      final body = json.decode(response.body) as Map<String, dynamic>;
      final doc = body['document'] as Map<String, dynamic>? ?? {};

      setState(() {
        _resolvedTitle = '${doc['title'] ?? _resolvedTitle}';
        _content = '${doc['content'] ?? ''}';
        _version = '${doc['version'] ?? ''}';
        _updatedAt = doc['updated_at']?.toString();
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = 'Belge yüklenemedi veya henüz yayınlanmadı.';
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: RoundedAppBar(titleText: _resolvedTitle),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, textAlign: TextAlign.center))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (_version.isNotEmpty || (_updatedAt ?? '').isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: Text(
                            [
                              if (_version.isNotEmpty) 'Versiyon: $_version',
                              if ((_updatedAt ?? '').isNotEmpty)
                                'Güncelleme: ${_updatedAt!.substring(0, 10)}',
                            ].join(' · '),
                            style: const TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                        ),
                      Html(
                        data: _content.isEmpty
                            ? '<p>İçerik henüz eklenmedi.</p>'
                            : _content,
                      ),
                    ],
                  ),
                ),
    );
  }
}
