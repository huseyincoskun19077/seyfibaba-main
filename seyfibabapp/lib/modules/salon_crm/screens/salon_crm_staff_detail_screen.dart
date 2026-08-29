import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmStaffDetailScreen extends StatefulWidget {
  const SalonCrmStaffDetailScreen({
    super.key,
    required this.staffId,
    this.isOwner = true,
  });

  final int staffId;
  final bool isOwner;

  @override
  State<SalonCrmStaffDetailScreen> createState() =>
      _SalonCrmStaffDetailScreenState();
}

class _SalonCrmStaffDetailScreenState extends State<SalonCrmStaffDetailScreen> {
  final _service = SalonCrmService();
  bool _loading = true;
  String? _error;
  SalonCrmStaffDetail? _data;
  String _payType = 'percent';
  String _payPeriod = 'monthly';
  final _percentCtrl = TextEditingController();
  final _salaryCtrl = TextEditingController();
  final _payAmountCtrl = TextEditingController();
  List<_StaffServiceDraft> _services = [];
  List<_HourDraft> _hours = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _percentCtrl.dispose();
    _salaryCtrl.dispose();
    _payAmountCtrl.dispose();
    for (final s in _services) {
      s.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      final data = await _service.fetchStaffDetail(
        token,
        staffId: widget.staffId,
      );
      if (!mounted) return;
      for (final s in _services) {
        s.dispose();
      }
      setState(() {
        _data = data;
        _payType = data.staff.payType == 'net' ? 'net' : 'percent';
        _payPeriod = data.staff.payPeriod == 'daily' ? 'daily' : 'monthly';
        _percentCtrl.text = data.staff.commissionPercent.toStringAsFixed(0);
        _salaryCtrl.text = data.staff.salaryAmount.toStringAsFixed(0);
        _payAmountCtrl.text = data.suggestedAmount > 0
            ? data.suggestedAmount.toStringAsFixed(0)
            : '';
        _services = data.staff.services
            .map((e) => _StaffServiceDraft.fromItem(e))
            .toList();
        _hours = [
          for (final h in (data.hours.isEmpty
              ? SalonCrmStaffHourItem.defaults()
              : data.hours))
            _HourDraft.fromItem(h),
        ];
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

  Future<void> _savePay() async {
    Utils.loadingDialog(context);
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      await _service.updateStaff(
        token: token,
        staffId: widget.staffId,
        payType: _payType,
        payPeriod: _payPeriod,
        commissionPercent: _payType == 'percent'
            ? double.tryParse(_percentCtrl.text.replaceAll(',', '.')) ?? 0
            : 0,
        salaryAmount: _payType == 'net'
            ? double.tryParse(_salaryCtrl.text.replaceAll(',', '.')) ?? 0
            : 0,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      await _load();
      if (!mounted) return;
      Utils.showSnackBar(context, 'Maaş ayarı kaydedildi');
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  Future<void> _saveServices() async {
    final rows = _services
        .where((s) => s.nameCtrl.text.trim().isNotEmpty)
        .map(
          (s) => {
            if (s.serviceId != null) 'service_id': s.serviceId,
            'name': s.nameCtrl.text.trim(),
            'price': double.tryParse(s.priceCtrl.text.replaceAll(',', '.')) ?? 0,
            'duration_minutes':
                int.tryParse(s.durationCtrl.text) ?? 30,
          },
        )
        .toList();
    Utils.loadingDialog(context);
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      await _service.syncStaffServices(
        token: token,
        staffId: widget.staffId,
        services: rows,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      await _load();
      if (!mounted) return;
      Utils.showSnackBar(context, 'Hizmetler kaydedildi');
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  Future<void> _paySalary() async {
    final amount = double.tryParse(_payAmountCtrl.text.replaceAll(',', '.'));
    Utils.loadingDialog(context);
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      await _service.createSalaryPayment(
        token: token,
        staffId: widget.staffId,
        periodKey: _data?.periodKey,
        amount: amount,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      await _load();
      if (!mounted) return;
      Utils.showSnackBar(
        context,
        'Ödeme kaydı açıldı. Personel onaylayınca “Maaş ödendi” yazılır.',
      );
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  Future<void> _saveHours() async {
    Utils.loadingDialog(context);
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      await _service.syncStaffHours(
        token: token,
        staffId: widget.staffId,
        hours: [
          for (final h in _hours)
            {
              'weekday': h.weekday,
              'start_time': h.startLabel,
              'end_time': h.endLabel,
              'is_off': h.isOff,
            },
        ],
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      await _load();
      if (!mounted) return;
      Utils.showSnackBar(context, 'Çalışma saatleri kaydedildi');
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  Future<void> _removeStaff() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Personeli kaldır'),
        content: const Text(
          'Personel listeden çıkarılır ve giriş yapamaz. Geçmiş randevular durur.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Vazgeç'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Kaldır'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    Utils.loadingDialog(context);
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      await _service.deactivateStaff(token: token, staffId: widget.staffId);
      if (!mounted) return;
      Utils.closeDialog(context);
      Navigator.pop(context);
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  Future<void> _pickHour(int index, {required bool start}) async {
    final hour = _hours[index];
    final picked = await salonCrmPickTime(
      context,
      initial: start ? hour.start : hour.end,
    );
    if (picked == null || !mounted) return;
    setState(() {
      if (start) {
        hour.start = picked;
      } else {
        hour.end = picked;
      }
    });
  }

  Future<void> _confirm(SalonCrmSalaryPaymentItem payment) async {
    Utils.loadingDialog(context);
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      final updated = await _service.confirmSalaryPayment(
        token: token,
        paymentId: payment.id,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      await _load();
      if (!mounted) return;
      Utils.showSnackBar(
        context,
        updated.isPaid ? 'Maaş ödendi' : updated.statusLabel,
      );
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  void _addService() {
    setState(() {
      _services.add(_StaffServiceDraft.empty());
    });
  }

  @override
  Widget build(BuildContext context) {
    final staff = _data?.staff;
    return CrmScaffold(
      title: staff?.name ?? (widget.isOwner ? 'Personel' : 'Maaşım'),
      actions: staff == null || !widget.isOwner
          ? null
          : [
              IconButton(
                tooltip: 'Fotoğraf',
                onPressed: () => Navigator.pushNamed(
                  context,
                  RouteNames.salonCrmMyPhotoScreen,
                  arguments: {
                    'staff_id': staff.id,
                    'staff_name': staff.name,
                    'photo': staff.photo,
                    'show_photo_to_customers': staff.showPhotoToCustomers,
                    'can_write': true,
                  },
                ).then((_) => _load()),
                icon: const Icon(Icons.photo_camera_outlined),
              ),
            ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : ListView(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 40),
                  children: [
                    if (staff != null) _header(staff),
                    const SizedBox(height: 16),
                    if (widget.isOwner) ...[
                      const CrmSectionLabel('Maaş'),
                      _payCard(),
                      const SizedBox(height: 16),
                      const CrmSectionLabel('Hizmetler ve ücretler'),
                      _servicesCard(),
                      const SizedBox(height: 16),
                      const CrmSectionLabel('Çalışma saatleri'),
                      _hoursCard(),
                      const SizedBox(height: 16),
                    ],
                    const CrmSectionLabel('Maaş ödemeleri'),
                    _paymentsCard(),
                    if (widget.isOwner) ...[
                      const SizedBox(height: 24),
                      OutlinedButton.icon(
                        onPressed: _removeStaff,
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.red.shade700,
                          side: BorderSide(color: Colors.red.shade200),
                        ),
                        icon: const Icon(Icons.person_off_outlined),
                        label: const Text('Personeli kaldır'),
                      ),
                    ],
                  ],
                ),
    );
  }

  Widget _header(SalonCrmStaffItem staff) {
    return CrmSoftCard(
      child: Row(
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: SalonCrmTheme.line.withValues(alpha: 0.4),
            backgroundImage:
                staff.photo != null && staff.photo!.isNotEmpty
                    ? NetworkImage(RemoteUrls.imageUrl(staff.photo!))
                    : null,
            child: staff.photo == null || staff.photo!.isEmpty
                ? const Icon(Icons.badge_outlined, color: SalonCrmTheme.muted)
                : null,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  staff.name,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 16,
                  ),
                ),
                Text(
                  '@${staff.username} · ${staff.paySummary}',
                  style: SalonCrmTheme.caption,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _payCard() {
    return CrmSoftCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Yüzdelik mi, net mi?',
            style: TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              ChoiceChip(
                label: const Text('Yüzdelik'),
                selected: _payType == 'percent',
                onSelected: widget.isOwner
                    ? (_) => setState(() => _payType = 'percent')
                    : null,
              ),
              ChoiceChip(
                label: const Text('Net tutar'),
                selected: _payType == 'net',
                onSelected: widget.isOwner
                    ? (_) => setState(() => _payType = 'net')
                    : null,
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Text(
            'Ne sıklıkla ödenir?',
            style: TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              ChoiceChip(
                label: const Text('Günlük'),
                selected: _payPeriod == 'daily',
                onSelected: widget.isOwner
                    ? (_) => setState(() => _payPeriod = 'daily')
                    : null,
              ),
              ChoiceChip(
                label: const Text('Aylık'),
                selected: _payPeriod == 'monthly',
                onSelected: widget.isOwner
                    ? (_) => setState(() => _payPeriod = 'monthly')
                    : null,
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (_payType == 'percent')
            TextField(
              controller: _percentCtrl,
              enabled: widget.isOwner,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Personel payı %',
                helperText: 'Kalanı salonun olur.',
                border: OutlineInputBorder(),
              ),
            )
          else
            TextField(
              controller: _salaryCtrl,
              enabled: widget.isOwner,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: _payPeriod == 'daily'
                    ? 'Günlük net maaş (₺)'
                    : 'Aylık net maaş (₺)',
                border: const OutlineInputBorder(),
              ),
            ),
          if (widget.isOwner) ...[
            const SizedBox(height: 12),
            CrmPrimaryButton(label: 'Maaş ayarını kaydet', onPressed: _savePay),
          ],
        ],
      ),
    );
  }

  Widget _servicesCard() {
    return CrmSoftCard(
      child: Column(
        children: [
          if (_services.isEmpty)
            const Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'Bu personelin hizmeti yok. Aşağıdan ekle.',
                style: TextStyle(color: SalonCrmTheme.muted),
              ),
            ),
          ..._services.asMap().entries.map((entry) {
            final i = entry.key;
            final s = entry.value;
            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: SalonCrmTheme.bg,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    flex: 5,
                    child: TextField(
                      controller: s.nameCtrl,
                      enabled: widget.isOwner,
                      decoration: const InputDecoration(
                        labelText: 'Hizmet',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    flex: 3,
                    child: TextField(
                      controller: s.priceCtrl,
                      enabled: widget.isOwner,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Ücret ₺',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                  if (widget.isOwner)
                    IconButton(
                      onPressed: () => setState(() {
                        _services.removeAt(i).dispose();
                      }),
                      icon: const Icon(Icons.close_rounded),
                    ),
                ],
              ),
              ),
            );
          }),
          if (widget.isOwner) ...[
            TextButton.icon(
              onPressed: _addService,
              icon: const Icon(Icons.add_rounded),
              label: const Text('Hizmet ekle'),
            ),
            CrmPrimaryButton(
              label: 'Hizmetleri kaydet',
              onPressed: _saveServices,
            ),
          ],
        ],
      ),
    );
  }

  Widget _hoursCard() {
    return CrmSoftCard(
      child: Column(
        children: [
          for (var i = 0; i < _hours.length; i++) ...[
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(_HourDraft.dayName(_hours[i].weekday)),
              subtitle: Text(
                _hours[i].isOff
                    ? 'İzin günü'
                    : '${_hours[i].startLabel} – ${_hours[i].endLabel}',
              ),
              value: !_hours[i].isOff,
              onChanged: widget.isOwner
                  ? (v) => setState(() => _hours[i].isOff = !v)
                  : null,
            ),
            if (!_hours[i].isOff && widget.isOwner)
              Row(
                children: [
                  TextButton(
                    onPressed: () => _pickHour(i, start: true),
                    child: Text('Başlangıç ${_hours[i].startLabel}'),
                  ),
                  TextButton(
                    onPressed: () => _pickHour(i, start: false),
                    child: Text('Bitiş ${_hours[i].endLabel}'),
                  ),
                ],
              ),
          ],
          if (widget.isOwner) ...[
            const SizedBox(height: 8),
            CrmPrimaryButton(
              label: 'Çalışma saatlerini kaydet',
              onPressed: _saveHours,
            ),
          ],
        ],
      ),
    );
  }

  Widget _paymentsCard() {
    final existing = _data?.existingPayment;
    final canCreate = widget.isOwner &&
        (existing == null || existing.status == 'cancelled');
    return CrmSoftCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Dönem: ${_data?.periodLabel ?? _data?.periodKey ?? '-'}',
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 4),
          Text(
            'Önerilen tutar: ${(_data?.suggestedAmount ?? 0).toStringAsFixed(0)} ₺',
            style: SalonCrmTheme.caption,
          ),
          if (canCreate) ...[
            const SizedBox(height: 10),
            TextField(
              controller: _payAmountCtrl,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Ödenecek tutar ₺',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 10),
            CrmPrimaryButton(
              label: 'Maaşı öde (patron onayı)',
              onPressed: _paySalary,
            ),
          ],
          const SizedBox(height: 12),
          if ((_data?.payments ?? []).isEmpty)
            const Text(
              'Henüz maaş kaydı yok.',
              style: TextStyle(color: SalonCrmTheme.muted),
            ),
          ...(_data?.payments ?? []).map((p) {
            final needsStaff = !p.isPaid && !p.staffConfirmed;
            final needsOwner = !p.isPaid && !p.ownerConfirmed && widget.isOwner;
            final canConfirm = !p.isPaid &&
                ((widget.isOwner && needsOwner) ||
                    (!widget.isOwner && needsStaff) ||
                    (!widget.isOwner && p.ownerConfirmed && !p.staffConfirmed) ||
                    (widget.isOwner && p.staffConfirmed && !p.ownerConfirmed));
            return ListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(
                p.isPaid ? 'Maaş ödendi' : p.statusLabel,
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: p.isPaid ? SalonCrmTheme.success : SalonCrmTheme.ink,
                ),
              ),
              subtitle: Text(
                '${p.periodKey ?? ''} · ${p.amount.toStringAsFixed(0)} ₺',
              ),
              trailing: canConfirm
                  ? TextButton(
                      onPressed: () => _confirm(p),
                      child: Text(widget.isOwner ? 'Onayla' : 'Aldım'),
                    )
                  : null,
            );
          }),
        ],
      ),
    );
  }
}

class _HourDraft {
  _HourDraft({
    required this.weekday,
    required this.isOff,
    required this.start,
    required this.end,
  });

  final int weekday;
  bool isOff;
  TimeOfDay start;
  TimeOfDay end;

  factory _HourDraft.fromItem(SalonCrmStaffHourItem item) {
    return _HourDraft(
      weekday: item.weekday,
      isOff: item.isOff,
      start: _parse(item.startTime),
      end: _parse(item.endTime),
    );
  }

  static TimeOfDay _parse(String raw) {
    final parts = raw.split(':');
    var hour = int.tryParse(parts.first) ?? 9;
    var minute = int.tryParse(parts.length > 1 ? parts[1] : '0') ?? 0;
    if (hour < 0) hour = 0;
    if (hour > 23) hour = 23;
    if (minute < 0) minute = 0;
    if (minute > 59) minute = 59;
    return TimeOfDay(hour: hour, minute: minute);
  }

  static String dayName(int weekday) {
    const names = [
      'Pazartesi',
      'Salı',
      'Çarşamba',
      'Perşembe',
      'Cuma',
      'Cumartesi',
      'Pazar',
    ];
    if (weekday < 1 || weekday > 7) return 'Gün';
    return names[weekday - 1];
  }

  String get startLabel => _fmt(start);
  String get endLabel => _fmt(end);

  static String _fmt(TimeOfDay t) =>
      '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';
}

class _StaffServiceDraft {
  _StaffServiceDraft({
    this.serviceId,
    required this.nameCtrl,
    required this.priceCtrl,
    required this.durationCtrl,
  });

  final int? serviceId;
  final TextEditingController nameCtrl;
  final TextEditingController priceCtrl;
  final TextEditingController durationCtrl;

  factory _StaffServiceDraft.empty() => _StaffServiceDraft(
        nameCtrl: TextEditingController(),
        priceCtrl: TextEditingController(),
        durationCtrl: TextEditingController(text: '30'),
      );

  factory _StaffServiceDraft.fromItem(SalonCrmServiceItem item) =>
      _StaffServiceDraft(
        serviceId: item.id,
        nameCtrl: TextEditingController(text: item.name),
        priceCtrl: TextEditingController(text: item.price.toStringAsFixed(0)),
        durationCtrl:
            TextEditingController(text: '${item.durationMinutes}'),
      );

  void dispose() {
    nameCtrl.dispose();
    priceCtrl.dispose();
    durationCtrl.dispose();
  }
}
