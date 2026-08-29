import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_catalog_option.dart';
import '../services/seller_api_service.dart';

class SellerQuickProductScreen extends StatefulWidget {
  const SellerQuickProductScreen({super.key});

  @override
  State<SellerQuickProductScreen> createState() =>
      _SellerQuickProductScreenState();
}

class _SellerQuickProductScreenState extends State<SellerQuickProductScreen> {
  static const _stepCount = 9;

  final _service = SellerApiService();
  final _nameCtrl = TextEditingController();
  final _shortDescCtrl = TextEditingController();
  final _longDescCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController(text: '1');
  final _packQtyCtrl = TextEditingController(text: '1');
  final _unitCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _offerCtrl = TextEditingController();
  final _brandNameCtrl = TextEditingController();
  final _skuCtrl = TextEditingController();
  final _weightCtrl = TextEditingController();
  final _seoTitleCtrl = TextEditingController();
  final _seoDescCtrl = TextEditingController();

  SellerProductCreateMeta? _meta;
  List<SellerCatalogOption> _subs = const [];
  List<SellerCatalogOption> _children = const [];
  int? _categoryId;
  int? _subCategoryId;
  int? _childCategoryId;
  int? _brandId;
  String? _imagePath;
  final List<String> _galleryPaths = [];
  final List<_ColorDraft> _colors = [];
  bool _loadingMeta = true;
  String? _metaError;
  bool _submitting = false;
  bool _aiLoading = false;
  bool _showLongDesc = false;
  int _step = 0;
  bool _syncingPrice = false;
  String _lastPriceField = 'total';

  @override
  void initState() {
    super.initState();
    _unitCtrl.addListener(_onUnitChanged);
    _priceCtrl.addListener(_onTotalChanged);
    _packQtyCtrl.addListener(_onPackQtyChanged);
    _shortDescCtrl.addListener(_onShortDescChanged);
    _loadMeta();
  }

  @override
  void dispose() {
    _unitCtrl.removeListener(_onUnitChanged);
    _priceCtrl.removeListener(_onTotalChanged);
    _packQtyCtrl.removeListener(_onPackQtyChanged);
    _shortDescCtrl.removeListener(_onShortDescChanged);
    _nameCtrl.dispose();
    _shortDescCtrl.dispose();
    _longDescCtrl.dispose();
    _qtyCtrl.dispose();
    _packQtyCtrl.dispose();
    _unitCtrl.dispose();
    _priceCtrl.dispose();
    _offerCtrl.dispose();
    _brandNameCtrl.dispose();
    _skuCtrl.dispose();
    _weightCtrl.dispose();
    _seoTitleCtrl.dispose();
    _seoDescCtrl.dispose();
    for (final color in _colors) {
      color.dispose();
    }
    super.dispose();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  int get _packQty {
    final n = int.tryParse(_packQtyCtrl.text.trim()) ?? 1;
    return n < 1 ? 1 : n;
  }

  double? _parseMoney(String raw) =>
      double.tryParse(raw.trim().replaceAll(',', '.'));

  void _onShortDescChanged() {
    if (!_showLongDesc && _shortDescCtrl.text.trim().isNotEmpty) {
      setState(() => _showLongDesc = true);
    }
  }

  void _onUnitChanged() {
    if (_syncingPrice) return;
    _lastPriceField = 'unit';
    _syncingPrice = true;
    final unit = _parseMoney(_unitCtrl.text) ?? 0;
    if (unit > 0) {
      _priceCtrl.text = (unit * _packQty).toStringAsFixed(2);
    }
    _syncingPrice = false;
    setState(() {});
  }

  void _onTotalChanged() {
    if (_syncingPrice) return;
    _lastPriceField = 'total';
    _syncingPrice = true;
    final total = _parseMoney(_priceCtrl.text) ?? 0;
    if (total > 0) {
      _unitCtrl.text = (total / _packQty).toStringAsFixed(2);
    }
    _syncingPrice = false;
    setState(() {});
  }

  void _onPackQtyChanged() {
    if (_syncingPrice) return;
    if (_lastPriceField == 'unit') {
      _onUnitChanged();
    } else {
      _onTotalChanged();
    }
  }

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
      _childCategoryId = null;
      _subs = const [];
      _children = const [];
    });
    if (id == null) return;
    try {
      final subs = await _service.fetchSubcategories(_token, id);
      if (!mounted) return;
      setState(() => _subs = subs);
    } catch (_) {}
  }

  Future<void> _onSubChanged(int? id) async {
    setState(() {
      _subCategoryId = id;
      _childCategoryId = null;
      _children = const [];
    });
    if (id == null) return;
    try {
      final children = await _service.fetchChildCategories(_token, id);
      if (!mounted) return;
      setState(() => _children = children);
    } catch (_) {}
  }

  Future<void> _pickImage(ImageSource source) async {
    final picker = ImagePicker();
    final file = await picker.pickImage(
      source: source,
      imageQuality: 85,
      maxWidth: 1600,
    );
    if (file == null) return;
    setState(() => _imagePath = file.path);
  }

  Future<void> _pickGallery() async {
    final picker = ImagePicker();
    final files = await picker.pickMultiImage(imageQuality: 85, maxWidth: 1600);
    if (files.isEmpty) return;
    setState(() {
      _galleryPaths.addAll(files.map((f) => f.path));
    });
  }

  Future<void> _pickColorImage(int index) async {
    final picker = ImagePicker();
    final file = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1600,
    );
    if (file == null) return;
    setState(() => _colors[index].imagePath = file.path);
  }

  Future<void> _fillAi() async {
    final name = _nameCtrl.text.trim();
    if (name.length < 2) {
      Utils.errorSnackBar(context, 'Önce ürün adını girin');
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
        if ('${r['short_description'] ?? ''}'.trim().isNotEmpty) {
          _shortDescCtrl.text = '${r['short_description']}';
        }
        if ('${r['long_description'] ?? ''}'.trim().isNotEmpty) {
          _longDescCtrl.text = '${r['long_description']}';
        }
        setState(() => _showLongDesc = true);
        Utils.showSnackBar(context, 'AI açıklama dolduruldu');
      } else {
        Utils.errorSnackBar(context, 'AI sonuç döndürmedi');
      }
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(
        context,
        'Açıklama şu an doldurulamadı. Kendiniz yazabilir veya daha sonra tekrar deneyebilirsiniz.',
      );
    } finally {
      if (mounted) setState(() => _aiLoading = false);
    }
  }

  bool _validateStep(int step) {
    if (step == 0 && _nameCtrl.text.trim().length < 2) {
      Utils.errorSnackBar(context, 'Ürün adı en az 2 karakter olmalı');
      return false;
    }
    if (step == 1 && (_imagePath == null || _imagePath!.isEmpty)) {
      Utils.errorSnackBar(context, 'Ürün fotoğrafı seçin');
      return false;
    }
    if (step == 3) {
      final price = _parseMoney(_priceCtrl.text);
      final qty = int.tryParse(_qtyCtrl.text.trim()) ?? 0;
      if (price == null || price < 0.01) {
        Utils.errorSnackBar(context, 'Toplam satış fiyatı girin');
        return false;
      }
      if (qty < 1) {
        Utils.errorSnackBar(context, 'Stok adedi girin');
        return false;
      }
    }
    if (step == 4 && _categoryId == null) {
      Utils.errorSnackBar(context, 'Ana kategori seçin');
      return false;
    }
    return true;
  }

  void _next() {
    if (!_validateStep(_step)) return;
    setState(() => _step = (_step + 1).clamp(0, _stepCount - 1));
  }

  void _back() {
    setState(() => _step = (_step - 1).clamp(0, _stepCount - 1));
  }

  void _skip() {
    setState(() => _step = (_step + 1).clamp(0, _stepCount - 1));
  }

  Future<void> _submit() async {
    if (!_validateStep(0) ||
        !_validateStep(1) ||
        !_validateStep(3) ||
        !_validateStep(4)) {
      return;
    }
    final name = _nameCtrl.text.trim();
    final qty = int.tryParse(_qtyCtrl.text.trim()) ?? 0;
    final price = _parseMoney(_priceCtrl.text);
    final offer = _parseMoney(_offerCtrl.text);

    setState(() => _submitting = true);
    Utils.loadingDialog(context);
    try {
      final result = await _service.quickCreateProduct(
        token: _token,
        name: name,
        quantity: qty,
        price: price!,
        offerPrice: offer,
        categoryId: _categoryId!,
        subCategoryId: _subCategoryId,
        childCategoryId: _childCategoryId,
        brandId: _brandId,
        brandName: _brandNameCtrl.text,
        shortDescription: _shortDescCtrl.text,
        longDescription: _longDescCtrl.text,
        sku: _skuCtrl.text,
        weight: _weightCtrl.text,
        seoTitle: _seoTitleCtrl.text,
        seoDescription: _seoDescCtrl.text,
        saleUnitQty: _packQty,
        thumbImagePath: _imagePath!,
        galleryImagePaths: List<String>.from(_galleryPaths),
        colors: _colors
            .where((c) => c.nameCtrl.text.trim().isNotEmpty)
            .map(
              (c) => {
                'name': c.nameCtrl.text.trim(),
                'price': c.priceCtrl.text.trim(),
                'qty': c.qtyCtrl.text.trim(),
                if (c.imagePath != null) 'image': c.imagePath!,
              },
            )
            .toList(),
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        '${result['message'] ?? 'Ürün yayına alındı.'}',
      );
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  String _optionName(List<SellerCatalogOption> list, int? id) {
    if (id == null) return '—';
    for (final item in list) {
      if (item.id == id) return item.name;
    }
    return '—';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Hızlı Ürün Ekle'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
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
              : Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                      child: Column(
                        children: [
                          Align(
                            alignment: Alignment.centerRight,
                            child: Text(
                              '${_step + 1} / $_stepCount',
                              style: const TextStyle(
                                color: HomeTheme.textMuted,
                                fontSize: 12,
                              ),
                            ),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: List.generate(_stepCount, (i) {
                              return Expanded(
                                child: Container(
                                  height: 6,
                                  margin: EdgeInsets.only(
                                    right: i == _stepCount - 1 ? 0 : 4,
                                  ),
                                  decoration: BoxDecoration(
                                    color: i <= _step
                                        ? const Color(0xFF6366F1)
                                        : const Color(0xFFE2E8F0),
                                    borderRadius: BorderRadius.circular(99),
                                  ),
                                ),
                              );
                            }),
                          ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: ListView(
                        padding: const EdgeInsets.all(16),
                        children: [_stepBody()],
                      ),
                    ),
                    _navBar(),
                  ],
                ),
    );
  }

  Widget _card({required String title, required String hint, required List<Widget> children}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: HomeTheme.cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: HomeTheme.textDark,
            ),
          ),
          const SizedBox(height: 6),
          Text(hint, style: const TextStyle(color: HomeTheme.textMuted, height: 1.4)),
          const SizedBox(height: 16),
          ...children,
        ],
      ),
    );
  }

  Widget _stepBody() {
    switch (_step) {
      case 0:
        return _card(
          title: 'Ürün adı',
          hint: 'Adı yazın, sonraki alanlar sırayla açılır.',
          children: [
            TextField(
              controller: _nameCtrl,
              textInputAction: TextInputAction.next,
              onSubmitted: (_) => _next(),
              decoration: const InputDecoration(
                labelText: 'Ürün adı *',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        );
      case 1:
        return _card(
          title: 'Kapak fotoğrafı',
          hint: 'Bu görsel ürün kartında görünür.',
          children: [
            _ImagePickerBox(
              path: _imagePath,
              onCamera: () => _pickImage(ImageSource.camera),
              onGallery: () => _pickImage(ImageSource.gallery),
            ),
          ],
        );
      case 2:
        return _card(
          title: 'Açıklama',
          hint: 'Kısa yazıyı girin, detay alanı ardından açılır. Boş bırakırsanız AI tamamlar.',
          children: [
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: _aiLoading ? null : _fillAi,
                icon: const Icon(Icons.auto_awesome, size: 18),
                label: const Text('AI ile doldur'),
              ),
            ),
            TextField(
              controller: _shortDescCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Kısa açıklama',
                border: OutlineInputBorder(),
              ),
            ),
            if (_showLongDesc) ...[
              const SizedBox(height: 12),
              TextField(
                controller: _longDescCtrl,
                maxLines: 6,
                decoration: const InputDecoration(
                  labelText: 'Detaylı açıklama',
                  border: OutlineInputBorder(),
                ),
              ),
            ],
          ],
        );
      case 3:
        final unit = _parseMoney(_unitCtrl.text) ?? 0;
        final total = _parseMoney(_priceCtrl.text) ?? 0;
        return _card(
          title: 'Fiyat',
          hint: 'Birim fiyat yazınca toplam, toplam yazınca birim görünür.',
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              margin: const EdgeInsets.only(bottom: 12),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFF59E0B)),
              ),
              child: const Text(
                'Kargo sizin üzerinizde: Müşteri kargo ücreti ödemez. Kargo bedelini siz ödersiniz; fiyatınızı buna göre yazın.',
                style: TextStyle(height: 1.4, fontWeight: FontWeight.w600),
              ),
            ),
            TextField(
              controller: _packQtyCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Paketteki ürün adedi',
                helperText: 'Tek ürünse 1, 5’li set ise 5 yazın.',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _unitCtrl,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Birim fiyat (₺ / adet)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _priceCtrl,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Toplam satış fiyatı (₺) *',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _offerCtrl,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'İndirimli fiyat (₺)',
                helperText: 'Varsa toplam fiyattan düşük yazın. Yoksa boş bırakın.',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _qtyCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Stok (kaç paket) *',
                border: OutlineInputBorder(),
              ),
            ),
            if (unit > 0 || total > 0) ...[
              const SizedBox(height: 12),
              Text(
                '$_packQty adet paket · birim ${_money(unit > 0 ? unit : total / _packQty)} ₺ · toplam ${_money(total > 0 ? total : unit * _packQty)} ₺',
                style: const TextStyle(color: HomeTheme.textMuted, height: 1.4),
              ),
              const SizedBox(height: 8),
              Text(
                'Kargo hariç net kalan: ${_money((total > 0 ? total : unit * _packQty) * 0.9)} ₺ (platform %10)',
                style: const TextStyle(
                  color: HomeTheme.textDark,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ],
        );
      case 4:
        return _card(
          title: 'Kategori',
          hint: 'Ana kategoriyi seçin; alt kategoriler sırayla gelir.',
          children: [
            DropdownButtonFormField<int?>(
              value: _categoryId,
              isExpanded: true,
              decoration: const InputDecoration(
                labelText: 'Ana kategori *',
                border: OutlineInputBorder(),
              ),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('Kategori seçin'),
                ),
                ...(_meta?.categories ?? const []).map(
                  (c) => DropdownMenuItem<int?>(
                    value: c.id,
                    child: Text(c.name, overflow: TextOverflow.ellipsis),
                  ),
                ),
              ],
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
                onChanged: _onSubChanged,
              ),
            ],
            if (_children.isNotEmpty) ...[
              const SizedBox(height: 12),
              DropdownButtonFormField<int?>(
                value: _childCategoryId,
                isExpanded: true,
                decoration: const InputDecoration(
                  labelText: 'Alt alt kategori',
                  border: OutlineInputBorder(),
                ),
                items: [
                  const DropdownMenuItem<int?>(
                    value: null,
                    child: Text('Seçilmedi'),
                  ),
                  ..._children.map(
                    (c) => DropdownMenuItem<int?>(
                      value: c.id,
                      child: Text(c.name, overflow: TextOverflow.ellipsis),
                    ),
                  ),
                ],
                onChanged: (v) => setState(() => _childCategoryId = v),
              ),
            ],
          ],
        );
      case 5:
        return _card(
          title: 'Marka',
          hint: 'Listeden seçin veya yoksa yeni marka yazın. Atlayabilirsiniz.',
          children: [
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
                ...(_meta?.brands ?? const []).map(
                  (b) => DropdownMenuItem<int?>(
                    value: b.id,
                    child: Text(b.name, overflow: TextOverflow.ellipsis),
                  ),
                ),
              ],
              onChanged: (v) {
                setState(() => _brandId = v);
                if (v != null) _brandNameCtrl.clear();
              },
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _brandNameCtrl,
              onChanged: (v) {
                if (v.trim().isNotEmpty && _brandId != null) {
                  setState(() => _brandId = null);
                }
              },
              decoration: const InputDecoration(
                labelText: 'Yeni marka adı',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        );
      case 6:
        return _card(
          title: 'Ek bilgiler',
          hint: 'SKU, ağırlık ve SEO isteğe bağlıdır. Atlayabilirsiniz.',
          children: [
            TextField(
              controller: _skuCtrl,
              decoration: const InputDecoration(
                labelText: 'SKU',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _weightCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Ağırlık (g)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _seoTitleCtrl,
              decoration: const InputDecoration(
                labelText: 'SEO başlığı',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _seoDescCtrl,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'SEO açıklaması',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        );
      case 7:
        return _card(
          title: 'Renk ve ek görseller',
          hint: 'Renk yoksa atlayın. En sonda ek fotoğraf da ekleyebilirsiniz.',
          children: [
            ...List.generate(_colors.length, (index) {
              final color = _colors[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    border: Border.all(color: HomeTheme.border),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    children: [
                      TextField(
                        controller: color.nameCtrl,
                        decoration: const InputDecoration(
                          labelText: 'Renk adı',
                          border: OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: color.priceCtrl,
                              keyboardType: const TextInputType.numberWithOptions(
                                decimal: true,
                              ),
                              decoration: const InputDecoration(
                                labelText: 'Fiyat',
                                border: OutlineInputBorder(),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: TextField(
                              controller: color.qtyCtrl,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(
                                labelText: 'Adet',
                                border: OutlineInputBorder(),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          TextButton.icon(
                            onPressed: () => _pickColorImage(index),
                            icon: const Icon(Icons.photo),
                            label: Text(
                              color.imagePath == null ? 'Renk fotoğrafı' : 'Fotoğraf seçildi',
                            ),
                          ),
                          const Spacer(),
                          TextButton(
                            onPressed: () {
                              setState(() {
                                _colors.removeAt(index).dispose();
                              });
                            },
                            child: const Text('Sil'),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            }),
            OutlinedButton.icon(
              onPressed: () => setState(() => _colors.add(_ColorDraft())),
              icon: const Icon(Icons.add),
              label: const Text('Renk ekle'),
            ),
            const SizedBox(height: 16),
            const Text(
              'Ek görseller',
              style: TextStyle(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                ..._galleryPaths.map(
                  (path) => ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.file(File(path), width: 72, height: 72, fit: BoxFit.cover),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: _pickGallery,
              icon: const Icon(Icons.add_photo_alternate_outlined),
              label: const Text('Ek fotoğraf ekle'),
            ),
          ],
        );
      default:
        return _previewCard();
    }
  }

  Widget _previewCard() {
    final total = _parseMoney(_priceCtrl.text) ?? 0;
    final unit = _packQty > 0 ? total / _packQty : total;
    final offer = _parseMoney(_offerCtrl.text);
    return _card(
      title: 'Önizleme',
      hint: 'Yayınlamadan önce kontrol edin. Geri dönüp düzeltebilirsiniz.',
      children: [
        if (_imagePath != null)
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.file(
              File(_imagePath!),
              height: 180,
              width: double.infinity,
              fit: BoxFit.cover,
            ),
          ),
        if (_imagePath != null) const SizedBox(height: 12),
        _previewRow('Ad', _nameCtrl.text.trim()),
        _previewRow(
          'Kısa açıklama',
          _shortDescCtrl.text.trim().isEmpty
              ? 'AI tamamlayacak'
              : _shortDescCtrl.text.trim(),
        ),
        _previewRow('Paket', '$_packQty adet'),
        _previewRow('Birim fiyat', '${_money(unit)} ₺'),
        _previewRow('Toplam fiyat', '${_money(total)} ₺'),
        _previewRow('Stok', '${_qtyCtrl.text.trim()} paket'),
        _previewRow('Kategori', _optionName(_meta?.categories ?? const [], _categoryId)),
        if (_subCategoryId != null)
          _previewRow('Alt kategori', _optionName(_subs, _subCategoryId)),
        if (_childCategoryId != null)
          _previewRow('Alt alt kategori', _optionName(_children, _childCategoryId)),
        _previewRow(
          'Marka',
          _brandNameCtrl.text.trim().isNotEmpty
              ? _brandNameCtrl.text.trim()
              : _optionName(_meta?.brands ?? const [], _brandId),
        ),
        _previewRow(
          'İndirimli fiyat',
          offer != null && offer > 0 ? '${_money(offer)} ₺' : 'Yok',
        ),
        if (_colors.any((c) => c.nameCtrl.text.trim().isNotEmpty))
          _previewRow(
            'Renkler',
            _colors
                .where((c) => c.nameCtrl.text.trim().isNotEmpty)
                .map((c) => c.nameCtrl.text.trim())
                .join(', '),
          ),
        if (_galleryPaths.isNotEmpty)
          _previewRow('Ek görsel', '${_galleryPaths.length} adet'),
      ],
    );
  }

  Widget _previewRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(label, style: const TextStyle(color: HomeTheme.textMuted)),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '—' : value,
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );
  }

  Widget _navBar() {
    final last = _step == _stepCount - 1;
    final canSkip = _step == 2 || _step == 5 || _step == 6 || _step == 7;
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
        ),
        child: Row(
          children: [
            if (_step > 0)
              Expanded(
                child: OutlinedButton(
                  onPressed: _back,
                  child: const Text('Geri'),
                ),
              ),
            if (_step > 0) const SizedBox(width: 8),
            if (canSkip && !last)
              Expanded(
                child: OutlinedButton(
                  onPressed: _skip,
                  child: const Text('Atla'),
                ),
              ),
            if (canSkip && !last) const SizedBox(width: 8),
            Expanded(
              flex: 2,
              child: FilledButton(
                onPressed: last
                    ? (_submitting ? null : _submit)
                    : _next,
                style: FilledButton.styleFrom(
                  backgroundColor: HomeTheme.brandYellow,
                  foregroundColor: HomeTheme.textDark,
                  minimumSize: const Size.fromHeight(48),
                ),
                child: Text(
                  last ? 'Ürünü yayınla' : 'Devam',
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _money(double n) => n.toStringAsFixed(2).replaceAll('.', ',');
}

class _ColorDraft {
  _ColorDraft()
      : nameCtrl = TextEditingController(),
        priceCtrl = TextEditingController(),
        qtyCtrl = TextEditingController();

  final TextEditingController nameCtrl;
  final TextEditingController priceCtrl;
  final TextEditingController qtyCtrl;
  String? imagePath;

  void dispose() {
    nameCtrl.dispose();
    priceCtrl.dispose();
    qtyCtrl.dispose();
  }
}

class _ImagePickerBox extends StatelessWidget {
  const _ImagePickerBox({
    required this.path,
    required this.onCamera,
    required this.onGallery,
  });

  final String? path;
  final VoidCallback onCamera;
  final VoidCallback onGallery;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 180,
      decoration: HomeTheme.cardDecoration(),
      clipBehavior: Clip.antiAlias,
      child: path == null
          ? Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.add_a_photo_outlined, size: 36),
                const SizedBox(height: 8),
                const Text('Ürün fotoğrafı *'),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    TextButton.icon(
                      onPressed: onCamera,
                      icon: const Icon(Icons.photo_camera),
                      label: const Text('Kamera'),
                    ),
                    TextButton.icon(
                      onPressed: onGallery,
                      icon: const Icon(Icons.photo_library),
                      label: const Text('Galeri'),
                    ),
                  ],
                ),
              ],
            )
          : Stack(
              fit: StackFit.expand,
              children: [
                Image.file(File(path!), fit: BoxFit.cover),
                Positioned(
                  right: 8,
                  bottom: 8,
                  child: Row(
                    children: [
                      _MiniBtn(icon: Icons.photo_camera, onTap: onCamera),
                      const SizedBox(width: 8),
                      _MiniBtn(icon: Icons.photo_library, onTap: onGallery),
                    ],
                  ),
                ),
              ],
            ),
    );
  }
}

class _MiniBtn extends StatelessWidget {
  const _MiniBtn({required this.icon, required this.onTap});
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.black54,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Padding(
          padding: const EdgeInsets.all(8),
          child: Icon(icon, color: Colors.white, size: 18),
        ),
      ),
    );
  }
}
