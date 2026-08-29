import 'package:flutter/material.dart';

import '../../../utils/utils.dart';
import '../services/salon_crm_session.dart';
import '../services/salon_crm_service.dart';
import '../widgets/salon_crm_dates.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmCustomersScreen extends StatefulWidget {
  const SalonCrmCustomersScreen({super.key, this.embedded = false});

  final bool embedded;

  @override
  State<SalonCrmCustomersScreen> createState() =>
      _SalonCrmCustomersScreenState();
}

class _SalonCrmCustomersScreenState extends State<SalonCrmCustomersScreen> {
  final _service = SalonCrmService();
  final _searchCtrl = TextEditingController();
  bool _loading = true;
  bool _canWrite = true;
  bool _isStaff = false;
  String? _error;
  List<SalonCrmCustomerItem> _items = [];
  String _crmToken = '';
  String _salonName = '';

  int get _missedCount => _items.where((c) => c.missedLast).length;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _load({String q = ''}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _crmToken = (await SalonCrmSession.token()) ?? '';
      if (_crmToken.isEmpty) throw Exception('CRM girişi gerekli');
      final session = await SalonCrmSession.read();
      final status = await _service.fetchStatus(_crmToken);
      final isStaff = (session?['role'] ?? '') == 'staff';
      final staffId = status.staff?.id;
      final list = await _service.fetchCustomers(
        _crmToken,
        q: q,
        onlyStaffId: isStaff ? staffId : null,
      );
      if (!mounted) return;
      setState(() {
        _canWrite = status.access.canWrite;
        _isStaff = isStaff;
        _salonName = status.salon?.name ?? '';
        _items = list;
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

  Future<void> _openAdd() async {
    if (!_canWrite) {
      Utils.errorSnackBar(context, 'CRM kilitli');
      return;
    }

    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: SalonCrmTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (ctx) => _AddCustomerSheet(
        token: _crmToken,
        service: _service,
      ),
    );

    if (saved == true && mounted) {
      await _load(q: _searchCtrl.text);
      if (mounted) Utils.showSnackBar(context, 'Müşteri eklendi');
    }
  }

  Future<void> _editNotes(SalonCrmCustomerItem c) async {
    if (!_canWrite) return;
    final notesCtrl = TextEditingController(text: c.notes ?? '');
    const chips = ['Sakal bırakıyor', 'Alerji var', 'Hassas cilt', 'Kısa kesim'];
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: SalonCrmTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (ctx) {
        final bottom = MediaQuery.of(ctx).viewInsets.bottom;
        return Padding(
          padding: EdgeInsets.fromLTRB(20, 12, 20, 20 + bottom),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: SalonCrmTheme.line,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Müşteri notu',
                style: SalonCrmTheme.titleMd.copyWith(fontSize: 18),
              ),
              const SizedBox(height: 6),
              Text(c.name, style: SalonCrmTheme.caption),
              const SizedBox(height: 14),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final chip in chips)
                    ActionChip(
                      label: Text(chip),
                      backgroundColor: SalonCrmTheme.bgDeep,
                      onPressed: () {
                        final cur = notesCtrl.text.trim();
                        notesCtrl.text = cur.isEmpty ? chip : '$cur, $chip';
                      },
                    ),
                ],
              ),
              const SizedBox(height: 12),
              TextField(
                controller: notesCtrl,
                maxLines: 4,
                decoration: SalonCrmTheme.field(
                  'Not',
                  hint: 'Sakal bırakıyor, alerji var…',
                ),
              ),
              const SizedBox(height: 18),
              CrmPrimaryButton(
                label: 'Kaydet',
                icon: Icons.check_rounded,
                onPressed: () async {
                  Utils.loadingDialog(ctx);
                  try {
                    await _service.updateCustomerNotes(
                      token: _crmToken,
                      id: c.id,
                      notes: notesCtrl.text.trim(),
                    );
                    if (!ctx.mounted) return;
                    Utils.closeDialog(ctx);
                    Navigator.pop(ctx, true);
                  } catch (e) {
                    if (!ctx.mounted) return;
                    Utils.closeDialog(ctx);
                    Utils.errorSnackBar(ctx, e.toString());
                  }
                },
              ),
            ],
          ),
        );
      },
    );
    notesCtrl.dispose();
    if (saved == true && mounted) {
      await _load(q: _searchCtrl.text);
    }
  }

  Future<void> _openCustomer(SalonCrmCustomerItem c) async {
    final last = c.lastStartsAt;
    final lastText = last == null
        ? 'Son randevu kaydı yok.'
        : 'Son randevu: ${SalonCrmDates.full(last.toLocal())} · ${c.lastServiceName ?? ''}';
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: SalonCrmTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (ctx) {
        return Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: SalonCrmTheme.line,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  _CustomerAvatar(name: c.name, size: 48),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          c.name,
                          style: SalonCrmTheme.titleMd.copyWith(fontSize: 18),
                        ),
                        Text(c.phone, style: SalonCrmTheme.body),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              if (c.missedLast)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF7ED),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: const Color(0xFFFDBA74).withValues(alpha: 0.5),
                    ),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.warning_amber_rounded,
                          color: Color(0xFFD97706), size: 20),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Önceki randevuya gelmedi',
                          style: TextStyle(
                            color: Color(0xFFB45309),
                            fontWeight: FontWeight.w800,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              if (c.missedLast) const SizedBox(height: 12),
              Text(lastText, style: SalonCrmTheme.caption),
              if (c.noShowCount > 0) ...[
                const SizedBox(height: 6),
                Text(
                  'Gelmediği randevu: ${c.noShowCount}',
                  style: SalonCrmTheme.caption,
                ),
              ],
              if (c.notes != null && c.notes!.isNotEmpty) ...[
                const SizedBox(height: 12),
                const CrmSectionLabel('Müşteri notu'),
                Text(c.notes!, style: SalonCrmTheme.body),
              ],
              if (_canWrite) ...[
                const SizedBox(height: 18),
                if (salonCrmHasWhatsApp(c.phone))
                  CrmPrimaryButton(
                    label: 'WhatsApp',
                    icon: Icons.chat_rounded,
                    onPressed: () {
                      salonCrmOpenWhatsApp(
                        ctx,
                        phone: c.phone,
                        text: salonCrmReminderMessage(
                          customerName: c.name,
                          salonName: _salonName,
                          startsAt: c.lastStartsAt,
                        ),
                      );
                    },
                  ),
                if (salonCrmHasWhatsApp(c.phone)) const SizedBox(height: 10),
                OutlinedButton.icon(
                  onPressed: () {
                    Navigator.pop(ctx);
                    _editNotes(c);
                  },
                  icon: const Icon(Icons.edit_note_rounded),
                  label: const Text('Notu düzenle'),
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size.fromHeight(48),
                    shape: RoundedRectangleBorder(
                      borderRadius:
                          BorderRadius.circular(SalonCrmTheme.radiusSm),
                    ),
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: _isStaff ? 'Müşterilerim' : 'Müşteriler',
      showBack: !widget.embedded,
      actions: [
        IconButton(
          onPressed: _loading ? null : () => _load(q: _searchCtrl.text),
          icon: const Icon(Icons.refresh_rounded, size: 22),
          color: SalonCrmTheme.ink,
        ),
      ],
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
            child: TextField(
              controller: _searchCtrl,
              decoration: SalonCrmTheme.field(
                'Ad veya telefon ara',
                prefix: const Icon(Icons.search_rounded, size: 22),
              ),
              onSubmitted: (v) => _load(q: v),
              textInputAction: TextInputAction.search,
            ),
          ),
          if (!_canWrite) _lockBanner(),
          if (!_loading && _error == null) ...[
            _summaryHero(),
            if (_canWrite) _addButton(),
          ],
          Expanded(child: _body()),
        ],
      ),
    );
  }

  Widget _lockBanner() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: CrmSoftCard(
        color: SalonCrmTheme.dangerSoft,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: const Row(
          children: [
            Icon(Icons.lock_rounded, color: SalonCrmTheme.danger, size: 20),
            SizedBox(width: 10),
            Expanded(
              child: Text(
                'CRM kilitli — yeni müşteri eklenemez.',
                style: TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w600,
                  color: SalonCrmTheme.danger,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _summaryHero() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      child: CrmSoftCard(
        padding: const EdgeInsets.all(18),
        color: const Color(0xFFEFF6FF),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: const Color(0xFFDBEAFE),
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(
                Icons.people_rounded,
                color: Color(0xFF2563EB),
                size: 22,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Müşteri listesi',
                    style: SalonCrmTheme.caption.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  Text(
                    '${_items.length}',
                    style: const TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.6,
                      color: Color(0xFF2563EB),
                    ),
                  ),
                ],
              ),
            ),
            if (_missedCount > 0)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF7ED),
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(
                    color: const Color(0xFFFDBA74).withValues(alpha: 0.6),
                  ),
                ),
                child: Text(
                  '$_missedCount gelmedi',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFFD97706),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _addButton() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      child: Material(
        color: SalonCrmTheme.accentSoft,
        borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
        child: InkWell(
          onTap: _openAdd,
          borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 14),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
              border: Border.all(
                color: SalonCrmTheme.accent.withValues(alpha: 0.5),
              ),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.person_add_alt_1_rounded,
                    size: 18, color: SalonCrmTheme.ink),
                SizedBox(width: 8),
                Text(
                  'Müşteri ekle',
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 14.5,
                    color: SalonCrmTheme.ink,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _body() {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(color: SalonCrmTheme.accent),
      );
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline_rounded,
                  size: 40, color: SalonCrmTheme.danger),
              const SizedBox(height: 8),
              Text(_error!, textAlign: TextAlign.center, style: SalonCrmTheme.body),
              const SizedBox(height: 12),
              TextButton(onPressed: () => _load(q: _searchCtrl.text),
                  child: const Text('Tekrar dene')),
            ],
          ),
        ),
      );
    }
    if (_items.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: SalonCrmTheme.bgDeep,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Icon(Icons.people_outline_rounded,
                    size: 32, color: SalonCrmTheme.muted),
              ),
              const SizedBox(height: 14),
              Text(
                'Henüz müşteri yok',
                style: SalonCrmTheme.titleMd.copyWith(fontSize: 17),
              ),
              const SizedBox(height: 6),
              Text(
                _canWrite
                    ? 'Müşteri ekleyerek randevu ve notları takip edin.'
                    : 'Kayıtlı müşteri bulunmuyor.',
                textAlign: TextAlign.center,
                style: SalonCrmTheme.caption,
              ),
              if (_canWrite) ...[
                const SizedBox(height: 18),
                SizedBox(
                  width: 200,
                  child: CrmPrimaryButton(
                    label: 'Müşteri ekle',
                    icon: Icons.person_add_alt_1_rounded,
                    onPressed: _openAdd,
                  ),
                ),
              ],
            ],
          ),
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
      itemCount: _items.length + 1,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, i) {
        if (i == 0) {
          return const Padding(
            padding: EdgeInsets.only(bottom: 4, top: 2),
            child: CrmSectionLabel('Kayıtlar'),
          );
        }
        return _CustomerCard(
          customer: _items[i - 1],
          onTap: () => _openCustomer(_items[i - 1]),
        );
      },
    );
  }
}

class _CustomerAvatar extends StatelessWidget {
  const _CustomerAvatar({required this.name, this.size = 42});

  final String name;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFBFDBFE), Color(0xFF93C5FD)],
        ),
        borderRadius: BorderRadius.circular(size * 0.32),
      ),
      child: Text(
        name.isNotEmpty ? name[0].toUpperCase() : 'M',
        style: TextStyle(
          fontWeight: FontWeight.w800,
          fontSize: size * 0.38,
          color: const Color(0xFF1E40AF),
        ),
      ),
    );
  }
}

class _CustomerCard extends StatelessWidget {
  const _CustomerCard({required this.customer, required this.onTap});

  final SalonCrmCustomerItem customer;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return CrmSoftCard(
      padding: const EdgeInsets.all(14),
      onTap: onTap,
      color: customer.missedLast
          ? const Color(0xFFFFFBEB).withValues(alpha: 0.6)
          : null,
      child: Row(
        children: [
          _CustomerAvatar(name: customer.name),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  customer.name,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                    color: SalonCrmTheme.ink,
                  ),
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.phone_rounded,
                        size: 13, color: SalonCrmTheme.muted),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        customer.phone,
                        style: SalonCrmTheme.caption,
                      ),
                    ),
                  ],
                ),
                if (customer.missedLast) ...[
                  const SizedBox(height: 6),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF7ED),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: const Text(
                      'Gelmedi',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFFD97706),
                      ),
                    ),
                  ),
                ] else if (customer.notes != null &&
                    customer.notes!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    customer.notes!,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: SalonCrmTheme.caption,
                  ),
                ],
              ],
            ),
          ),
          const Icon(Icons.chevron_right_rounded, color: SalonCrmTheme.muted),
        ],
      ),
    );
  }
}

class _AddCustomerSheet extends StatefulWidget {
  const _AddCustomerSheet({
    required this.token,
    required this.service,
  });

  final String token;
  final SalonCrmService service;

  @override
  State<_AddCustomerSheet> createState() => _AddCustomerSheetState();
}

class _AddCustomerSheetState extends State<_AddCustomerSheet> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  bool _saving = false;

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_saving) return;
    final name = _nameCtrl.text.trim();
    final phone = _phoneCtrl.text.trim();
    if (name.isEmpty || phone.isEmpty) {
      Utils.errorSnackBar(context, 'Ad ve telefon zorunlu');
      return;
    }

    setState(() => _saving = true);
    FocusScope.of(context).unfocus();
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      useRootNavigator: true,
      builder: (_) => const Center(
        child: CircularProgressIndicator(color: SalonCrmTheme.accent),
      ),
    );

    try {
      await widget.service.createCustomer(
        token: widget.token,
        name: name,
        phone: phone,
        notes: _notesCtrl.text.trim(),
      );
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      setState(() => _saving = false);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 12, 20, 20 + bottom),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: SalonCrmTheme.line,
                borderRadius: BorderRadius.circular(999),
              ),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: SalonCrmTheme.accentSoft,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.person_add_alt_1_rounded,
                  color: SalonCrmTheme.ink,
                ),
              ),
              const SizedBox(width: 12),
              Text(
                'Müşteri ekle',
                style: SalonCrmTheme.titleMd.copyWith(fontSize: 18),
              ),
            ],
          ),
          const SizedBox(height: 18),
          TextField(
            controller: _nameCtrl,
            textInputAction: TextInputAction.next,
            decoration: SalonCrmTheme.field('Ad soyad'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _phoneCtrl,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.next,
            decoration: SalonCrmTheme.field('Telefon'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _notesCtrl,
            maxLines: 2,
            decoration: SalonCrmTheme.field(
              'Not (ör. sakal bırakıyor, alerji var)',
            ),
          ),
          const SizedBox(height: 18),
          CrmPrimaryButton(
            label: _saving ? 'Kaydediliyor...' : 'Kaydet',
            icon: Icons.check_rounded,
            onPressed: _saving ? null : _save,
          ),
        ],
      ),
    );
  }
}
