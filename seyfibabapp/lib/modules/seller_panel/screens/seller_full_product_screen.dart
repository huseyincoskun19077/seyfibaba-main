import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_catalog_option.dart';
import '../services/seller_api_service.dart';

class SellerFullProductScreen extends StatefulWidget {
  const SellerFullProductScreen({super.key});

  @override
  State<SellerFullProductScreen> createState() =>
      _SellerFullProductScreenState();
}

class _SellerFullProductScreenState extends State<SellerFullProductScreen> {
  final _service = SellerApiService();
  final _nameCtrl = TextEditingController();
  final _shortNameCtrl = TextEditingController();
  final _slugCtrl = TextEditingController();
  final _shortDescCtrl = TextEditingController();
  final _longDescCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _offerCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController(text: '1');
  final _skuCtrl = TextEditingController();
  final _tagsCtrl = TextEditingController();
  final _seoTitleCtrl = TextEditingController();
  final _seoDescCtrl = TextEditingController();

  SellerProductCreateMeta? _meta;
  List<SellerCatalogOption> _subs = const [];
  int? _categoryId;
  int? _subCategoryId;
  int? _brandId;
  String? _imagePath;
  bool _loadingMeta = true;
  bool _submitting = false;
  bool _aiLoading = false;
  String? _metaError;

  @override
  void initState() {
    super.initState();
    _loadMeta();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _shortNameCtrl.dispose();
    _slugCtrl.dispose();
    _shortDescCtrl.dispose();
    _longDescCtrl.dispose();
    _priceCtrl.dispose();
    _offerCtrl.dispose();
    _qtyCtrl.dispose();
    _skuCtrl.dispose();
    _tagsCtrl.dispose();
    _seoTitleCtrl.dispose();
    _seoDescCtrl.dispose();
    super.dispose();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<void> _loadMeta() async {
    setState(() {
      _loadingMeta = true;
      _metaError = null;
    });
    try {
      final meta = await _service.fetchCreateMeta(_token);
      if (!mounted) return;
      setState(() {
        _meta = meta;
        _loadingMeta = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadingMeta = false;
        _metaError = '$e';
      });
    }
  }

  Future<void> _onCategoryChanged(int? id) async {
    setState(() {
      _categoryId = id;
      _subCategoryId = null;
      _subs = const [];
    });
    if (id == null) return;
    try {
      final subs = await _service.fetchSubcategories(_token, id);
      if (!mounted) return;
      setState(() => _subs = subs);
    } catch (_) {}
  }

  Future<void> _pickImage() async {
    final file = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1600,
    );
    if (file == null) return;
    setState(() => _imagePath = file.path);
  }

  Future<void> _runAi() async {
    final name = _nameCtrl.text.trim();
    if (name.isEmpty) {
      Utils.errorSnackBar(context, 'Önce ürün adı girin');
      return;
    }
    String? categoryName;
    for (final c in _meta?.categories ?? const <SellerCatalogOption>[]) {
      if (c.id == _categoryId) {
        categoryName = c.name;
        break;
      }
    }

    setState(() => _aiLoading = true);
    Utils.loadingDialog(context);
    try {
      final result = await _service.generateAiContent(
        token: _token,
        productName: name,
        categoryName: categoryName,
        action: 'full',
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      final results = result['results'];
      if (results is Map) {
        final r = Map<String, dynamic>.from(results);
        if ('${r['name'] ?? ''}'.trim().isNotEmpty) {
          _nameCtrl.text = '${r['name']}';
        }
        if ('${r['short_name'] ?? ''}'.trim().isNotEmpty) {
          _shortNameCtrl.text = '${r['short_name']}';
        } else if (_shortNameCtrl.text.trim().isEmpty) {
          _shortNameCtrl.text = _nameCtrl.text;
        }
        if ('${r['short_description'] ?? ''}'.trim().isNotEmpty) {
          _shortDescCtrl.text = '${r['short_description']}';
        }
        if ('${r['long_description'] ?? ''}'.trim().isNotEmpty) {
          _longDescCtrl.text = '${r['long_description']}';
        }
        if ('${r['seo_title'] ?? ''}'.trim().isNotEmpty) {
          _seoTitleCtrl.text = '${r['seo_title']}';
        }
        if ('${r['seo_description'] ?? ''}'.trim().isNotEmpty) {
          _seoDescCtrl.text = '${r['seo_description']}';
        }
        if ('${r['tags'] ?? ''}'.trim().isNotEmpty) {
          _tagsCtrl.text = '${r['tags']}';
        }
        if ('${r['slug'] ?? ''}'.trim().isNotEmpty) {
          _slugCtrl.text = '${r['slug']}';
        }
        setState(() {});
        Utils.showSnackBar(context, 'AI içerik uygulandı');
      } else {
        Utils.errorSnackBar(context, 'AI sonuç döndürmedi');
      }
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _aiLoading = false);
    }
  }

  String _kycAwareMessage(Object e) {
    final msg = '$e';
    final lower = msg.toLowerCase();
    if (lower.contains('403') ||
        lower.contains('kyc') ||
        lower.contains('doğrulama') ||
        lower.contains('dogrulama')) {
      return 'Ürün ekleyebilmek için KYC doğrulamanız onaylanmış olmalı. '
          'Lütfen belge ve hesap bilgilerinizi tamamlayın.\n\n$msg';
    }
    return msg;
  }

  Future<void> _submit() async {
    final name = _nameCtrl.text.trim();
    final shortName = _shortNameCtrl.text.trim().isEmpty
        ? name
        : _shortNameCtrl.text.trim();
    final qty = int.tryParse(_qtyCtrl.text.trim()) ?? 0;
    final price = double.tryParse(_priceCtrl.text.trim().replaceAll(',', '.'));

    if (name.length < 2) {
      Utils.errorSnackBar(context, 'Ürün adı en az 2 karakter olmalı');
      return;
    }
    if (_shortDescCtrl.text.trim().isEmpty ||
        _longDescCtrl.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Kısa ve uzun açıklama zorunlu');
      return;
    }
    if (qty < 1) {
      Utils.errorSnackBar(context, 'Geçerli stok girin');
      return;
    }
    if (price == null || price < 0.01) {
      Utils.errorSnackBar(context, 'Geçerli fiyat girin');
      return;
    }
    if (_categoryId == null) {
      Utils.errorSnackBar(context, 'Kategori seçin');
      return;
    }
    if (_imagePath == null) {
      Utils.errorSnackBar(context, 'Ürün fotoğrafı seçin');
      return;
    }

    setState(() => _submitting = true);
    Utils.loadingDialog(context);
    try {
      await _service.createFullProduct(
        token: _token,
        fields: {
          'name': name,
          'short_name': shortName,
          'slug': _slugCtrl.text.trim().isEmpty
              ? name
              : _slugCtrl.text.trim(),
          'category': '$_categoryId',
          if (_subCategoryId != null) 'sub_category': '$_subCategoryId',
          if (_brandId != null) 'brand': '$_brandId',
          'short_description': _shortDescCtrl.text.trim(),
          'long_description': _longDescCtrl.text.trim(),
          'price': '$price',
          'offer_price': _offerCtrl.text.trim(),
          'quantity': '$qty',
          'status': '1',
          'sku': _skuCtrl.text.trim(),
          'tags': _tagsCtrl.text.trim(),
          'seo_title': _seoTitleCtrl.text.trim(),
          'seo_description': _seoDescCtrl.text.trim(),
        },
        thumbImagePath: _imagePath!,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Ürün oluşturuldu');
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, _kycAwareMessage(e));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final brands = _meta?.brands ?? const <SellerCatalogOption>[];
    final categories = _meta?.categories ?? const <SellerCatalogOption>[];

    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Detaylı Ürün Ekle'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
        actions: [
          TextButton(
            onPressed: (_aiLoading || _loadingMeta) ? null : _runAi,
            child: const Text('AI'),
          ),
        ],
      ),
      body: _loadingMeta
          ? const Center(child: CircularProgressIndicator())
          : _metaError != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_metaError!, textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: _loadMeta,
                          child: const Text('Tekrar Dene'),
                        ),
                      ],
                    ),
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Container(
                      decoration: HomeTheme.cardDecoration(),
                      clipBehavior: Clip.antiAlias,
                      child: Column(
                        children: [
                          SizedBox(
                            width: double.infinity,
                            height: 140,
                            child: _imagePath != null
                                ? Image.file(
                                    File(_imagePath!),
                                    fit: BoxFit.cover,
                                  )
                                : const Center(
                                    child: Icon(Icons.image_outlined, size: 40),
                                  ),
                          ),
                          TextButton.icon(
                            onPressed: _pickImage,
                            icon: const Icon(Icons.photo_library),
                            label: Text(
                              _imagePath == null
                                  ? 'Görsel Seç *'
                                  : 'Görseli Değiştir',
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    _field(_nameCtrl, 'Ürün adı *'),
                    _field(_shortNameCtrl, 'Kısa ad'),
                    _field(_slugCtrl, 'Slug'),
                    _field(_shortDescCtrl, 'Kısa açıklama *', maxLines: 3),
                    _field(_longDescCtrl, 'Uzun açıklama *', maxLines: 6),
                    Row(
                      children: [
                        Expanded(child: _field(_priceCtrl, 'Fiyat *')),
                        const SizedBox(width: 12),
                        Expanded(child: _field(_offerCtrl, 'İndirimli fiyat')),
                      ],
                    ),
                    _field(_qtyCtrl, 'Stok *', number: true),
                    DropdownButtonFormField<int?>(
                      value: _categoryId,
                      isExpanded: true,
                      decoration: const InputDecoration(
                        labelText: 'Kategori *',
                        border: OutlineInputBorder(),
                      ),
                      items: categories
                          .map(
                            (c) => DropdownMenuItem<int?>(
                              value: c.id,
                              child: Text(
                                c.name,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: _onCategoryChanged,
                    ),
                    if (_subs.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      DropdownButtonFormField<int?>(
                        value: _subCategoryId,
                        isExpanded: true,
                        decoration: const InputDecoration(
                          labelText: 'Alt kategori',
                          border: OutlineInputBorder(),
                        ),
                        items: [
                          const DropdownMenuItem<int?>(
                            value: null,
                            child: Text('Seçilmedi'),
                          ),
                          ..._subs.map(
                            (c) => DropdownMenuItem<int?>(
                              value: c.id,
                              child: Text(
                                c.name,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ),
                        ],
                        onChanged: (v) => setState(() => _subCategoryId = v),
                      ),
                    ],
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      value: _brandId,
                      isExpanded: true,
                      decoration: const InputDecoration(
                        labelText: 'Marka',
                        border: OutlineInputBorder(),
                      ),
                      items: [
                        const DropdownMenuItem<int?>(
                          value: null,
                          child: Text('Seçilmedi'),
                        ),
                        ...brands.map(
                          (b) => DropdownMenuItem<int?>(
                            value: b.id,
                            child: Text(
                              b.name,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ),
                      ],
                      onChanged: (v) => setState(() => _brandId = v),
                    ),
                    const SizedBox(height: 12),
                    _field(_skuCtrl, 'SKU'),
                    _field(_tagsCtrl, 'Etiketler'),
                    _field(_seoTitleCtrl, 'SEO başlık'),
                    _field(_seoDescCtrl, 'SEO açıklama', maxLines: 3),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: _submitting ? null : _submit,
                      style: FilledButton.styleFrom(
                        backgroundColor: HomeTheme.brandYellow,
                        foregroundColor: HomeTheme.textDark,
                        minimumSize: const Size.fromHeight(48),
                      ),
                      child: const Text(
                        'Ürünü Yayınla',
                        style: TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _field(
    TextEditingController ctrl,
    String label, {
    int maxLines = 1,
    bool number = false,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextField(
        controller: ctrl,
        maxLines: maxLines,
        keyboardType: number
            ? TextInputType.number
            : (maxLines > 1 ? TextInputType.multiline : TextInputType.text),
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
          alignLabelWithHint: maxLines > 1,
        ),
      ),
    );
  }
}
