import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_kyc_model.dart';
import '../services/seller_api_service.dart';
import '../widgets/seller_kyc_banner.dart';

class SellerKycScreen extends StatefulWidget {
  const SellerKycScreen({super.key});

  @override
  State<SellerKycScreen> createState() => _SellerKycScreenState();
}

class _SellerKycScreenState extends State<SellerKycScreen> {
  final _service = SellerApiService();
  final _ibanCtrl = TextEditingController();
  final _taxCtrl = TextEditingController();
  final _tcCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();
  final _taxOfficeCtrl = TextEditingController();
  final _legalTitleCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  SellerKycBundle? _bundle;
  String? _sellerType;
  bool _loading = true;
  bool _savingInfo = false;
  bool _uploading = false;
  String? _error;
  String? _pickedPath;
  String _pickedName = '';

  static const _sellerTypes = <String, String>{
    'sole_proprietorship': 'Şahıs Şirketi',
    'limited_company': 'Limited / A.Ş.',
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _ibanCtrl.dispose();
    _taxCtrl.dispose();
    _tcCtrl.dispose();
    _addressCtrl.dispose();
    _taxOfficeCtrl.dispose();
    _legalTitleCtrl.dispose();
    _emailCtrl.dispose();
    super.dispose();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final bundle = await _service.fetchKycDocuments(_token);
      if (!mounted) return;
      _ibanCtrl.text = bundle.iban;
      _taxCtrl.text = bundle.taxNumber;
      _tcCtrl.text = bundle.tcIdentity;
      _addressCtrl.text = bundle.address;
      _taxOfficeCtrl.text = bundle.taxOffice;
      _legalTitleCtrl.text = bundle.legalCompanyTitle;
      final type = bundle.sellerType;
      _sellerType = _sellerTypes.containsKey(type)
          ? type
          : (type == 'corporate' ? 'limited_company' : null);
      setState(() {
        _bundle = bundle;
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

  Future<void> _saveInfo() async {
    if (_sellerType == null) {
      Utils.errorSnackBar(context, 'Satıcı tipi seçin');
      return;
    }
    if (_ibanCtrl.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'IBAN girin');
      return;
    }
    if (_tcCtrl.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'TC Kimlik No girin');
      return;
    }
    if (_addressCtrl.text.trim().length < 10) {
      Utils.errorSnackBar(context, 'Adres en az 10 karakter olmalı');
      return;
    }

    setState(() => _savingInfo = true);
    Utils.loadingDialog(context);
    try {
      final fields = <String, String>{
        'seller_type': _sellerType!,
        'iban': _ibanCtrl.text.trim(),
        'tc_identity': _tcCtrl.text.trim(),
        'address': _addressCtrl.text.trim(),
        'tax_number': _taxCtrl.text.trim(),
        'tax_office': _taxOfficeCtrl.text.trim(),
        'legal_company_title': _legalTitleCtrl.text.trim(),
      };
      if (_emailCtrl.text.trim().isNotEmpty) {
        fields['email'] = _emailCtrl.text.trim();
      }
      await _service.updateKycInfo(token: _token, fields: fields);
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Bilgiler kaydedildi');
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _savingInfo = false);
    }
  }

  Future<void> _pickFile() async {
    final choice = await showModalBottomSheet<String>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera),
              title: const Text('Kamera'),
              onTap: () => Navigator.pop(context, 'camera'),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Galeri'),
              onTap: () => Navigator.pop(context, 'gallery'),
            ),
            ListTile(
              leading: const Icon(Icons.insert_drive_file),
              title: const Text('Dosya (PDF)'),
              onTap: () => Navigator.pop(context, 'file'),
            ),
          ],
        ),
      ),
    );
    if (choice == null || !mounted) return;

    if (choice == 'file') {
      final result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png'],
      );
      if (result == null || result.files.isEmpty) return;
      final path = result.files.single.path;
      if (path == null) return;
      setState(() {
        _pickedPath = path;
        _pickedName = result.files.single.name;
      });
      return;
    }

    final source =
        choice == 'camera' ? ImageSource.camera : ImageSource.gallery;
    final file = await ImagePicker().pickImage(
      source: source,
      imageQuality: 85,
      maxWidth: 2000,
    );
    if (file == null) return;
    setState(() {
      _pickedPath = file.path;
      _pickedName = file.name;
    });
  }

  Future<void> _upload() async {
    if (_pickedPath == null) {
      Utils.errorSnackBar(context, 'Önce belge seçin');
      return;
    }
    setState(() => _uploading = true);
    Utils.loadingDialog(context);
    try {
      final bundle = await _service.uploadKycDocument(
        token: _token,
        documentType: 'tax_certificate',
        filePath: _pickedPath!,
        iban: _ibanCtrl.text,
        taxNumber: _taxCtrl.text,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        bundle.message ?? 'Belge yüklendi, onay bekleniyor.',
      );
      setState(() {
        _pickedPath = null;
        _pickedName = '';
      });
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _delete(SellerKycDocument doc) async {
    try {
      Utils.loadingDialog(context);
      await _service.deleteKycDocument(_token, doc.id);
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Belge silindi');
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = _bundle?.kycStatus ?? 'not_submitted';
    final approved = status == 'approved';
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('KYC Doğrulama'),
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
                padding: const EdgeInsets.fromLTRB(0, 0, 0, 24),
                children: [
                  _KycStatusHeader(status: status),
                  if (!approved)
                    SellerKycBanner(kycStatus: status, tappable: false),
                  if (_error != null)
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child:
                          Text(_error!, style: const TextStyle(color: Colors.red)),
                    ),
                  if (_bundle != null) _KycSummaryCard(bundle: _bundle!),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                    child: Container(
                      padding: const EdgeInsets.all(14),
                      decoration: HomeTheme.cardDecoration(),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            approved ? 'Kayıtlı Hesap Bilgileri' : 'Hesap Bilgileri',
                            style: const TextStyle(fontWeight: FontWeight.w800),
                          ),
                          if (approved) ...[
                            const SizedBox(height: 6),
                            const Text(
                              'Hesabınız doğrulandı. Bilgileri gerekirse güncelleyebilirsiniz.',
                              style: TextStyle(
                                fontSize: 12,
                                color: HomeTheme.textMuted,
                              ),
                            ),
                          ],
                          const SizedBox(height: 12),
                          DropdownButtonFormField<String>(
                            // ignore: deprecated_member_use
                            value: _sellerType,
                            isExpanded: true,
                            decoration: const InputDecoration(
                              labelText: 'Satıcı tipi *',
                              border: OutlineInputBorder(),
                            ),
                            items: _sellerTypes.entries
                                .map(
                                  (e) => DropdownMenuItem(
                                    value: e.key,
                                    child: Text(e.value),
                                  ),
                                )
                                .toList(),
                            onChanged: (v) => setState(() => _sellerType = v),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _ibanCtrl,
                            decoration: const InputDecoration(
                              labelText: 'IBAN *',
                              border: OutlineInputBorder(),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _tcCtrl,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(
                              labelText: 'TC Kimlik No *',
                              border: OutlineInputBorder(),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _addressCtrl,
                            maxLines: 3,
                            decoration: const InputDecoration(
                              labelText: 'Adres *',
                              border: OutlineInputBorder(),
                              alignLabelWithHint: true,
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _taxCtrl,
                            decoration: const InputDecoration(
                              labelText: 'Vergi No',
                              border: OutlineInputBorder(),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _taxOfficeCtrl,
                            decoration: const InputDecoration(
                              labelText: 'Vergi Dairesi',
                              border: OutlineInputBorder(),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _legalTitleCtrl,
                            decoration: const InputDecoration(
                              labelText: 'Unvan / Şirket Ünvanı',
                              border: OutlineInputBorder(),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _emailCtrl,
                            keyboardType: TextInputType.emailAddress,
                            decoration: const InputDecoration(
                              labelText: 'E-posta (isteğe bağlı)',
                              border: OutlineInputBorder(),
                              helperText:
                                  'Hesabınızda geçerli e-posta yoksa zorunlu olabilir',
                            ),
                          ),
                          const SizedBox(height: 14),
                          FilledButton.icon(
                            onPressed: _savingInfo ? null : _saveInfo,
                            icon: const Icon(Icons.save_outlined),
                            label: const Text('Bilgileri Kaydet'),
                            style: FilledButton.styleFrom(
                              backgroundColor: HomeTheme.brandYellow,
                              foregroundColor: HomeTheme.textDark,
                              minimumSize: const Size.fromHeight(46),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  if (!approved)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                      child: Container(
                        padding: const EdgeInsets.all(14),
                        decoration: HomeTheme.cardDecoration(),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Belge Yükle',
                              style: TextStyle(fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(height: 6),
                            const Text(
                              'Şimdilik yalnızca vergi levhası yeterlidir (PDF/JPG/PNG, max 5MB).',
                              style: TextStyle(
                                fontSize: 12,
                                color: HomeTheme.textMuted,
                              ),
                            ),
                            const SizedBox(height: 12),
                            OutlinedButton.icon(
                              onPressed: _pickFile,
                              icon: const Icon(Icons.attach_file),
                              label: Text(
                                _pickedName.isEmpty ? 'Belge seç' : _pickedName,
                              ),
                            ),
                            const SizedBox(height: 12),
                            FilledButton.icon(
                              onPressed: _uploading ? null : _upload,
                              icon: const Icon(Icons.upload_file),
                              label: const Text('Belgeyi Yükle'),
                              style: FilledButton.styleFrom(
                                backgroundColor: HomeTheme.brandYellow,
                                foregroundColor: HomeTheme.textDark,
                                minimumSize: const Size.fromHeight(46),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  const Padding(
                    padding: EdgeInsets.fromLTRB(16, 16, 16, 0),
                    child: Text(
                      'Yüklenen Belgeler',
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                      ),
                    ),
                  ),
                  if ((_bundle?.documents ?? const []).isEmpty)
                    const Padding(
                      padding: EdgeInsets.all(24),
                      child: Center(
                        child: Text(
                          'Henüz belge yok',
                          style: TextStyle(color: HomeTheme.textMuted),
                        ),
                      ),
                    )
                  else
                    ..._bundle!.documents.map(
                      (doc) => Padding(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: HomeTheme.cardDecoration(),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      doc.typeLabel,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    Text(
                                      doc.originalName.isEmpty
                                          ? doc.statusLabel
                                          : '${doc.originalName} · ${doc.statusLabel}',
                                      style: const TextStyle(
                                        fontSize: 12,
                                        color: HomeTheme.textMuted,
                                      ),
                                    ),
                                    if (doc.adminNote.isNotEmpty)
                                      Text(
                                        doc.adminNote,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: Colors.redAccent,
                                        ),
                                      ),
                                  ],
                                ),
                              ),
                              if (doc.canDelete)
                                IconButton(
                                  onPressed: () => _delete(doc),
                                  icon: const Icon(Icons.delete_outline),
                                ),
                            ],
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}

class _KycStatusHeader extends StatelessWidget {
  const _KycStatusHeader({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final (title, subtitle, color, icon) = switch (status) {
      'approved' => (
          'Doğrulandı',
          'KYC onaylı — ürün ekleyebilir ve satış yapabilirsiniz.',
          const Color(0xFF34A853),
          Icons.verified_rounded,
        ),
      'pending' => (
          'İncelemede',
          'Belgeleriniz kontrol ediliyor.',
          const Color(0xFFF9A825),
          Icons.hourglass_top_rounded,
        ),
      'rejected' => (
          'Reddedildi',
          'Eksik/hatalı bilgileri güncelleyip tekrar yükleyin.',
          Colors.redAccent,
          Icons.cancel_outlined,
        ),
      _ => (
          'Doğrulama gerekli',
          'Bilgilerinizi girin ve vergi levhasını yükleyin.',
          HomeTheme.brandYellow,
          Icons.verified_user_outlined,
        ),
    };

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 4),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.45)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 32),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    fontSize: 17,
                    color: color == HomeTheme.brandYellow
                        ? HomeTheme.textDark
                        : color,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: const TextStyle(
                    fontSize: 12,
                    color: HomeTheme.textMuted,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _KycSummaryCard extends StatelessWidget {
  const _KycSummaryCard({required this.bundle});

  final SellerKycBundle bundle;

  static const _sellerTypes = <String, String>{
    'sole_proprietorship': 'Şahıs Şirketi',
    'limited_company': 'Limited / A.Ş.',
    'corporate': 'Limited / A.Ş.',
  };

  @override
  Widget build(BuildContext context) {
    final rows = <(String, String)>[
      if (bundle.sellerType.trim().isNotEmpty)
        (
          'Satıcı tipi',
          _sellerTypes[bundle.sellerType] ?? bundle.sellerType,
        ),
      if (bundle.legalCompanyTitle.trim().isNotEmpty)
        ('Unvan', bundle.legalCompanyTitle.trim()),
      if (bundle.taxNumber.trim().isNotEmpty)
        ('Vergi No', bundle.taxNumber.trim()),
      if (bundle.taxOffice.trim().isNotEmpty)
        ('Vergi Dairesi', bundle.taxOffice.trim()),
      if (bundle.tcIdentity.trim().isNotEmpty)
        ('TC Kimlik No', bundle.tcIdentity.trim()),
      if (bundle.iban.trim().isNotEmpty) ('IBAN', bundle.iban.trim()),
      if (bundle.address.trim().isNotEmpty) ('Adres', bundle.address.trim()),
    ];

    if (rows.isEmpty) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(14),
        decoration: HomeTheme.cardDecoration(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Doğrulama Bilgileri',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 4),
            const Text(
              'Sistemde kayıtlı bilgiler',
              style: TextStyle(fontSize: 12, color: HomeTheme.textMuted),
            ),
            const SizedBox(height: 10),
            ...rows.map(
              (row) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(
                      width: 110,
                      child: Text(
                        row.$1,
                        style: const TextStyle(
                          fontSize: 12,
                          color: HomeTheme.textMuted,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    Expanded(
                      child: Text(
                        row.$2,
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
