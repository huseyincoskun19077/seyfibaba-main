import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmWebsiteScreen extends StatefulWidget {
  const SalonCrmWebsiteScreen({super.key});

  @override
  State<SalonCrmWebsiteScreen> createState() => _SalonCrmWebsiteScreenState();
}

class _SalonCrmWebsiteScreenState extends State<SalonCrmWebsiteScreen> {
  final _service = SalonCrmService();
  final _provinceCtrl = TextEditingController();
  final _districtCtrl = TextEditingController();
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _whatsappCtrl = TextEditingController();
  final _instagramCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();
  final _bioCtrl = TextEditingController();
  final _seoTitleCtrl = TextEditingController();
  final _seoDescCtrl = TextEditingController();

  bool _loading = true;
  bool _saving = false;
  bool _previewing = false;
  String? _error;
  String? _previewUrl;
  String? _savedUrl;
  String? _qrUrl;
  String? _accessMessage;
  String? _logoUrl;
  String? _logoPath;
  bool _removeLogo = false;
  bool _subscriptionUnlocked = true;
  bool _publicActive = false;

  bool _enabled = false;
  bool _showCalendar = false;
  bool _showPrices = false;
  bool _showStaffAppts = false;
  String _type = 'kuafor';
  int _openHour = 9;
  int _closeHour = 21;
  Timer? _previewDebounce;

  @override
  void initState() {
    super.initState();
    _provinceCtrl.addListener(_schedulePreview);
    _districtCtrl.addListener(_schedulePreview);
    _nameCtrl.addListener(_schedulePreview);
    _load();
  }

  @override
  void dispose() {
    _previewDebounce?.cancel();
    _provinceCtrl.dispose();
    _districtCtrl.dispose();
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _whatsappCtrl.dispose();
    _instagramCtrl.dispose();
    _addressCtrl.dispose();
    _bioCtrl.dispose();
    _seoTitleCtrl.dispose();
    _seoDescCtrl.dispose();
    super.dispose();
  }

  void _apply(SalonCrmWebsiteSettings s) {
    _enabled = s.websiteEnabled;
    _showCalendar = s.showCalendar;
    _showPrices = s.showPrices;
    _showStaffAppts = s.showStaffAppointments;
    _type = s.type;
    _openHour = s.openHour;
    _closeHour = s.closeHour;
    _provinceCtrl.text = s.province ?? '';
    _districtCtrl.text = s.district ?? '';
    _nameCtrl.text = s.name;
    _phoneCtrl.text = s.phone ?? '';
    _whatsappCtrl.text = s.whatsapp ?? '';
    _instagramCtrl.text = s.instagram ?? '';
    _addressCtrl.text = s.address ?? '';
    _bioCtrl.text = s.profileText ?? '';
    _seoTitleCtrl.text = s.seoTitle ?? '';
    _seoDescCtrl.text = s.seoDescription ?? '';
    _savedUrl = s.url;
    _previewUrl = s.url;
    _qrUrl = s.qrUrl;
    _logoUrl = s.logoImage;
    _logoPath = null;
    _removeLogo = false;
    _subscriptionUnlocked = s.subscriptionUnlocked;
    _publicActive = s.publicActive;
    _accessMessage = s.accessMessage;
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      if (token.isEmpty) throw Exception('CRM girişi gerekli');
      final settings = await _service.fetchWebsiteSettings(token);
      if (!mounted) return;
      setState(() {
        _apply(settings);
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  void _schedulePreview() {
    _previewDebounce?.cancel();
    _previewDebounce = Timer(const Duration(milliseconds: 450), _runPreview);
  }

  Future<void> _runPreview() async {
    final province = _provinceCtrl.text.trim();
    final district = _districtCtrl.text.trim();
    final name = _nameCtrl.text.trim();
    if (province.isEmpty || district.isEmpty || name.isEmpty) {
      if (mounted) setState(() => _previewUrl = _savedUrl);
      return;
    }
    final token = (await SalonCrmSession.token()) ?? '';
    if (token.isEmpty) return;
    setState(() => _previewing = true);
    try {
      final preview = await _service.previewWebsiteUrl(
        token: token,
        province: province,
        district: district,
        name: name,
      );
      if (!mounted) return;
      setState(() {
        _previewUrl = preview['url']?.toString() ?? _previewUrl;
        _previewing = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _previewing = false);
    }
  }

  Future<void> _pickLogo() async {
    final file = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      maxWidth: 1600,
      imageQuality: 85,
    );
    if (file == null) return;
    setState(() {
      _logoPath = file.path;
      _removeLogo = false;
    });
  }

  Future<void> _save() async {
    final token = (await SalonCrmSession.token()) ?? '';
    if (token.isEmpty) return;
    setState(() => _saving = true);
    try {
      final settings = await _service.updateWebsiteSettings(
        token: token,
        websiteEnabled: _enabled,
        showCalendar: _showCalendar,
        showPrices: _showPrices,
        showStaffAppointments: _showStaffAppts,
        province: _provinceCtrl.text.trim(),
        district: _districtCtrl.text.trim(),
        name: _nameCtrl.text.trim(),
        type: _type,
        phone: _phoneCtrl.text.trim(),
        whatsapp: _whatsappCtrl.text.trim(),
        instagram: _instagramCtrl.text.trim(),
        address: _addressCtrl.text.trim(),
        profileText: _bioCtrl.text.trim(),
        openHour: _openHour,
        closeHour: _closeHour,
        seoTitle: _seoTitleCtrl.text.trim(),
        seoDescription: _seoDescCtrl.text.trim(),
        logoImagePath: _logoPath,
        removeLogo: _removeLogo,
      );
      if (!mounted) return;
      setState(() {
        _apply(settings);
        _saving = false;
      });
      Utils.showSnackBar(context, 'Web sitesi ayarları kaydedildi');
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  Future<void> _copyUrl() async {
    final url = _savedUrl ?? _previewUrl;
    if (url == null || url.isEmpty) return;
    await Clipboard.setData(ClipboardData(text: url));
    if (!mounted) return;
    Utils.showSnackBar(context, 'Link kopyalandı');
  }

  Future<void> _shareUrl() async {
    final url = _savedUrl ?? _previewUrl;
    if (url == null || url.isEmpty) return;
    await Share.share('${_nameCtrl.text.trim()} — Seyfibaba salon sitesi\n$url');
  }

  Future<void> _openUrl() async {
    final url = _savedUrl ?? _previewUrl;
    if (url == null || url.isEmpty) return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: 'Web siteni aç',
      actions: [
        IconButton(
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh_rounded, size: 22),
          color: SalonCrmTheme.ink,
        ),
      ],
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(color: SalonCrmTheme.accent),
            )
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_error!, textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        TextButton(onPressed: _load, child: const Text('Tekrar dene')),
                      ],
                    ),
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
                  children: [
                    Text(
                      'Müşterilerin için seyfibaba.com üzerinde mini bir salon sitesi. '
                      'Mevcut CRM randevu akışına yönlendirir; CRM’in yerine geçmez.',
                      style: SalonCrmTheme.body,
                    ),
                    if (!_subscriptionUnlocked) ...[
                      const SizedBox(height: 12),
                      CrmSoftCard(
                        child: Text(
                          _accessMessage ??
                              'Abonelik / erişim kapalıyken site yayında görünmez.',
                          style: SalonCrmTheme.body,
                        ),
                      ),
                    ],
                    const SizedBox(height: 16),
                    SwitchListTile.adaptive(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Site açık',
                          style: TextStyle(fontWeight: FontWeight.w700)),
                      subtitle: Text(
                        _publicActive
                            ? 'Yayında'
                            : (_enabled
                                ? 'Açık seçili ama abonelik/erişim nedeniyle pasif'
                                : 'Kapalı — güzel bir kapalı sayfası gösterilir'),
                        style: SalonCrmTheme.caption,
                      ),
                      value: _enabled,
                      onChanged: (v) => setState(() => _enabled = v),
                    ),
                    SwitchListTile.adaptive(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Takvimi sitede göster'),
                      value: _showCalendar,
                      onChanged: (v) => setState(() => _showCalendar = v),
                    ),
                    SwitchListTile.adaptive(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Fiyatları göster'),
                      subtitle: const Text(
                        'Kapalıysa yalnızca isim + süre',
                        style: TextStyle(fontSize: 12),
                      ),
                      value: _showPrices,
                      onChanged: (v) => setState(() => _showPrices = v),
                    ),
                    SwitchListTile.adaptive(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Personel randevularını göster'),
                      value: _showStaffAppts,
                      onChanged: (v) => setState(() => _showStaffAppts = v),
                    ),
                    const SizedBox(height: 12),
                    Text('Adres yolu', style: SalonCrmTheme.caption),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _provinceCtrl,
                      decoration: SalonCrmTheme.field('İl'),
                      textCapitalization: TextCapitalization.words,
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _districtCtrl,
                      decoration: SalonCrmTheme.field('İlçe'),
                      textCapitalization: TextCapitalization.words,
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _nameCtrl,
                      decoration: SalonCrmTheme.field('Dükkan adı'),
                      textCapitalization: TextCapitalization.words,
                    ),
                    const SizedBox(height: 10),
                    DropdownButtonFormField<String>(
                      value: _type,
                      decoration: SalonCrmTheme.field('Tür'),
                      items: const [
                        DropdownMenuItem(value: 'kuafor', child: Text('Kuaför')),
                        DropdownMenuItem(value: 'berber', child: Text('Berber')),
                        DropdownMenuItem(
                            value: 'guzellik', child: Text('Güzellik')),
                      ],
                      onChanged: (v) {
                        if (v != null) setState(() => _type = v);
                      },
                    ),
                    const SizedBox(height: 12),
                    CrmSoftCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Text('Canlı URL', style: SalonCrmTheme.caption),
                              if (_previewing) ...[
                                const SizedBox(width: 8),
                                const SizedBox(
                                  width: 12,
                                  height: 12,
                                  child: CircularProgressIndicator(strokeWidth: 2),
                                ),
                              ],
                            ],
                          ),
                          const SizedBox(height: 8),
                          SelectableText(
                            _previewUrl ??
                                'İl, ilçe ve ad girince link oluşur',
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              color: SalonCrmTheme.ink,
                            ),
                          ),
                          if (_qrUrl != null && _qrUrl!.isNotEmpty) ...[
                            const SizedBox(height: 12),
                            Center(
                              child: Image.network(
                                _qrUrl!,
                                width: 140,
                                height: 140,
                                errorBuilder: (_, __, ___) =>
                                    const SizedBox.shrink(),
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              'QR — yazdırıp dükkana as',
                              style: SalonCrmTheme.caption,
                            ),
                          ],
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              OutlinedButton.icon(
                                onPressed: _copyUrl,
                                icon: const Icon(Icons.copy_rounded, size: 18),
                                label: const Text('Kopyala'),
                              ),
                              OutlinedButton.icon(
                                onPressed: _shareUrl,
                                icon: const Icon(Icons.share_rounded, size: 18),
                                label: const Text('Paylaş'),
                              ),
                              OutlinedButton.icon(
                                onPressed: _openUrl,
                                icon: const Icon(Icons.open_in_new_rounded,
                                    size: 18),
                                label: const Text('Aç'),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text('İletişim', style: SalonCrmTheme.caption),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _phoneCtrl,
                      decoration: SalonCrmTheme.field('Telefon'),
                      keyboardType: TextInputType.phone,
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _whatsappCtrl,
                      decoration: SalonCrmTheme.field('WhatsApp'),
                      keyboardType: TextInputType.phone,
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _instagramCtrl,
                      decoration: SalonCrmTheme.field('Instagram (@kullanici)'),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _addressCtrl,
                      decoration: SalonCrmTheme.field('Adres'),
                      maxLines: 2,
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _bioCtrl,
                      decoration: SalonCrmTheme.field('Tanıtım yazısı'),
                      maxLines: 4,
                    ),
                    const SizedBox(height: 12),
                    Text('Logo', style: SalonCrmTheme.caption),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            width: 72,
                            height: 72,
                            color: SalonCrmTheme.line.withValues(alpha: 0.35),
                            child: _logoPath != null
                                ? Image.file(File(_logoPath!), fit: BoxFit.cover)
                                : (!_removeLogo &&
                                        _logoUrl != null &&
                                        _logoUrl!.isNotEmpty)
                                    ? Image.network(
                                        RemoteUrls.imageUrl(_logoUrl!),
                                        fit: BoxFit.cover,
                                      )
                                    : const Icon(Icons.add_a_photo_outlined),
                          ),
                        ),
                        const SizedBox(width: 12),
                        OutlinedButton(
                          onPressed: _pickLogo,
                          child: const Text('Logo seç'),
                        ),
                        TextButton(
                          onPressed: () => setState(() {
                            _logoPath = null;
                            _removeLogo = true;
                          }),
                          child: const Text('Kaldır'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Text('Çalışma saatleri', style: SalonCrmTheme.caption),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<int>(
                            value: _openHour,
                            decoration: SalonCrmTheme.field('Açılış'),
                            items: [
                              for (var h = 0; h <= 23; h++)
                                DropdownMenuItem(
                                  value: h,
                                  child: Text('${h.toString().padLeft(2, '0')}:00'),
                                ),
                            ],
                            onChanged: (v) {
                              if (v != null) setState(() => _openHour = v);
                            },
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: DropdownButtonFormField<int>(
                            value: _closeHour,
                            decoration: SalonCrmTheme.field('Kapanış'),
                            items: [
                              for (var h = 0; h <= 23; h++)
                                DropdownMenuItem(
                                  value: h,
                                  child: Text('${h.toString().padLeft(2, '0')}:00'),
                                ),
                            ],
                            onChanged: (v) {
                              if (v != null) setState(() => _closeHour = v);
                            },
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Text('SEO', style: SalonCrmTheme.caption),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _seoTitleCtrl,
                      decoration: SalonCrmTheme.field('SEO başlık'),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _seoDescCtrl,
                      decoration: SalonCrmTheme.field('SEO açıklama'),
                      maxLines: 3,
                    ),
                    const SizedBox(height: 22),
                    CrmPrimaryButton(
                      label: _saving ? 'Kaydediliyor…' : 'Kaydet',
                      onPressed: _saving ? null : _save,
                    ),
                  ],
                ),
    );
  }
}
