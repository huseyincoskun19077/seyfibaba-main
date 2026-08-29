import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_catalog_option.dart';
import '../services/seller_api_service.dart';

class SellerEditProductScreen extends StatefulWidget {
  const SellerEditProductScreen({super.key, required this.productId});

  final int productId;

  @override
  State<SellerEditProductScreen> createState() =>
      _SellerEditProductScreenState();
}

class _SellerEditProductScreenState extends State<SellerEditProductScreen> {
  final _service = SellerApiService();
  final _nameCtrl = TextEditingController();
  final _shortNameCtrl = TextEditingController();
  final _slugCtrl = TextEditingController();
  final _shortDescCtrl = TextEditingController();
  final _longDescCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _offerCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController();
  final _skuCtrl = TextEditingController();
  final _tagsCtrl = TextEditingController();
  final _seoTitleCtrl = TextEditingController();
  final _seoDescCtrl = TextEditingController();

  bool _loading = true;
  bool _saving = false;
  bool _aiLoading = false;
  String? _error;
  String? _thumbUrl;
  String? _newImagePath;
  int? _categoryId;
  int? _subCategoryId;
  int? _brandId;
  List<SellerCatalogOption> _categories = const [];
  List<SellerCatalogOption> _brands = const [];
  List<SellerCatalogOption> _subs = const [];
  int _status = 1;

  @override
  void initState() {
    super.initState();
    _load();
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

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await _service.fetchProductEdit(_token, widget.productId);
      final product = data['product'];
      if (product is! Map) throw Exception('Ürün bulunamadı');
      final p = Map<String, dynamic>.from(product);

      _categories = _parseOptions(data['categories']);
      _brands = _parseOptions(data['brands']);
      _subs = _parseOptions(data['subCategories']);

      _nameCtrl.text = '${p['name'] ?? ''}';
      _shortNameCtrl.text = '${p['short_name'] ?? p['name'] ?? ''}';
      _slugCtrl.text = '${p['slug'] ?? ''}';
      _shortDescCtrl.text = '${p['short_description'] ?? ''}';
      _longDescCtrl.text = '${p['long_description'] ?? ''}';
      _priceCtrl.text = '${p['price'] ?? ''}';
      _offerCtrl.text = '${p['offer_price'] ?? ''}';
      _qtyCtrl.text = '${p['qty'] ?? ''}';
      _skuCtrl.text = '${p['sku'] ?? ''}';
      _tagsCtrl.text = '${p['tags'] ?? ''}';
      _seoTitleCtrl.text = '${p['seo_title'] ?? ''}';
      _seoDescCtrl.text = '${p['seo_description'] ?? ''}';
      _categoryId = int.tryParse('${p['category_id'] ?? ''}');
      _subCategoryId = int.tryParse('${p['sub_category_id'] ?? ''}');
      _brandId = int.tryParse('${p['brand_id'] ?? ''}');
      _status = int.tryParse('${p['status'] ?? 1}') ?? 1;
      _thumbUrl = '${p['thumb_image'] ?? ''}';

      if (!mounted) return;
      setState(() => _loading = false);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  List<SellerCatalogOption> _parseOptions(dynamic raw) {
    if (raw is! List) return const [];
    return raw
        .whereType<Map>()
        .map((e) => SellerCatalogOption.fromMap(Map<String, dynamic>.from(e)))
        .where((e) => e.id > 0)
        .toList();
  }

  Future<void> _onCategoryChanged(int? id) async {
    setState(() {
      _categoryId = id;
      _subCategoryId = null;
      _subs = const [];
    });
    if (id == null) return;
    final subs = await _service.fetchSubcategories(_token, id);
    if (!mounted) return;
    setState(() => _subs = subs);
  }

  Future<void> _pickImage() async {
    final file = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1600,
    );
    if (file == null) return;
    setState(() => _newImagePath = file.path);
  }

  Future<void> _runAi() async {
    final name = _nameCtrl.text.trim();
    if (name.isEmpty) {
      Utils.errorSnackBar(context, 'Önce ürün adı girin');
      return;
    }
    String? categoryName;
    for (final c in _categories) {
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

  Future<void> _save() async {
    if (_categoryId == null) {
      Utils.errorSnackBar(context, 'Kategori seçin');
      return;
    }
    final price = double.tryParse(_priceCtrl.text.trim().replaceAll(',', '.'));
    final qty = int.tryParse(_qtyCtrl.text.trim());
    if (_nameCtrl.text.trim().isEmpty ||
        _shortNameCtrl.text.trim().isEmpty ||
        _shortDescCtrl.text.trim().isEmpty ||
        _longDescCtrl.text.trim().isEmpty ||
        price == null ||
        qty == null) {
      Utils.errorSnackBar(context, 'Zorunlu alanları doldurun');
      return;
    }

    setState(() => _saving = true);
    Utils.loadingDialog(context);
    try {
      await _service.updateProduct(
        token: _token,
        productId: widget.productId,
        fields: {
          'short_name': _shortNameCtrl.text.trim(),
          'name': _nameCtrl.text.trim(),
          'slug': _slugCtrl.text.trim().isEmpty
              ? _nameCtrl.text.trim()
              : _slugCtrl.text.trim(),
          'category': '$_categoryId',
          if (_subCategoryId != null) 'sub_category': '$_subCategoryId',
          if (_brandId != null) 'brand': '$_brandId',
          'short_description': _shortDescCtrl.text.trim(),
          'long_description': _longDescCtrl.text.trim(),
          'price': '$price',
          'offer_price': _offerCtrl.text.trim(),
          'quantity': '$qty',
          'status': '$_status',
          'sku': _skuCtrl.text.trim(),
          'tags': _tagsCtrl.text.trim(),
          'seo_title': _seoTitleCtrl.text.trim(),
          'seo_description': _seoDescCtrl.text.trim(),
        },
        thumbImagePath: _newImagePath,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Ürün güncellendi');
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final imagePreview = _newImagePath != null
        ? Image.file(File(_newImagePath!), height: 140, fit: BoxFit.cover)
        : (_thumbUrl != null && _thumbUrl!.isNotEmpty
            ? Image.network(
                RemoteUrls.imageUrl(_thumbUrl!),
                height: 140,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => const SizedBox(
                  height: 140,
                  child: Center(child: Icon(Icons.broken_image_outlined)),
                ),
              )
            : const SizedBox(
                height: 140,
                child: Center(child: Icon(Icons.image_outlined)),
              ));

    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Ürün Düzenle'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
        actions: [
          TextButton(
            onPressed: (_aiLoading || _loading) ? null : _runAi,
            child: const Text('AI'),
          ),
          PopupMenuButton<String>(
            onSelected: (value) {
              if (value == 'gallery') {
                Navigator.pushNamed(
                  context,
                  RouteNames.sellerProductGalleryScreen,
                  arguments: widget.productId,
                );
              } else if (value == 'variants') {
                Navigator.pushNamed(
                  context,
                  RouteNames.sellerProductVariantsScreen,
                  arguments: widget.productId,
                );
              }
            },
            itemBuilder: (_) => const [
              PopupMenuItem(value: 'gallery', child: Text('Galeri')),
              PopupMenuItem(value: 'variants', child: Text('Varyantlar')),
            ],
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
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
                            child: imagePreview,
                          ),
                          TextButton.icon(
                            onPressed: _pickImage,
                            icon: const Icon(Icons.photo_library),
                            label: const Text('Görsel Değiştir'),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    _field(_nameCtrl, 'Ürün adı *'),
                    _field(_shortNameCtrl, 'Kısa ad *'),
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
                      items: _categories
                          .map(
                            (c) => DropdownMenuItem<int?>(
                              value: c.id,
                              child: Text(c.name, overflow: TextOverflow.ellipsis),
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
                              child: Text(c.name, overflow: TextOverflow.ellipsis),
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
                        ..._brands.map(
                          (b) => DropdownMenuItem<int?>(
                            value: b.id,
                            child: Text(b.name, overflow: TextOverflow.ellipsis),
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
                      onPressed: _saving ? null : _save,
                      style: FilledButton.styleFrom(
                        backgroundColor: HomeTheme.brandYellow,
                        foregroundColor: HomeTheme.textDark,
                        minimumSize: const Size.fromHeight(48),
                      ),
                      child: const Text(
                        'Kaydet',
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
