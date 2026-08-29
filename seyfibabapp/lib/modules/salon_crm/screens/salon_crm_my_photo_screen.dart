import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmMyPhotoScreen extends StatefulWidget {
  const SalonCrmMyPhotoScreen({
    super.key,
    required this.staffId,
    required this.staffName,
    this.initialPhoto,
    this.initialShowToCustomers = true,
    this.canWrite = true,
  });

  final int staffId;
  final String staffName;
  final String? initialPhoto;
  final bool initialShowToCustomers;
  final bool canWrite;

  @override
  State<SalonCrmMyPhotoScreen> createState() => _SalonCrmMyPhotoScreenState();
}

class _SalonCrmMyPhotoScreenState extends State<SalonCrmMyPhotoScreen> {
  final _service = SalonCrmService();
  bool _saving = false;
  bool _showToCustomers = true;
  String? _photoUrl;
  String? _photoPath;

  @override
  void initState() {
    super.initState();
    _photoUrl = widget.initialPhoto;
    _showToCustomers = widget.initialShowToCustomers;
  }

  Future<void> _pick() async {
    if (!widget.canWrite) {
      Utils.errorSnackBar(context, 'CRM kilitli');
      return;
    }
    final file = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1200,
    );
    if (file == null) return;
    setState(() => _photoPath = file.path);
  }

  Future<void> _save() async {
    if (!widget.canWrite || _saving) return;
    setState(() => _saving = true);
    Utils.loadingDialog(context);
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      final staff = await _service.updateStaffPhoto(
        token: token,
        staffId: widget.staffId,
        photoPath: _photoPath,
        showPhotoToCustomers: _showToCustomers,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      setState(() {
        _photoUrl = staff.photo;
        _photoPath = null;
        _saving = false;
      });
      Utils.showSnackBar(context, 'Fotoğraf kaydedildi');
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      setState(() => _saving = false);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final networkUrl = _photoPath == null &&
            _photoUrl != null &&
            _photoUrl!.isNotEmpty
        ? RemoteUrls.imageUrl(_photoUrl!)
        : null;

    return CrmScaffold(
      title: widget.staffName,
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
        children: [
          Text(
            'Müşteriler randevu alırken sizi görebilir. Salon profili açıksa ve siz de izin verirseniz fotoğrafınız görünür.',
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 20),
          Center(
            child: GestureDetector(
              onTap: _pick,
              child: CircleAvatar(
                radius: 56,
                backgroundColor: SalonCrmTheme.line.withValues(alpha: 0.4),
                backgroundImage: _photoPath != null
                    ? FileImage(File(_photoPath!))
                    : networkUrl != null && networkUrl.isNotEmpty
                        ? NetworkImage(networkUrl)
                        : null,
                child: _photoPath == null &&
                        (networkUrl == null || networkUrl.isEmpty)
                    ? const Icon(
                        Icons.person_outline_rounded,
                        size: 48,
                        color: SalonCrmTheme.muted,
                      )
                    : null,
              ),
            ),
          ),
          const SizedBox(height: 8),
          Center(
            child: TextButton.icon(
              onPressed: widget.canWrite ? _pick : null,
              icon: const Icon(Icons.photo_camera_outlined),
              label: const Text('Fotoğraf seç'),
            ),
          ),
          const SizedBox(height: 16),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text(
              'Müşterilere fotoğrafımı göster',
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: SalonCrmTheme.ink,
              ),
            ),
            value: _showToCustomers,
            activeColor: SalonCrmTheme.accent,
            onChanged: widget.canWrite
                ? (v) => setState(() => _showToCustomers = v)
                : null,
          ),
          const SizedBox(height: 20),
          if (widget.canWrite)
            CrmPrimaryButton(
              label: 'Kaydet',
              icon: Icons.check_rounded,
              onPressed: _saving ? null : _save,
            ),
        ],
      ),
    );
  }
}
