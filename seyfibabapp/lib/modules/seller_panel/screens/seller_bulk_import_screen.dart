import 'dart:async';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:share_plus/share_plus.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_bulk_import_model.dart';
import '../services/seller_api_service.dart';
import '../widgets/seller_kyc_banner.dart';

class SellerBulkImportScreen extends StatefulWidget {
  const SellerBulkImportScreen({super.key});

  @override
  State<SellerBulkImportScreen> createState() => _SellerBulkImportScreenState();
}

class _SellerBulkImportScreenState extends State<SellerBulkImportScreen> {
  final _service = SellerApiService();
  List<SellerBulkImportModel> _imports = const [];
  bool _loading = true;
  String? _error;
  String _kycStatus = 'approved';
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait([
        _service.fetchBulkImports(_token),
        _service.fetchKycStatus(_token),
      ]);
      if (!mounted) return;
      setState(() {
        _imports = results[0] as List<SellerBulkImportModel>;
        _kycStatus = results[1] as String;
        _loading = false;
      });
      _maybeStartPolling();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  void _maybeStartPolling() {
    final pending = _imports.any((e) => !e.isDone);
    _pollTimer?.cancel();
    if (!pending) return;
    _pollTimer = Timer.periodic(const Duration(seconds: 4), (_) async {
      try {
        final imports = await _service.fetchBulkImports(_token);
        if (!mounted) return;
        setState(() => _imports = imports);
        if (!imports.any((e) => !e.isDone)) {
          _pollTimer?.cancel();
        }
      } catch (_) {}
    });
  }

  Future<void> _download({required bool sample}) async {
    Utils.loadingDialog(context);
    try {
      final file = await _service.downloadBulkTemplate(_token, sample: sample);
      if (!mounted) return;
      Utils.closeDialog(context);
      await Share.shareXFiles(
        [XFile(file.path)],
        text: sample ? 'Örnek Excel' : 'Ürün şablonu',
      );
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _pickAndUpload() async {
    if (_kycStatus != 'approved') {
      Utils.errorSnackBar(context, 'Excel yüklemek için KYC onayınız gerekli.');
      return;
    }
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const ['csv', 'txt', 'xlsx', 'xls'],
      withData: false,
    );
    if (result == null || result.files.isEmpty) return;
    final path = result.files.single.path;
    if (path == null || path.isEmpty) {
      if (!mounted) return;
      Utils.errorSnackBar(context, 'Dosya yolu alınamadı');
      return;
    }

    if (!mounted) return;
    Utils.loadingDialog(context);
    try {
      final response = await _service.uploadBulkImport(
        token: _token,
        filePath: path,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        '${response['message'] ?? 'Dosya yüklendi, işleniyor.'}',
      );
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Color _statusColor(String status) {
    return switch (status) {
      'completed' => const Color(0xFF34A853),
      'failed' => Colors.redAccent,
      'processing' || 'pending' => const Color(0xFFF9A825),
      _ => HomeTheme.textMuted,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Excel Toplu Yükleme'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              color: HomeTheme.brandYellow,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  SellerKycBanner(kycStatus: _kycStatus),
                  const Text(
                    'Web’deki gibi CSV/XLSX yükleyin. Şablon indirip doldurun, sonra yükleyin.',
                    style: TextStyle(color: HomeTheme.textMuted, height: 1.4),
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      OutlinedButton.icon(
                        onPressed: () => _download(sample: false),
                        icon: const Icon(Icons.download_outlined),
                        label: const Text('CSV Şablon'),
                      ),
                      OutlinedButton.icon(
                        onPressed: () => _download(sample: true),
                        icon: const Icon(Icons.table_view_outlined),
                        label: const Text('Örnek Excel'),
                      ),
                      FilledButton.icon(
                        onPressed: _pickAndUpload,
                        icon: const Icon(Icons.upload_file),
                        label: const Text('Dosya Yükle'),
                        style: FilledButton.styleFrom(
                          backgroundColor: HomeTheme.brandYellow,
                          foregroundColor: HomeTheme.textDark,
                        ),
                      ),
                    ],
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                  ],
                  const SizedBox(height: 20),
                  const Text(
                    'Son Yüklemeler',
                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                  ),
                  const SizedBox(height: 8),
                  if (_imports.isEmpty)
                    const Padding(
                      padding: EdgeInsets.only(top: 40),
                      child: Center(
                        child: Text(
                          'Henüz yükleme yok',
                          style: TextStyle(color: HomeTheme.textMuted),
                        ),
                      ),
                    )
                  else
                    ..._imports.map((item) {
                      return Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(14),
                        decoration: HomeTheme.cardDecoration(),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    item.originalName.isEmpty
                                        ? 'Import #${item.id}'
                                        : item.originalName,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                                Text(
                                  item.status,
                                  style: TextStyle(
                                    color: _statusColor(item.status),
                                    fontWeight: FontWeight.w800,
                                    fontSize: 12,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              'Toplam: ${item.totalRows} · İşlenen: ${item.processedRows} · Başarılı: ${item.successCount} · Hata: ${item.errorCount}',
                              style: const TextStyle(
                                fontSize: 12,
                                color: HomeTheme.textMuted,
                              ),
                            ),
                            if (item.errorLog.isNotEmpty) ...[
                              const SizedBox(height: 6),
                              Text(
                                item.errorLog.take(3).join('\n'),
                                style: const TextStyle(
                                  fontSize: 11,
                                  color: Colors.redAccent,
                                ),
                              ),
                            ],
                          ],
                        ),
                      );
                    }),
                ],
              ),
            ),
    );
  }
}
