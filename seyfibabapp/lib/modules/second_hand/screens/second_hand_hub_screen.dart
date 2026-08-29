import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../../../utils/language_string.dart';
import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../models/second_hand_models.dart';
import '../services/second_hand_service.dart';
import '../widgets/second_hand_ui.dart';
import '../widgets/turkey_address_selects.dart';

class SecondHandVerificationTab extends StatefulWidget {
  const SecondHandVerificationTab({
    super.key,
    required this.service,
    required this.token,
    required this.errorMessage,
    required this.onApproved,
  });

  final SecondHandService service;
  final String token;
  final String Function(Object) errorMessage;
  final VoidCallback onApproved;

  @override
  State<SecondHandVerificationTab> createState() =>
      SecondHandVerificationTabState();
}

class SecondHandVerificationTabState extends State<SecondHandVerificationTab> {
  final _businessName = TextEditingController();
  final _taxNumber = TextEditingController();
  final _registryNumber = TextEditingController();

  SecondHandVerification? _verification;
  Map<String, String> _agreements = {};
  bool _loading = true;
  bool _submitting = false;
  bool _acceptTerms = false;
  bool _acceptPrivacy = false;
  String? _taxDocPath;
  String? _barberDocPath;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _businessName.dispose();
    _taxNumber.dispose();
    _registryNumber.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final results = await Future.wait([
        widget.service.fetchVerification(widget.token),
        widget.service.fetchAgreements(),
      ]);
      if (!mounted) return;
      final verification = results[0] as SecondHandVerification;
      final agreements = results[1] as Map<String, String>;
      setState(() {
        _verification = verification;
        _agreements = agreements;
        _businessName.text = verification.businessName ?? '';
        _taxNumber.text = verification.taxNumber ?? '';
        _registryNumber.text = verification.barberRegistryNumber ?? '';
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  Future<void> _submit() async {
    if (_verification?.isApproved == true) {
      widget.onApproved();
      return;
    }
    if (_businessName.text.trim().isEmpty ||
        _taxNumber.text.trim().isEmpty ||
        _taxDocPath == null) {
      Utils.errorSnackBar(
          context, 'İşletme adı, vergi no ve vergi belgesi zorunludur.');
      return;
    }
    if (_registryNumber.text.trim().isEmpty && _barberDocPath == null) {
      Utils.errorSnackBar(context,
          'Kuaförler Odası sicil no veya evrak yüklemeniz gerekiyor.');
      return;
    }
    if (!_acceptTerms || !_acceptPrivacy) {
      Utils.errorSnackBar(context, 'Sözleşmeleri onaylamanız gerekiyor.');
      return;
    }

    setState(() => _submitting = true);
    try {
      final msg = await widget.service.submitVerification(
        token: widget.token,
        businessName: _businessName.text.trim(),
        taxNumber: _taxNumber.text.trim(),
        barberRegistryNumber: _registryNumber.text.trim(),
        taxDocumentPath: _taxDocPath!,
        barberDocumentPath: _barberDocPath,
        acceptTerms: _acceptTerms,
        acceptPrivacy: _acceptPrivacy,
      );
      if (!mounted) return;
      setState(() => _submitting = false);
      Utils.showSnackBar(context, msg);
      await _load();
    } catch (e) {
      if (!mounted) return;
      setState(() => _submitting = false);
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  void _showAgreement(String title, String content) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: SingleChildScrollView(
          child: Text(content.isNotEmpty ? content : 'İçerik yüklenemedi.'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(Language.dismiss),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const ShLoading();

    final verification = _verification;
    if (verification?.isApproved == true) {
      return ShEmptyState(
        icon: Icons.verified_rounded,
        title: 'Hesabınız onaylı',
        subtitle: verification?.businessName ?? 'İlan verebilirsiniz.',
        action: ShPrimaryButton(
          label: 'İlan Ekle',
          icon: Icons.add_rounded,
          onPressed: widget.onApproved,
        ),
      );
    }

    if (verification?.isPending == true) {
      return ShEmptyState(
        icon: Icons.hourglass_top_rounded,
        title: 'Başvurunuz inceleniyor',
        subtitle: verification?.businessName != null
            ? '${verification!.businessName}\nOnay sonrası bilgilendirileceksiniz.'
            : 'Kısa süre içinde değerlendirilecek.',
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (verification?.isRejected == true) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: ShTheme.danger.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(ShTheme.radiusSm),
                border: Border.all(color: ShTheme.danger.withValues(alpha: 0.2)),
              ),
              child: Text(
                verification?.adminNote?.isNotEmpty == true
                    ? 'Red nedeni: ${verification!.adminNote}'
                    : 'Başvurunuz reddedildi. Bilgileri güncelleyip tekrar gönderebilirsiniz.',
                style: const TextStyle(fontSize: 13, height: 1.4, color: ShTheme.dark),
              ),
            ),
            const SizedBox(height: 16),
          ],
          const ShSectionTitle(
            title: 'Kuaför doğrulaması',
            subtitle: 'İkinci el alışverişi için işletme bilgilerinizi doğrulayın.',
          ),
          ShTextField(
            controller: _businessName,
            label: 'İşletme adı',
            required: true,
          ),
          const SizedBox(height: 14),
          ShTextField(
            controller: _taxNumber,
            label: 'Vergi numarası',
            required: true,
            keyboardType: TextInputType.number,
          ),
          const SizedBox(height: 14),
          ShTextField(
            controller: _registryNumber,
            label: 'Kuaförler Odası sicil no',
            hint: 'Sicil no veya evrak yükleyin',
          ),
          const SizedBox(height: 14),
          ShUploadTile(
            label: 'Vergi belgesi',
            selected: _taxDocPath != null,
            onPick: () async {
              final path = await Utils.pickSingleImage();
              if (path != null) setState(() => _taxDocPath = path);
            },
          ),
          const SizedBox(height: 10),
          ShUploadTile(
            label: 'Kuaförler Odası evrakı',
            selected: _barberDocPath != null,
            icon: Icons.badge_outlined,
            onPick: () async {
              final path = await Utils.pickSingleImage();
              if (path != null) setState(() => _barberDocPath = path);
            },
          ),
          const SizedBox(height: 16),
          _AgreementCheck(
            value: _acceptTerms,
            label: 'Kullanım koşullarını kabul ediyorum',
            onChanged: (v) => setState(() => _acceptTerms = v),
            onRead: () => _showAgreement(
              _agreements['terms_title'] ?? 'Kullanım Koşulları',
              _agreements['terms_content'] ?? '',
            ),
          ),
          _AgreementCheck(
            value: _acceptPrivacy,
            label: 'KVKK / gizlilik metnini kabul ediyorum',
            onChanged: (v) => setState(() => _acceptPrivacy = v),
            onRead: () => _showAgreement(
              _agreements['privacy_title'] ?? 'Gizlilik',
              _agreements['privacy_content'] ?? '',
            ),
          ),
          const SizedBox(height: 20),
          ShPrimaryButton(
            label: 'Başvuruyu Gönder',
            icon: Icons.send_rounded,
            loading: _submitting,
            onPressed: _submitting ? null : _submit,
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}

class _AgreementCheck extends StatelessWidget {
  const _AgreementCheck({
    required this.value,
    required this.label,
    required this.onChanged,
    required this.onRead,
  });

  final bool value;
  final String label;
  final ValueChanged<bool> onChanged;
  final VoidCallback onRead;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 24,
            height: 24,
            child: Checkbox(
              value: value,
              activeColor: ShTheme.primary,
              onChanged: (v) => onChanged(v ?? false),
              materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: GestureDetector(
              onTap: onRead,
              child: Text(
                label,
                style: const TextStyle(fontSize: 13, height: 1.35, color: ShTheme.dark),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class SecondHandAddListingTab extends StatefulWidget {
  const SecondHandAddListingTab({
    super.key,
    required this.service,
    required this.token,
    required this.errorMessage,
    required this.onPublished,
  });

  final SecondHandService service;
  final String token;
  final String Function(Object) errorMessage;
  final VoidCallback onPublished;

  @override
  State<SecondHandAddListingTab> createState() =>
      SecondHandAddListingTabState();
}

class SecondHandAddListingTabState extends State<SecondHandAddListingTab> {
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _price = TextEditingController();

  List<Map<String, dynamic>> _categories = [];
  List<Map<String, dynamic>> _subCategories = [];
  List<Map<String, dynamic>> _childCategories = [];

  String? _categoryId;
  String? _subCategoryId;
  String? _childCategoryId;
  String _condition = 'used';
  TurkeyAddressValue _address = const TurkeyAddressValue();

  SecondHandListing? _draft;
  bool _loadingCategories = true;
  bool _saving = false;
  bool _acceptUploadTerms = false;
  String _listingRulesTitle = 'İkinci El İlan Kuralları';
  String _listingRulesContent = '';

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    await Future.wait([
      _loadCategories(),
      _loadListingRules(),
    ]);
  }

  Future<void> _loadListingRules() async {
    try {
      final rules = await widget.service.fetchListingRules();
      if (!mounted) return;
      setState(() {
        _listingRulesTitle = rules['title']?.isNotEmpty == true
            ? rules['title']!
            : 'İkinci El İlan Kuralları';
        _listingRulesContent = rules['content'] ?? '';
      });
    } catch (_) {}
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _price.dispose();
    super.dispose();
  }

  Future<void> _loadCategories() async {
    try {
      final categories = await widget.service.fetchCategories();
      if (!mounted) return;
      setState(() {
        _categories = withoutCosmeticSecondHandCategories(categories);
        _loadingCategories = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loadingCategories = false);
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  Future<void> _onCategoryChanged(String? v) async {
    final id = (v == null || v.isEmpty) ? null : v;
    Map<String, dynamic> selected = {};
    for (final c in _categories) {
      if ('${c['id']}' == id) {
        selected = c;
        break;
      }
    }
    var subs = SecondHandService.nestedList(selected, const [
      'active_sub_categories',
      'activeSubCategories',
      'sub_categories',
      'subCategories',
    ]);
    setState(() {
      _categoryId = id;
      _subCategoryId = null;
      _childCategoryId = null;
      _subCategories = subs;
      _childCategories = [];
    });
    if (id != null && subs.isEmpty) {
      await _loadSubCategories(id);
    }
  }

  Future<void> _onSubCategoryChanged(String? v) async {
    final id = (v == null || v.isEmpty) ? null : v;
    Map<String, dynamic> selected = {};
    for (final s in _subCategories) {
      if ('${s['id']}' == id) {
        selected = s;
        break;
      }
    }
    var children = SecondHandService.nestedList(selected, const [
      'active_child_categories',
      'activeChildCategories',
      'child_categories',
      'childCategories',
    ]);
    setState(() {
      _subCategoryId = id;
      _childCategoryId = null;
      _childCategories = children;
    });
    if (id != null && children.isEmpty) {
      await _loadChildCategories(id);
    }
  }

  Future<void> _loadSubCategories(String categoryId) async {
    try {
      final subs = await widget.service.fetchSubCategories(categoryId);
      if (!mounted) return;
      setState(() {
        _subCategories = subs;
        _childCategories = [];
        _subCategoryId = null;
        _childCategoryId = null;
      });
    } catch (e) {
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  Future<void> _loadChildCategories(String subCategoryId) async {
    try {
      final children = await widget.service.fetchChildCategories(subCategoryId);
      if (!mounted) return;
      setState(() {
        _childCategories = children;
        _childCategoryId = null;
      });
    } catch (e) {
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  Map<String, dynamic> _formBody() {
    return {
      'title': _title.text.trim(),
      'description': _description.text.trim(),
      'price': num.tryParse(_price.text.trim()) ?? 0,
      'condition': _condition,
      if (_categoryId != null) 'category_id': int.parse(_categoryId!),
      if (_subCategoryId != null) 'sub_category_id': int.parse(_subCategoryId!),
      if (_childCategoryId != null)
        'child_category_id': int.parse(_childCategoryId!),
      if (_address.province.trim().isNotEmpty) 'province': _address.province.trim(),
      if (_address.district.trim().isNotEmpty) 'district': _address.district.trim(),
      if (_address.locality.trim().isNotEmpty) 'locality': _address.locality.trim(),
      if (_address.neighborhood.trim().isNotEmpty)
        'neighborhood': _address.neighborhood.trim(),
    };
  }

  Future<void> _saveDraft() async {
    if (!_acceptUploadTerms) {
      Utils.errorSnackBar(
        context,
        'İlan yüklemek için İkinci El İlan Kuralları’nı kabul etmelisiniz.',
      );
      return;
    }
    if (_title.text.trim().isEmpty || _price.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Başlık ve fiyat zorunludur.');
      return;
    }
    setState(() => _saving = true);
    try {
      SecondHandListing listing;
      if (_draft != null) {
        listing = await widget.service.updateDraft(
          token: widget.token,
          id: _draft!.id,
          body: _formBody(),
        );
      } else {
        listing = await widget.service.createDraft(
          token: widget.token,
          body: _formBody(),
        );
      }
      if (!mounted) return;
      setState(() {
        _draft = listing;
        _saving = false;
      });
      Utils.showSnackBar(context, 'Taslak kaydedildi.');
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  Future<void> _publish() async {
    if (!_acceptUploadTerms) {
      Utils.errorSnackBar(
        context,
        'İlan yüklemek için İkinci El İlan Kuralları’nı kabul etmelisiniz.',
      );
      return;
    }

    if (_draft == null) {
      await _saveDraft();
      if (_draft == null) return;
    }
    setState(() => _saving = true);
    try {
      final msg = await widget.service.publishListing(
        token: widget.token,
        id: _draft!.id,
      );
      if (!mounted) return;
      setState(() => _saving = false);
      Utils.showSnackBar(context, msg);
      widget.onPublished();
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  Future<void> _addImage() async {
    if (!_acceptUploadTerms) {
      Utils.errorSnackBar(
        context,
        'İlan yüklemek için İkinci El İlan Kuralları’nı kabul etmelisiniz.',
      );
      return;
    }
    if (_draft == null) {
      Utils.errorSnackBar(context, 'Önce taslağı kaydedin.');
      return;
    }
    if (_draft!.images.length >= 6) {
      Utils.errorSnackBar(context, 'En fazla 6 fotoğraf yükleyebilirsiniz.');
      return;
    }
    final path = await Utils.pickSingleImage();
    if (path == null) return;
    setState(() => _saving = true);
    try {
      final listing = await widget.service.uploadListingImage(
        token: widget.token,
        listingId: _draft!.id,
        filePath: path,
      );
      if (!mounted) return;
      setState(() {
        _draft = listing;
        _saving = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  Future<void> _deleteImage(int imageId) async {
    if (_draft == null) return;
    setState(() => _saving = true);
    try {
      final listing = await widget.service.deleteListingImage(
        token: widget.token,
        listingId: _draft!.id,
        imageId: imageId,
      );
      if (!mounted) return;
      setState(() {
        _draft = listing;
        _saving = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loadingCategories) return const ShLoading();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const ShSectionTitle(
            title: 'Yeni ilan',
            subtitle: 'Başlık ve fiyat zorunlu. Önce kaydedin, sonra fotoğraf ekleyin.',
          ),
          ShTextField(controller: _title, label: 'Başlık', required: true),
          const SizedBox(height: 14),
          ShTextField(
            controller: _description,
            label: 'Açıklama',
            maxLines: 4,
            hint: 'Ürünün durumunu kısaca anlatın',
          ),
          const SizedBox(height: 14),
          ShTextField(
            controller: _price,
            label: 'Fiyat (₺)',
            required: true,
            keyboardType: TextInputType.number,
          ),
          const SizedBox(height: 14),
          ShDropdownField<String>(
            label: 'Ürün durumu',
            value: _condition,
            items: secondHandConditionLabels.entries
                .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                .toList(),
            onChanged: (v) {
              if (v != null) setState(() => _condition = v);
            },
          ),
          const SizedBox(height: 14),
          ShDropdownField<String>(
            label: 'Kategori',
            value: _categoryId ?? '',
            hint: 'Kategori seçin',
            items: [
              const DropdownMenuItem(value: '', child: Text('Seçin')),
              ..._categories.map(
                (c) => DropdownMenuItem(
                  value: '${c['id']}',
                  child: Text('${c['name']}'),
                ),
              ),
            ],
            onChanged: _onCategoryChanged,
          ),
          if (_subCategories.isNotEmpty) ...[
            const SizedBox(height: 14),
            ShDropdownField<String>(
              label: 'Alt kategori',
              value: _subCategoryId ?? '',
              hint: 'Alt kategori seçin',
              items: [
                const DropdownMenuItem(value: '', child: Text('Seçin')),
                ..._subCategories.map(
                  (c) => DropdownMenuItem(
                    value: '${c['id']}',
                    child: Text('${c['name']}'),
                  ),
                ),
              ],
              onChanged: _onSubCategoryChanged,
            ),
          ],
          if (_childCategories.isNotEmpty) ...[
            const SizedBox(height: 14),
            ShDropdownField<String>(
              label: 'Alt-alt kategori',
              value: _childCategoryId ?? '',
              hint: 'Alt-alt kategori seçin',
              items: [
                const DropdownMenuItem(value: '', child: Text('Seçin')),
                ..._childCategories.map(
                  (c) => DropdownMenuItem(
                    value: '${c['id']}',
                    child: Text('${c['name']}'),
                  ),
                ),
              ],
              onChanged: (v) => setState(() {
                _childCategoryId = (v == null || v.isEmpty) ? null : v;
              }),
            ),
          ],
          const SizedBox(height: 20),
          const ShSectionTitle(
            title: 'Konum',
            subtitle: 'İl, ilçe ve mahalle listeden seçilir.',
          ),
          TurkeyAddressSelects(
            value: _address,
            onChanged: (v) => setState(() => _address = v),
          ),
          if (_draft != null) ...[
            const SizedBox(height: 20),
            const ShSectionTitle(
              title: 'Fotoğraflar',
              subtitle: 'En fazla 6 fotoğraf ekleyebilirsiniz.',
            ),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                ..._draft!.images.map(
                  (img) => Stack(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(ShTheme.radiusSm),
                        child: ColoredBox(
                          color: const Color(0xFFF0F0F3),
                          child: CachedNetworkImage(
                            width: 84,
                            height: 84,
                            fit: BoxFit.contain,
                            imageUrl: SecondHandService.resolveListingImageUrl(img),
                          ),
                        ),
                      ),
                      Positioned(
                        top: 2,
                        right: 2,
                        child: GestureDetector(
                          onTap: () => _deleteImage(img.id),
                          child: Container(
                            padding: const EdgeInsets.all(2),
                            decoration: const BoxDecoration(
                              color: ShTheme.dark,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.close,
                              size: 14,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                if (_draft!.images.length < 6)
                  Material(
                    color: ShTheme.card,
                    borderRadius: BorderRadius.circular(ShTheme.radiusSm),
                    child: InkWell(
                      onTap: _saving ? null : _addImage,
                      borderRadius: BorderRadius.circular(ShTheme.radiusSm),
                      child: Container(
                        width: 84,
                        height: 84,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(ShTheme.radiusSm),
                          border: Border.all(color: ShTheme.border),
                        ),
                        child: const Icon(
                          Icons.add_a_photo_outlined,
                          color: ShTheme.muted,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ],
          const SizedBox(height: 20),
          const ShSectionTitle(
            title: 'İlan kuralları',
            subtitle: 'Kaydetmeden ve yayına göndermeden önce kabul edin.',
          ),
          _AgreementCheck(
            value: _acceptUploadTerms,
            label: 'İkinci El İlan Kuralları’nı okudum ve kabul ediyorum',
            onChanged: (v) => setState(() => _acceptUploadTerms = v),
            onRead: () => _showListingAgreement(
              _listingRulesTitle,
              _listingRulesContent,
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: ShOutlineButton(
                  label: 'Kaydet',
                  onPressed: _saving ? null : _saveDraft,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ShPrimaryButton(
                  label: 'Yayına Gönder',
                  loading: _saving,
                  expand: true,
                  onPressed: _saving ? null : _publish,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  void _showListingAgreement(String title, String content) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: SizedBox(
          width: double.maxFinite,
          child: SingleChildScrollView(
            child: content.contains('<')
                ? Html(data: content)
                : Text(
                    content.isNotEmpty
                        ? content
                        : 'İkinci El İlan Kuralları henüz yüklenemedi. Admin paneldeki yasal belgelerden kontrol edin.',
                  ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(Language.dismiss),
          ),
        ],
      ),
    );
  }
}

class SecondHandMessagesTab extends StatefulWidget {
  const SecondHandMessagesTab({
    super.key,
    required this.service,
    required this.token,
    required this.errorMessage,
  });

  final SecondHandService service;
  final String token;
  final String Function(Object) errorMessage;

  @override
  State<SecondHandMessagesTab> createState() => SecondHandMessagesTabState();
}

class SecondHandMessagesTabState extends State<SecondHandMessagesTab> {
  List<SecondHandConversation> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (widget.token.isEmpty) {
      setState(() {
        _loading = false;
        _items = [];
      });
      return;
    }

    setState(() => _loading = true);
    try {
      final result = await widget.service.fetchInbox(token: widget.token);
      if (!mounted) return;
      setState(() {
        _items = result.items;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      Utils.errorSnackBar(context, widget.errorMessage(e));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.token.isEmpty) {
      return ShEmptyState(
        icon: Icons.login_rounded,
        title: 'Giriş yapın',
        subtitle: 'Mesajlarınızı görmek için hesabınıza giriş yapın.',
        action: ShPrimaryButton(
          label: 'Giriş Yap',
          onPressed: () {
            Navigator.pushNamed(context, RouteNames.authenticationScreen);
          },
        ),
      );
    }

    if (_loading) return const ShLoading();
    if (_items.isEmpty) {
      return const ShEmptyState(
        icon: Icons.chat_bubble_outline,
        title: 'Mesajınız yok',
        subtitle: 'Bir ilana mesaj gönderdiğinizde\nkonuşmalar burada görünür.',
      );
    }
    return RefreshIndicator(
      color: ShTheme.primary,
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 8),
        itemBuilder: (context, index) {
          final conv = _items[index];
          return Material(
            color: ShTheme.card,
            borderRadius: BorderRadius.circular(ShTheme.radius),
            child: InkWell(
              onTap: () {
                Navigator.pushNamed(
                  context,
                  RouteNames.secondHandConversationScreen,
                  arguments: conv.id,
                ).then((_) => _load());
              },
              borderRadius: BorderRadius.circular(ShTheme.radius),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: ShTheme.cardDecoration(),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 22,
                      backgroundColor: ShTheme.primary.withValues(alpha: 0.2),
                      child: const Icon(
                        Icons.store_outlined,
                        color: ShTheme.dark,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            conv.listingTitle,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 3),
                          Text(
                            conv.lastMessagePreview.isNotEmpty
                                ? conv.lastMessagePreview
                                : conv.counterpartyDisplay,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 13,
                              color: ShTheme.muted,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (conv.unreadCount > 0) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: ShTheme.primary,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          '${conv.unreadCount}',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: ShTheme.dark,
                          ),
                        ),
                      ),
                    ] else
                      Icon(
                        Icons.chevron_right_rounded,
                        color: ShTheme.muted.withValues(alpha: 0.5),
                      ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
