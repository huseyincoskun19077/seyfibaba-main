import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/remote_urls.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../services/seller_api_service.dart';

class SellerBrandsScreen extends StatefulWidget {
  const SellerBrandsScreen({super.key});

  @override
  State<SellerBrandsScreen> createState() => _SellerBrandsScreenState();
}

class _SellerBrandsScreenState extends State<SellerBrandsScreen> {
  final _service = SellerApiService();
  List<Map<String, dynamic>> _brands = const [];
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
      final data = await _service.fetchBrands(_token);
      final my = data['my_brands'];
      final list = my is List
          ? my
              .whereType<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList()
          : <Map<String, dynamic>>[];
      if (!mounted) return;
      setState(() {
        _brands = list;
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

  Future<void> _showAddDialog() async {
    final nameCtrl = TextEditingController();
    String? logoPath;

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setLocal) {
            return AlertDialog(
              title: const Text('Marka Ekle'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextField(
                    controller: nameCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Marka adı *',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (logoPath != null)
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.file(
                        File(logoPath!),
                        height: 80,
                        width: double.infinity,
                        fit: BoxFit.cover,
                      ),
                    )
                  else
                    const Text(
                      'Logo seçin (zorunlu)',
                      style: TextStyle(color: HomeTheme.textMuted),
                    ),
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                    onPressed: () async {
                      final file = await ImagePicker().pickImage(
                        source: ImageSource.gallery,
                        imageQuality: 85,
                        maxWidth: 1200,
                      );
                      if (file == null) return;
                      setLocal(() => logoPath = file.path);
                    },
                    icon: const Icon(Icons.image_outlined),
                    label: Text(logoPath == null ? 'Logo Seç' : 'Logoyu Değiştir'),
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: const Text('İptal'),
                ),
                FilledButton(
                  onPressed: () => Navigator.pop(context, true),
                  child: const Text('Kaydet'),
                ),
              ],
            );
          },
        );
      },
    );

    final name = nameCtrl.text.trim();
    nameCtrl.dispose();
    if (ok != true || !mounted) return;
    if (name.length < 2) {
      Utils.errorSnackBar(context, 'Marka adı girin');
      return;
    }
    if (logoPath == null) {
      Utils.errorSnackBar(context, 'Logo seçin');
      return;
    }

    Utils.loadingDialog(context);
    try {
      await _service.createBrand(
        token: _token,
        name: name,
        logoPath: logoPath!,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Marka eklendi');
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Markalarım'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
        actions: [
          IconButton(
            onPressed: _showAddDialog,
            icon: const Icon(Icons.add),
            tooltip: 'Marka ekle',
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _showAddDialog,
        backgroundColor: HomeTheme.brandYellow,
        foregroundColor: HomeTheme.textDark,
        child: const Icon(Icons.add),
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
                  : _brands.isEmpty
                      ? ListView(
                          children: const [
                            SizedBox(height: 120),
                            Center(
                              child: Text(
                                'Henüz marka eklemediniz',
                                style: TextStyle(color: HomeTheme.textMuted),
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
                          itemCount: _brands.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            final b = _brands[index];
                            final logo = '${b['logo'] ?? ''}';
                            return Container(
                              padding: const EdgeInsets.all(12),
                              decoration: HomeTheme.cardDecoration(),
                              child: Row(
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: logo.isEmpty
                                        ? Container(
                                            width: 48,
                                            height: 48,
                                            color: HomeTheme.border
                                                .withValues(alpha: 0.3),
                                            child: const Icon(
                                              Icons.branding_watermark_outlined,
                                            ),
                                          )
                                        : Image.network(
                                            RemoteUrls.imageUrl(logo),
                                            width: 48,
                                            height: 48,
                                            fit: BoxFit.cover,
                                            errorBuilder: (_, __, ___) =>
                                                Container(
                                              width: 48,
                                              height: 48,
                                              color: HomeTheme.border
                                                  .withValues(alpha: 0.3),
                                              child: const Icon(
                                                Icons.broken_image,
                                              ),
                                            ),
                                          ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Text(
                                      '${b['name'] ?? 'Marka'}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
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
