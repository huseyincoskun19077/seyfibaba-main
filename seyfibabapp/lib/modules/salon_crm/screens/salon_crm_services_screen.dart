import 'package:flutter/material.dart';

import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmServicesScreen extends StatefulWidget {
  const SalonCrmServicesScreen({super.key});

  @override
  State<SalonCrmServicesScreen> createState() => _SalonCrmServicesScreenState();
}

class _SalonCrmServicesScreenState extends State<SalonCrmServicesScreen> {
  final _service = SalonCrmService();
  bool _loading = true;
  bool _canWrite = true;
  String? _error;
  String _token = '';
  List<SalonCrmServiceItem> _services = [];

  @override
  void initState() {
    super.initState();
    _load();
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
      final services = await _service.fetchServices(token);
      if (!mounted) return;
      setState(() {
        _token = token;
        _canWrite = status.access.canWrite;
        _services = services;
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

  Future<void> _editOrCreate({SalonCrmServiceItem? item}) async {
    if (!_canWrite) {
      Utils.errorSnackBar(context, 'CRM kilitli');
      return;
    }
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (ctx) => _ServiceEditorDialog(item: item),
    );
    if (result == null || !mounted) return;

    final name = '${result['name'] ?? ''}'.trim();
    final price =
        double.tryParse('${result['price'] ?? 0}'.replaceAll(',', '.')) ?? 0;
    final duration = int.tryParse('${result['duration'] ?? 30}') ?? 30;
    if (name.isEmpty) {
      Utils.errorSnackBar(context, 'Hizmet adı yazın');
      return;
    }

    Utils.loadingDialog(context);
    try {
      if (item == null) {
        await _service.createService(
          token: _token,
          name: name,
          price: price,
          durationMinutes: duration,
        );
      } else {
        await _service.updateService(
          token: _token,
          id: item.id,
          name: name,
          price: price,
          durationMinutes: duration,
          isActive: item.isActive,
        );
      }
      if (!mounted) return;
      Utils.closeDialog(context);
      await _load();
      if (!mounted) return;
      Utils.showSnackBar(
        context,
        item == null ? 'Hizmet eklendi' : 'Hizmet güncellendi',
      );
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  Future<void> _toggleActive(SalonCrmServiceItem item) async {
    if (!_canWrite) {
      Utils.errorSnackBar(context, 'CRM kilitli');
      return;
    }
    Utils.loadingDialog(context);
    try {
      await _service.updateService(
        token: _token,
        id: item.id,
        name: item.name,
        price: item.price,
        durationMinutes: item.durationMinutes,
        isActive: !item.isActive,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final active = _services.where((s) => s.isActive).toList();
    final inactive = _services.where((s) => !s.isActive).toList();

    return CrmScaffold(
      title: 'Hizmetler',
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
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
                  children: [
                    Text(
                      'İsim, süre (dk) ve fiyatı düzenleyebilirsin. Randevu alırken buradaki hizmetler listelenir.',
                      style: SalonCrmTheme.body,
                    ),
                    const SizedBox(height: 16),
                    if (active.isEmpty && inactive.isEmpty)
                      CrmSoftCard(
                        child: Text(
                          'Henüz hizmet yok. Aşağıdan ekle.',
                          style: SalonCrmTheme.caption,
                        ),
                      )
                    else ...[
                      ...active.map(_serviceCard),
                      if (inactive.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        const CrmSectionLabel('Pasif hizmetler'),
                        const SizedBox(height: 8),
                        ...inactive.map(_serviceCard),
                      ],
                    ],
                  ],
                ),
      floatingActionButton: _canWrite && !_loading
          ? FloatingActionButton.extended(
              onPressed: () => _editOrCreate(),
              backgroundColor: SalonCrmTheme.accent,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.add_rounded),
              label: const Text('Hizmet ekle'),
            )
          : null,
    );
  }

  Widget _serviceCard(SalonCrmServiceItem s) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: CrmSoftCard(
        padding: const EdgeInsets.fromLTRB(14, 12, 8, 12),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: s.isActive
                    ? SalonCrmTheme.accentSoft
                    : SalonCrmTheme.line,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                Icons.content_cut_rounded,
                color: s.isActive ? SalonCrmTheme.ink : SalonCrmTheme.muted,
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    s.name,
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 15,
                      color: s.isActive
                          ? SalonCrmTheme.ink
                          : SalonCrmTheme.muted,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${s.durationMinutes} dk · ${s.price.toStringAsFixed(0)} ₺',
                    style: SalonCrmTheme.caption,
                  ),
                ],
              ),
            ),
            if (_canWrite) ...[
              IconButton(
                tooltip: 'Düzenle',
                onPressed: () => _editOrCreate(item: s),
                icon: const Icon(Icons.edit_rounded, size: 20),
                color: SalonCrmTheme.inkSoft,
              ),
              IconButton(
                tooltip: s.isActive ? 'Pasif yap' : 'Aktif yap',
                onPressed: () => _toggleActive(s),
                icon: Icon(
                  s.isActive
                      ? Icons.visibility_off_outlined
                      : Icons.visibility_outlined,
                  size: 20,
                ),
                color: SalonCrmTheme.inkSoft,
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _ServiceEditorDialog extends StatefulWidget {
  const _ServiceEditorDialog({this.item});

  final SalonCrmServiceItem? item;

  @override
  State<_ServiceEditorDialog> createState() => _ServiceEditorDialogState();
}

class _ServiceEditorDialogState extends State<_ServiceEditorDialog> {
  late final TextEditingController _nameCtrl;
  late final TextEditingController _priceCtrl;
  late final TextEditingController _durationCtrl;

  @override
  void initState() {
    super.initState();
    final item = widget.item;
    _nameCtrl = TextEditingController(text: item?.name ?? '');
    _priceCtrl = TextEditingController(
      text: item != null ? item.price.toStringAsFixed(0) : '',
    );
    _durationCtrl = TextEditingController(
      text: '${item?.durationMinutes ?? 30}',
    );
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _priceCtrl.dispose();
    _durationCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.item != null;
    return AlertDialog(
      title: Text(isEdit ? 'Hizmeti düzenle' : 'Hizmet ekle'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: _nameCtrl,
              autofocus: true,
              decoration: const InputDecoration(
                labelText: 'Hizmet adı',
                hintText: 'Örn. Saç kesimi',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _durationCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Süre (dk)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _priceCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Fiyat (₺)',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Vazgeç'),
        ),
        TextButton(
          onPressed: () {
            Navigator.pop(context, {
              'name': _nameCtrl.text.trim(),
              'price': _priceCtrl.text.trim(),
              'duration': _durationCtrl.text.trim(),
            });
          },
          child: Text(isEdit ? 'Kaydet' : 'Ekle'),
        ),
      ],
    );
  }
}
