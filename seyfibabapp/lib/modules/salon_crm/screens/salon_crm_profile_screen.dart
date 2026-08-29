import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmProfileScreen extends StatefulWidget {
  const SalonCrmProfileScreen({super.key});

  @override
  State<SalonCrmProfileScreen> createState() => _SalonCrmProfileScreenState();
}

class _SalonCrmProfileScreenState extends State<SalonCrmProfileScreen> {
  final _service = SalonCrmService();
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _bioCtrl = TextEditingController();

  bool _loading = true;
  bool _saving = false;
  bool _showToCustomers = false;
  String? _error;
  String? _logoUrl;
  String? _coverUrl;
  String? _logoPath;
  String? _coverPath;
  bool _canWrite = true;
  SalonCrmStatus? _status;
  int _openHour = 9;
  int _closeHour = 21;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _bioCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      if (token.isEmpty) throw Exception('CRM girişi gerekli');
      final status = await _service.fetchStatus(token);
      final profile = await _service.fetchSalonProfile(token);
      if (!mounted) return;
      setState(() {
        _nameCtrl.text = profile.name;
        _phoneCtrl.text = profile.phone ?? '';
        _bioCtrl.text = profile.profileText ?? '';
        _showToCustomers = profile.showProfileToCustomers;
        _logoUrl = profile.logoImage;
        _coverUrl = profile.coverImage;
        _openHour = profile.openHour;
        _closeHour = profile.closeHour;
        _canWrite = status.access.canWrite;
        _status = status;
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

  Future<void> _pick(bool logo) async {
    if (!_canWrite) {
      Utils.errorSnackBar(context, 'CRM kilitli');
      return;
    }
    final file = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1600,
    );
    if (file == null) return;
    setState(() {
      if (logo) {
        _logoPath = file.path;
      } else {
        _coverPath = file.path;
      }
    });
  }

  Future<void> _save() async {
    if (!_canWrite || _saving) return;
    final name = _nameCtrl.text.trim();
    if (name.isEmpty) {
      Utils.errorSnackBar(context, 'Salon adı gerekli');
      return;
    }
    setState(() => _saving = true);
    Utils.loadingDialog(context);
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      final profile = await _service.updateSalonProfile(
        token: token,
        name: name,
        phone: _phoneCtrl.text.trim(),
        profileText: _bioCtrl.text.trim(),
        showProfileToCustomers: _showToCustomers,
        openHour: _openHour,
        closeHour: _closeHour,
        logoImagePath: _logoPath,
        coverImagePath: _coverPath,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      setState(() {
        _logoUrl = profile.logoImage;
        _coverUrl = profile.coverImage;
        _logoPath = null;
        _coverPath = null;
        _saving = false;
      });
      Utils.showSnackBar(context, 'Salon profili kaydedildi');
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      setState(() => _saving = false);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  Widget _imageBox({
    required String label,
    required double height,
    String? url,
    String? localPath,
    required VoidCallback onPick,
  }) {
    final imageUrl = localPath != null
        ? null
        : (url != null && url.isNotEmpty ? RemoteUrls.imageUrl(url) : null);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CrmSectionLabel(label),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: _canWrite ? onPick : null,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
            child: Container(
              height: height,
              width: double.infinity,
              color: SalonCrmTheme.line.withValues(alpha: 0.35),
              child: localPath != null
                  ? Image.file(File(localPath), fit: BoxFit.cover)
                  : imageUrl != null && imageUrl.isNotEmpty
                      ? Image.network(imageUrl, fit: BoxFit.cover)
                      : Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.add_a_photo_outlined,
                              color: SalonCrmTheme.muted,
                              size: height > 100 ? 36 : 28,
                            ),
                            const SizedBox(height: 6),
                            Text(
                              _canWrite ? 'Fotoğraf ekle' : 'Fotoğraf yok',
                              style: SalonCrmTheme.caption,
                            ),
                          ],
                        ),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: 'Salon profili',
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
              ? Center(child: Text(_error!))
              : ListView(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
                  children: [
                    _AccessDetailCard(
                      canWrite: _canWrite,
                      status: _status,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Müşteriler randevu alırken salonunuzu görebilir. Bu tamamen isteğe bağlıdır.',
                      style: SalonCrmTheme.body,
                    ),
                    const SizedBox(height: 16),
                    _imageBox(
                      label: 'Kapak fotoğrafı',
                      height: 140,
                      url: _coverUrl,
                      localPath: _coverPath,
                      onPick: () => _pick(false),
                    ),
                    const SizedBox(height: 16),
                    _imageBox(
                      label: 'Logo / profil fotoğrafı',
                      height: 120,
                      url: _logoUrl,
                      localPath: _logoPath,
                      onPick: () => _pick(true),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _nameCtrl,
                      enabled: _canWrite,
                      decoration: SalonCrmTheme.field('Salon adı'),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _phoneCtrl,
                      enabled: _canWrite,
                      keyboardType: TextInputType.phone,
                      decoration: SalonCrmTheme.field('Telefon'),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _bioCtrl,
                      enabled: _canWrite,
                      maxLines: 4,
                      decoration: SalonCrmTheme.field(
                        'Salon hakkında (isteğe bağlı)',
                      ),
                    ),
                    const SizedBox(height: 12),
                    SwitchListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text(
                        'Müşterilere profili göster',
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                      subtitle: Text(
                        'Kapalıyken müşteriler yalnızca salon adını görür.',
                        style: SalonCrmTheme.caption,
                      ),
                      value: _showToCustomers,
                      activeColor: SalonCrmTheme.accent,
                      onChanged: _canWrite
                          ? (v) => setState(() => _showToCustomers = v)
                          : null,
                    ),
                    const SizedBox(height: 8),
                    const CrmSectionLabel('Açılış / kapanış saatleri'),
                    Text(
                      'Anasayfadaki sarı-yeşil-kırmızı saatler bu aralığa göre görünür.',
                      style: SalonCrmTheme.caption,
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<int>(
                            value: _openHour,
                            decoration: SalonCrmTheme.field('Açılış'),
                            items: [
                              for (var h = 0; h <= 22; h++)
                                DropdownMenuItem(
                                  value: h,
                                  child: Text(salonCrmHourLabel(h)),
                                ),
                            ],
                            onChanged: _canWrite
                                ? (v) {
                                    if (v != null) {
                                      setState(() => _openHour = v);
                                    }
                                  }
                                : null,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: DropdownButtonFormField<int>(
                            value: _closeHour <= _openHour
                                ? _openHour + 1
                                : _closeHour,
                            decoration: SalonCrmTheme.field('Kapanış'),
                            items: [
                              for (var h = _openHour + 1; h <= 23; h++)
                                DropdownMenuItem(
                                  value: h,
                                  child: Text(salonCrmHourLabel(h)),
                                ),
                            ],
                            onChanged: _canWrite
                                ? (v) {
                                    if (v != null) {
                                      setState(() => _closeHour = v);
                                    }
                                  }
                                : null,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    if (_canWrite)
                      CrmPrimaryButton(
                        label: 'Kaydet',
                        icon: Icons.check_rounded,
                        onPressed: _saving ? null : _save,
                      )
                    else
                      Text(
                        'CRM kilitli — profil görüntülenir, düzenlenemez.',
                        style: SalonCrmTheme.caption,
                      ),
                  ],
                ),
    );
  }
}

class _AccessDetailCard extends StatelessWidget {
  const _AccessDetailCard({required this.canWrite, this.status});

  final bool canWrite;
  final SalonCrmStatus? status;

  @override
  Widget build(BuildContext context) {
    if (status == null) return const SizedBox.shrink();
    final access = status!.access;
    final unlocked = access.isUnlocked;
    final progress = access.threshold <= 0
        ? 0.0
        : (access.monthSpend / access.threshold).clamp(0.0, 1.0);

    return CrmSoftCard(
      color: unlocked
          ? SalonCrmTheme.accentSoft.withValues(alpha: 0.55)
          : SalonCrmTheme.dangerSoft.withValues(alpha: 0.4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: unlocked
                      ? SalonCrmTheme.successSoft
                      : SalonCrmTheme.dangerSoft,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  unlocked ? Icons.verified_rounded : Icons.lock_rounded,
                  color: unlocked
                      ? SalonCrmTheme.success
                      : SalonCrmTheme.danger,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      unlocked ? 'CRM Aktif' : 'CRM Kilitli',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: unlocked
                            ? SalonCrmTheme.success
                            : SalonCrmTheme.danger,
                      ),
                    ),
                    Text(
                      status!.salon?.name ?? '',
                      style: SalonCrmTheme.caption,
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(access.message, style: SalonCrmTheme.body),
          const SizedBox(height: 14),
          ClipRRect(
            borderRadius: BorderRadius.circular(999),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 7,
              backgroundColor: SalonCrmTheme.line,
              color: unlocked ? SalonCrmTheme.success : SalonCrmTheme.accent,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Bu ay sipariş: ${access.monthSpend.toStringAsFixed(0)} / ${access.threshold} TL',
            style: SalonCrmTheme.caption,
          ),
          if (!unlocked) ...[
            const SizedBox(height: 10),
            Text(
              'Alışveriş yaparak CRM\'i aktif edebilirsiniz. Geçmiş verileriniz korunur.',
              style: SalonCrmTheme.caption,
            ),
          ],
        ],
      ),
    );
  }
}
