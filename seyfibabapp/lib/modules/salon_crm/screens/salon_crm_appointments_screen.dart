import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../utils/utils.dart';
import '../services/salon_crm_session.dart';
import '../services/salon_crm_service.dart';
import '../widgets/salon_crm_dates.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

String salonCrmAssigneeLabel(SalonCrmAppointmentItem item) {
  final name = item.staffName?.trim() ?? '';
  return name.isEmpty ? 'Ben' : name;
}

bool salonCrmCanManageAppointmentPrice({
  required SalonCrmAppointmentItem item,
  required String viewerRole,
  int? viewerStaffId,
}) {
  if (item.isBlock) return false;
  if (viewerRole == 'staff') {
    return item.staffId != null && item.staffId == viewerStaffId;
  }
  if (viewerRole == 'owner') {
    return item.staffId == null;
  }
  return false;
}

Future<Map<String, dynamic>?> showSalonCrmCompleteDialog(
  BuildContext context,
  SalonCrmAppointmentItem item,
) {
  if (item.isBlock) return Future.value(null);
  final priceCtrl = TextEditingController(
    text: item.price > 0 ? item.price.toStringAsFixed(0) : '',
  );
  String method = 'cash';
  return showDialog<Map<String, dynamic>>(
    context: context,
    builder: (ctx) {
      return StatefulBuilder(
        builder: (ctx, setLocal) {
          final price = double.tryParse(
                priceCtrl.text.replaceAll(',', '.'),
              ) ??
              0;
          final percent = item.staffCommissionPercent;
          final staffShare = price * percent / 100;
          final ownerShare = price - staffShare;
          return AlertDialog(
            title: Text(
              item.isUnpaid ? 'Veresiye tahsil et' : 'Ne kadar ücret aldın?',
            ),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.customerName,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: priceCtrl,
                    autofocus: true,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Alınan ücret (₺)',
                      hintText: 'Örn. 350',
                      border: OutlineInputBorder(),
                    ),
                    onChanged: (_) => setLocal(() {}),
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'Nasıl alındı?',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final e in [
                        ('cash', 'Nakit'),
                        ('card', 'Kart'),
                        ('iban', 'IBAN'),
                        if (!item.isUnpaid) ('credit', 'Veresiye'),
                      ])
                        ChoiceChip(
                          label: Text(e.$2),
                          selected: method == e.$1,
                          onSelected: (_) => setLocal(() => method = e.$1),
                        ),
                    ],
                  ),
                  if (price > 0 && percent > 0) ...[
                    const SizedBox(height: 12),
                    Text(
                      'Personel (%${percent.toStringAsFixed(0)}): ${staffShare.toStringAsFixed(0)} ₺\n'
                      'Salon payı: ${ownerShare.toStringAsFixed(0)} ₺',
                      style: SalonCrmTheme.caption,
                    ),
                  ],
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('Vazgeç'),
              ),
              TextButton(
                onPressed: price <= 0
                    ? null
                    : () {
                        Navigator.pop(ctx, {
                          'price': price,
                          'method': method,
                        });
                      },
                child: Text(item.isUnpaid ? 'Tahsil et' : 'Tamamla'),
              ),
            ],
          );
        },
      );
    },
  );
}

Future<String?> showSalonCrmAppointmentDetail(
  BuildContext context, {
  required SalonCrmAppointmentItem item,
  required SalonCrmService service,
  required String token,
  required bool canWrite,
  required String salonName,
  required String viewerRole,
  int? viewerStaffId,
}) {
  final canManagePrice = salonCrmCanManageAppointmentPrice(
    item: item,
    viewerRole: viewerRole,
    viewerStaffId: viewerStaffId,
  );
  final isStaffAppointment = item.staffId != null;
  return showModalBottomSheet<String>(
    context: context,
    backgroundColor: SalonCrmTheme.surface,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (ctx) {
      final time = item.startsAt == null
          ? '--:--'
          : DateFormat('HH:mm').format(item.startsAt!.toLocal());
      final date = item.startsAt == null
          ? ''
          : SalonCrmDates.full(item.startsAt!.toLocal());

      return SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Handle bar
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: SalonCrmTheme.line,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Başlık
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: item.isBlock
                          ? SalonCrmTheme.dangerSoft
                          : (item.status == 'pending'
                              ? const Color(0xFFFFF7ED)
                              : (item.status == 'completed'
                                  ? SalonCrmTheme.successSoft
                                  : const Color(0xFFEFF6FF))),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(
                      item.isBlock
                          ? Icons.block_rounded
                          : (item.status == 'completed'
                              ? Icons.check_circle_rounded
                              : (item.status == 'pending'
                                  ? Icons.schedule_rounded
                                  : Icons.event_rounded)),
                      color: item.isBlock
                          ? SalonCrmTheme.danger
                          : (item.status == 'completed'
                              ? SalonCrmTheme.success
                              : (item.status == 'pending'
                                  ? const Color(0xFFD97706)
                                  : const Color(0xFF3B82F6))),
                      size: 22,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.isBlock
                              ? 'Mola / kapalı saat'
                              : item.customerName,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        Text(
                          [
                            if (date.isNotEmpty) date,
                            time,
                            '${item.durationMinutes} dk',
                          ].join(' · '),
                          style: SalonCrmTheme.caption,
                        ),
                      ],
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              // Bilgi satırları
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: SalonCrmTheme.bg,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Column(
                  children: [
                    if (!item.isBlock && item.serviceName.isNotEmpty)
                      _DetailRow(
                          icon: Icons.content_cut_rounded,
                          label: 'Hizmet',
                          value: item.serviceName),
                    _DetailRow(
                        icon: Icons.person_rounded,
                        label: 'Kim',
                        value: salonCrmAssigneeLabel(item)),
                    if (!item.isBlock &&
                        item.customerPhone.isNotEmpty &&
                        item.customerPhone != '-')
                      _DetailRow(
                          icon: Icons.phone_rounded,
                          label: 'Telefon',
                          value: item.customerPhone),
                    _DetailRow(
                      icon: Icons.info_outline_rounded,
                      label: 'Durum',
                      value: item.isBlock
                          ? 'Kapalı saat'
                          : (item.status == 'pending'
                              ? 'Onay bekliyor'
                              : (item.status == 'completed'
                                  ? 'Tamamlandı'
                                  : (item.status == 'scheduled'
                                      ? 'Planlandı'
                                      : item.status))),
                      valueColor: item.isBlock
                          ? SalonCrmTheme.danger
                          : (item.status == 'pending'
                              ? const Color(0xFFD97706)
                              : (item.status == 'completed'
                                  ? SalonCrmTheme.success
                                  : SalonCrmTheme.ink)),
                    ),
                    if (!item.isBlock && item.price > 0)
                      _DetailRow(
                          icon: Icons.payments_rounded,
                          label: 'Ücret',
                          value: '${item.price.toStringAsFixed(0)} ₺'),
                    if (!item.isBlock &&
                        isStaffAppointment &&
                        viewerRole == 'owner' &&
                        item.status == 'completed' &&
                        item.price > 0)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(
                          'Ücreti personel girdi. Patron düzenleyemez.',
                          style: SalonCrmTheme.caption.copyWith(
                            color: SalonCrmTheme.muted,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                  ],
                ),
              ),

              if (canWrite) ...[
                const SizedBox(height: 16),

                // Ana aksiyon butonları
                if (item.status == 'pending' && !item.isBlock)
                  CrmPrimaryButton(
                    label: 'Talebi onayla',
                    icon: Icons.check_rounded,
                    onPressed: () async {
                      Utils.loadingDialog(ctx);
                      try {
                        await service.updateAppointmentStatus(
                          token: token,
                          id: item.id,
                          status: 'scheduled',
                        );
                        if (!ctx.mounted) return;
                        Utils.closeDialog(ctx);
                        Navigator.pop(ctx, 'reload');
                      } catch (e) {
                        if (!ctx.mounted) return;
                        Utils.closeDialog(ctx);
                        Utils.errorSnackBar(ctx, e.toString());
                      }
                    },
                  ),

                if (item.status == 'scheduled' && !item.isBlock && canManagePrice)
                  CrmPrimaryButton(
                    label: item.price > 0
                        ? 'İşi bitir · ${item.price.toStringAsFixed(0)} ₺'
                        : 'İşi bitir',
                    icon: Icons.check_circle_rounded,
                    onPressed: () async {
                      final result =
                          await showSalonCrmCompleteDialog(ctx, item);
                      if (result == null || !ctx.mounted) return;
                      Utils.loadingDialog(ctx);
                      try {
                        await service.updateAppointmentStatus(
                          token: token,
                          id: item.id,
                          status: 'completed',
                          price: result['price'] as double?,
                          paymentMethod: result['method'] as String?,
                        );
                        if (!ctx.mounted) return;
                        Utils.closeDialog(ctx);
                        Navigator.pop(ctx, 'reload');
                      } catch (e) {
                        if (!ctx.mounted) return;
                        Utils.closeDialog(ctx);
                        Utils.errorSnackBar(ctx, e.toString());
                      }
                    },
                  ),

                if (item.isUnpaid && !item.isBlock && canManagePrice)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: CrmPrimaryButton(
                      label: 'Veresiye tahsil et',
                      icon: Icons.payments_rounded,
                      onPressed: () async {
                        final result =
                            await showSalonCrmCompleteDialog(ctx, item);
                        if (result == null || !ctx.mounted) return;
                        Utils.loadingDialog(ctx);
                        try {
                          await service.updateAppointmentStatus(
                            token: token,
                            id: item.id,
                            status: 'completed',
                            price: result['price'] as double?,
                            paymentMethod: result['method'] as String?,
                          );
                          if (!ctx.mounted) return;
                          Utils.closeDialog(ctx);
                          Navigator.pop(ctx, 'reload');
                        } catch (e) {
                          if (!ctx.mounted) return;
                          Utils.closeDialog(ctx);
                          Utils.errorSnackBar(ctx, e.toString());
                        }
                      },
                    ),
                  ),

                if (item.status == 'completed' &&
                    !item.isBlock &&
                    canManagePrice &&
                    item.price > 0)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: CrmPrimaryButton(
                      label: 'Ücreti düzelt',
                      icon: Icons.edit_rounded,
                      onPressed: () async {
                        final result =
                            await showSalonCrmCompleteDialog(ctx, item);
                        if (result == null || !ctx.mounted) return;
                        Utils.loadingDialog(ctx);
                        try {
                          await service.updateAppointmentStatus(
                            token: token,
                            id: item.id,
                            status: 'completed',
                            price: result['price'] as double?,
                            paymentMethod: result['method'] as String?,
                          );
                          if (!ctx.mounted) return;
                          Utils.closeDialog(ctx);
                          Navigator.pop(ctx, 'reload');
                        } catch (e) {
                          if (!ctx.mounted) return;
                          Utils.closeDialog(ctx);
                          Utils.errorSnackBar(ctx, e.toString());
                        }
                      },
                    ),
                  ),

                if (item.status == 'scheduled' &&
                    !item.isBlock &&
                    isStaffAppointment &&
                    viewerRole == 'owner')
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Text(
                      'Bu randevunun ücretini personel girecek.',
                      textAlign: TextAlign.center,
                      style: SalonCrmTheme.caption.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),

                const SizedBox(height: 8),

                // İkincil aksiyonlar
                Row(
                  children: [
                    if (item.status == 'scheduled' || item.status == 'pending')
                      Expanded(
                        child: _SecondaryAction(
                          icon: Icons.schedule_rounded,
                          label: 'Saati kaydır',
                          onTap: () => Navigator.pop(ctx, 'reschedule'),
                        ),
                      ),
                    if (!item.isBlock &&
                        salonCrmHasWhatsApp(item.customerPhone) &&
                        (item.status == 'scheduled' ||
                            item.status == 'pending'))
                      Expanded(
                        child: _SecondaryAction(
                          icon: Icons.message_rounded,
                          label: 'WhatsApp',
                          onTap: () {
                            salonCrmOpenWhatsApp(
                              ctx,
                              phone: item.customerPhone,
                              text: salonCrmReminderMessage(
                                customerName: item.customerName,
                                salonName: salonName,
                                startsAt: item.startsAt,
                              ),
                            );
                          },
                        ),
                      ),
                    if (item.status == 'pending' ||
                        item.status == 'scheduled' ||
                        item.isBlock)
                      Expanded(
                        child: _SecondaryAction(
                          icon: Icons.close_rounded,
                          label: item.isBlock ? 'Molayı kaldır' : 'İptal et',
                          color: SalonCrmTheme.danger,
                          onTap: () async {
                            Utils.loadingDialog(ctx);
                            try {
                              await service.updateAppointmentStatus(
                                token: token,
                                id: item.id,
                                status: 'cancelled',
                              );
                              if (!ctx.mounted) return;
                              Utils.closeDialog(ctx);
                              Navigator.pop(ctx, 'reload');
                            } catch (e) {
                              if (!ctx.mounted) return;
                              Utils.closeDialog(ctx);
                              Utils.errorSnackBar(ctx, e.toString());
                            }
                          },
                        ),
                      ),
                  ],
                ),
              ],
            ],
          ),
        ),
      );
    },
  );
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
    this.valueColor,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 16, color: SalonCrmTheme.muted),
          const SizedBox(width: 8),
          SizedBox(
            width: 70,
            child: Text(label, style: SalonCrmTheme.caption),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 14,
                color: valueColor ?? SalonCrmTheme.ink,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SecondaryAction extends StatelessWidget {
  const _SecondaryAction({
    required this.icon,
    required this.label,
    required this.onTap,
    this.color,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final c = color ?? SalonCrmTheme.inkSoft;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Ink(
            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
            decoration: BoxDecoration(
              color: (color ?? SalonCrmTheme.ink).withValues(alpha: 0.04),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: SalonCrmTheme.line),
            ),
            child: Column(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: SalonCrmTheme.surface,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: SalonCrmTheme.line),
                  ),
                  child: Icon(icon, size: 20, color: c),
                ),
                const SizedBox(height: 6),
                Text(
                  label,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 11.5,
                    fontWeight: FontWeight.w700,
                    color: c,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class SalonCrmAppointmentsScreen extends StatefulWidget {
  const SalonCrmAppointmentsScreen({super.key, this.embedded = false});

  final bool embedded;

  @override
  State<SalonCrmAppointmentsScreen> createState() =>
      _SalonCrmAppointmentsScreenState();
}

class _SalonCrmAppointmentsScreenState
    extends State<SalonCrmAppointmentsScreen> {
  final _service = SalonCrmService();
  DateTime _day = SalonCrmDates.today();
  DateTime _calendarMonth =
      DateTime(SalonCrmDates.now().year, SalonCrmDates.now().month);
  bool _calendarExpanded = false;
  bool _loading = true;
  String? _error;
  bool _canWrite = true;
  List<SalonCrmAppointmentItem> _items = [];
  List<SalonCrmDayOccupancy> _occupancy = [];
  SalonCrmDaySummary? _daySummary;
  List<SalonCrmStaffItem> _staff = [];
  String _crmToken = '';
  String _salonName = '';
  String _role = 'owner';
  int? _viewerStaffId;
  String _displayName = '';
  String _filterKey = 'all';
  int _openHour = 9;
  int _closeHour = 21;

  String get _dateKey => SalonCrmDates.dateKey(_day);

  List<SalonCrmAppointmentItem> get _filteredItems {
    if (_role == 'staff' && _viewerStaffId != null) {
      return _items.where((e) => e.staffId == _viewerStaffId).toList();
    }
    if (_filterKey == 'owner') {
      return _items.where((e) => e.staffId == null).toList();
    }
    if (_filterKey.startsWith('staff_')) {
      final id = int.tryParse(_filterKey.replaceFirst('staff_', ''));
      if (id != null) {
        return _items.where((e) => e.staffId == id).toList();
      }
    }
    return _items;
  }

  int get _activeAppointmentCount => _filteredItems
      .where((e) =>
          !e.isBlock && e.status != 'cancelled' && e.status != 'no_show')
      .length;

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
      _crmToken = (await SalonCrmSession.token()) ?? '';
      if (_crmToken.isEmpty) throw Exception('CRM girişi gerekli');
      final session = await SalonCrmSession.read();
      final status = await _service.fetchStatus(_crmToken);
      final result =
          await _service.fetchAppointments(_crmToken, date: _dateKey);
      var staff = <SalonCrmStaffItem>[];
      final role = session?['role'] ?? 'owner';
      if (role == 'owner') {
        staff = await _service.fetchStaff(_crmToken);
      }
      if (!mounted) return;
      setState(() {
        _items = result.appointments;
        _occupancy = result.occupancy;
        _daySummary = result.daySummary;
        _canWrite = status.access.canWrite;
        _salonName = status.salon?.name ?? '';
        _role = role;
        _viewerStaffId = status.staff?.id;
        _displayName = session?['display_name'] ?? status.staff?.name ?? '';
        _staff = staff.where((s) => s.isActive).toList();
        _openHour = status.salon?.openHour ?? 9;
        _closeHour = status.salon?.closeHour ?? 21;
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

  Future<void> _openCreate({
    int hour = 10,
    SalonCrmAppointmentItem? existing,
  }) async {
    if (!_canWrite) {
      Utils.errorSnackBar(context, 'CRM kilitli');
      return;
    }
    final created = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: SalonCrmTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => SalonCrmCreateAppointmentSheet(
        service: _service,
        token: _crmToken,
        hostContext: context,
        initialDay: existing?.startsAt?.toLocal() ?? _day,
        initialHour: existing?.startsAt?.toLocal().hour ?? hour,
        existing: existing,
        forceStaffId: _role == 'staff' ? _viewerStaffId : null,
      ),
    );
    if (created == true && mounted) await _load();
  }

  Future<void> _openDetail(SalonCrmAppointmentItem item) async {
    final result = await showSalonCrmAppointmentDetail(
      context,
      item: item,
      service: _service,
      token: _crmToken,
      canWrite: _canWrite,
      salonName: _salonName,
      viewerRole: _role,
      viewerStaffId: _viewerStaffId,
    );
    if (!mounted) return;
    if (result == 'reload') {
      await _load();
    } else if (result == 'reschedule') {
      await _openCreate(existing: item);
    }
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'pending':
        return 'Bekliyor';
      case 'completed':
        return 'Bitti';
      case 'cancelled':
        return 'İptal';
      case 'no_show':
        return 'Gelmedi';
      default:
        return 'Planlı';
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'pending':
        return SalonCrmTheme.danger;
      case 'completed':
        return SalonCrmTheme.success;
      case 'cancelled':
        return SalonCrmTheme.muted;
      case 'no_show':
        return const Color(0xFFD97706);
      default:
        return SalonCrmTheme.danger;
    }
  }

  Color _statusBg(String status) {
    switch (status) {
      case 'pending':
        return SalonCrmTheme.dangerSoft;
      case 'completed':
        return SalonCrmTheme.successSoft;
      case 'cancelled':
        return SalonCrmTheme.bgDeep;
      case 'no_show':
        return const Color(0xFFFFF7ED);
      default:
        return SalonCrmTheme.dangerSoft;
    }
  }

  int _occupancyForDay(DateTime day) {
    final key = SalonCrmDates.dateKey(day);
    for (final occ in _occupancy) {
      if (occ.date == key) return occ.total;
    }
    return 0;
  }

  Widget _staffFilterBar() {
    if (_role == 'staff') {
      final name = _displayName.isNotEmpty ? _displayName : 'Personel';
      return Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
        child: Align(
          alignment: Alignment.centerLeft,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: const Color(0xFFD1FAE5),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              'Randevularım · $name',
              style: const TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w800,
                color: Color(0xFF047857),
              ),
            ),
          ),
        ),
      );
    }

    return SizedBox(
      height: 44,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
        children: [
          _StaffFilterChip(
            label: 'Tümü',
            selected: _filterKey == 'all',
            onTap: () => setState(() => _filterKey = 'all'),
          ),
          _StaffFilterChip(
            label: 'Ben',
            selected: _filterKey == 'owner',
            onTap: () => setState(() => _filterKey = 'owner'),
          ),
          ..._staff.map(
            (s) => _StaffFilterChip(
              label: s.name,
              selected: _filterKey == 'staff_${s.id}',
              onTap: () => setState(() => _filterKey = 'staff_${s.id}'),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: 'Randevular',
      showBack: !widget.embedded,
      floatingActionButton: _canWrite
          ? FloatingActionButton(
              heroTag: 'crm_appt_fab',
              onPressed: () => _openCreate(hour: SalonCrmDates.now().hour),
              elevation: 2,
              backgroundColor: SalonCrmTheme.accent,
              foregroundColor: SalonCrmTheme.ink,
              child: const Icon(Icons.add_rounded, size: 28),
            )
          : null,
      actions: [
        IconButton(
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh_rounded, size: 22),
          color: SalonCrmTheme.ink,
        ),
      ],
      body: Column(
        children: [
          _calendarHeader(),
          if (_calendarExpanded) _calendarGrid(),
          if (!_calendarExpanded) _weekStrip(),
          _staffFilterBar(),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 8),
            child: Row(
              children: [
                Text(
                  SalonCrmDates.full(_day),
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                    color: SalonCrmTheme.ink,
                  ),
                ),
                const Spacer(),
                if (!_loading)
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEFF6FF),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      '$_activeAppointmentCount randevu',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF3B82F6),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          if (!_canWrite)
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
              child: Text(
                'CRM kilitli — yeni randevu eklenemez.',
                style: SalonCrmTheme.caption,
              ),
            ),
          Expanded(
            child: _loading
                ? const Center(
                    child: CircularProgressIndicator(
                      color: SalonCrmTheme.accent,
                    ),
                  )
                : _error != null
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.all(24),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.error_outline_rounded,
                                  size: 40, color: SalonCrmTheme.danger),
                              const SizedBox(height: 8),
                              Text(_error!,
                                  textAlign: TextAlign.center,
                                  style: SalonCrmTheme.body),
                              const SizedBox(height: 12),
                              TextButton(
                                  onPressed: _load,
                                  child: const Text('Tekrar dene')),
                            ],
                          ),
                        ),
                      )
                    : Builder(
                        builder: (context) {
                          final banner = _daySummaryBanner();
                          return Column(
                            children: [
                              if (banner != null) banner,
                              Expanded(child: _hourBoard()),
                            ],
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget? _daySummaryBanner() {
    final s = _daySummary;
    if (s == null) return null;
    final isPast = _day.isBefore(SalonCrmDates.today());
    final hasMsg = (s.message ?? '').isNotEmpty || s.needsOutcome > 0;
    if (!isPast && !hasMsg) return null;

    final msg = s.message ??
        (s.needsOutcome > 0
            ? '${s.needsOutcome} randevu için sonuç girilmedi (geldi / gelmedi).'
            : null);
    if (msg == null && !isPast) return null;

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: s.needsOutcome > 0
            ? const Color(0xFFFFF7ED)
            : SalonCrmTheme.accentSoft,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: s.needsOutcome > 0
              ? const Color(0xFFFDBA74)
              : SalonCrmTheme.accent.withValues(alpha: 0.4),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (msg != null)
            Text(
              msg,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: SalonCrmTheme.ink,
              ),
            ),
          if (isPast) ...[
            if (msg != null) const SizedBox(height: 6),
            Text(
              'Gün özeti: ${s.completed} geldi · ${s.noShow} gelmedi · ${s.scheduled} sonuç bekliyor',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: SalonCrmTheme.inkSoft,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _calendarHeader() {
    final monthLabel = SalonCrmDates.monthYear(_calendarMonth);
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 4, 12, 0),
      child: Row(
        children: [
          IconButton(
            onPressed: () {
              setState(() {
                _calendarMonth = DateTime(
                    _calendarMonth.year, _calendarMonth.month - 1);
              });
            },
            icon: const Icon(Icons.chevron_left_rounded, size: 22),
            color: SalonCrmTheme.ink,
          ),
          Expanded(
            child: GestureDetector(
              onTap: () =>
                  setState(() => _calendarExpanded = !_calendarExpanded),
              child: Text(
                monthLabel,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: SalonCrmTheme.ink,
                ),
              ),
            ),
          ),
          IconButton(
            onPressed: () {
              setState(() {
                _calendarMonth = DateTime(
                    _calendarMonth.year, _calendarMonth.month + 1);
              });
            },
            icon: const Icon(Icons.chevron_right_rounded, size: 22),
            color: SalonCrmTheme.ink,
          ),
          IconButton(
            onPressed: () =>
                setState(() => _calendarExpanded = !_calendarExpanded),
            icon: Icon(
              _calendarExpanded
                  ? Icons.expand_less_rounded
                  : Icons.expand_more_rounded,
              size: 22,
            ),
            color: SalonCrmTheme.muted,
          ),
        ],
      ),
    );
  }

  Widget _calendarGrid() {
    final firstDay = DateTime(_calendarMonth.year, _calendarMonth.month, 1);
    final daysInMonth =
        DateTime(_calendarMonth.year, _calendarMonth.month + 1, 0).day;
    final startWeekday = firstDay.weekday;
    final today = SalonCrmDates.today();
    final dayNames = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Column(
        children: [
          Row(
            children: dayNames
                .map((d) => Expanded(
                      child: Center(
                        child: Text(
                          d,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: SalonCrmTheme.muted,
                          ),
                        ),
                      ),
                    ))
                .toList(),
          ),
          const SizedBox(height: 4),
          ...List.generate(6, (week) {
            return Row(
              children: List.generate(7, (weekday) {
                final dayIndex =
                    week * 7 + weekday - (startWeekday - 1);
                if (dayIndex < 1 || dayIndex > daysInMonth) {
                  return const Expanded(child: SizedBox(height: 40));
                }
                final date = DateTime(
                    _calendarMonth.year, _calendarMonth.month, dayIndex);
                final isSelected = SalonCrmDates.sameDay(date, _day);
                final isToday = SalonCrmDates.sameDay(date, today);
                final count = _occupancyForDay(date);

                return Expanded(
                  child: GestureDetector(
                    onTap: () {
                      setState(() {
                        _day = date;
                        _calendarExpanded = false;
                      });
                      _load();
                    },
                    child: Container(
                      height: 40,
                      margin: const EdgeInsets.all(1),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? SalonCrmTheme.accent
                            : (isToday
                                ? SalonCrmTheme.accentSoft
                                : Colors.transparent),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            '$dayIndex',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: isSelected || isToday
                                  ? FontWeight.w800
                                  : FontWeight.w600,
                              color: SalonCrmTheme.ink,
                            ),
                          ),
                          if (count > 0)
                            Container(
                              width: 5,
                              height: 5,
                              decoration: BoxDecoration(
                                color: isSelected
                                    ? SalonCrmTheme.ink
                                    : SalonCrmTheme.success,
                                shape: BoxShape.circle,
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                );
              }),
            );
          }),
        ],
      ),
    );
  }

  Widget _weekStrip() {
    final today = SalonCrmDates.today();
    final monday = _day.subtract(Duration(days: _day.weekday - 1));
    final days = List.generate(7, (i) => monday.add(Duration(days: i)));
    final dayLabels = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        children: List.generate(7, (i) {
          final date = days[i];
          final isSelected = SalonCrmDates.sameDay(date, _day);
          final isToday = SalonCrmDates.sameDay(date, today);
          final count = _occupancyForDay(date);

          return Expanded(
            child: GestureDetector(
              onTap: () {
                setState(() {
                  _day = date;
                  _calendarMonth =
                      DateTime(date.year, date.month);
                });
                _load();
              },
              child: Container(
                margin: const EdgeInsets.symmetric(horizontal: 2),
                padding: const EdgeInsets.symmetric(vertical: 8),
                decoration: BoxDecoration(
                  color: isSelected
                      ? SalonCrmTheme.accent
                      : (isToday ? SalonCrmTheme.accentSoft : null),
                  borderRadius: BorderRadius.circular(12),
                  border: isToday && !isSelected
                      ? Border.all(color: SalonCrmTheme.accent, width: 1.5)
                      : null,
                ),
                child: Column(
                  children: [
                    Text(
                      dayLabels[i],
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: isSelected
                            ? SalonCrmTheme.ink
                            : SalonCrmTheme.muted,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${date.day}',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: SalonCrmTheme.ink,
                      ),
                    ),
                    if (count > 0)
                      Container(
                        margin: const EdgeInsets.only(top: 3),
                        width: 5,
                        height: 5,
                        decoration: BoxDecoration(
                          color: isSelected
                              ? SalonCrmTheme.ink
                              : SalonCrmTheme.success,
                          shape: BoxShape.circle,
                        ),
                      ),
                  ],
                ),
              ),
            ),
          );
        }),
      ),
    );
  }

  Widget _hourBoard() {
    final startHour = _openHour;
    final endHour = _closeHour < _openHour ? _openHour : _closeHour;

    if (_filteredItems.isEmpty) {
      final filtered = _filterKey != 'all';
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.event_available_rounded,
                size: 48, color: SalonCrmTheme.muted.withValues(alpha: 0.5)),
            const SizedBox(height: 12),
            Text(
              filtered ? 'Bu kişi için randevu yok' : 'Bu gün randevu yok',
              style: SalonCrmTheme.body,
            ),
            if (_canWrite) ...[
              const SizedBox(height: 8),
              TextButton.icon(
                onPressed: () => _openCreate(hour: SalonCrmDates.now().hour),
                icon: const Icon(Icons.add_rounded),
                label: const Text('Randevu ekle'),
              ),
            ],
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
      itemCount: endHour - startHour + 1,
      itemBuilder: (context, i) {
        final hour = startHour + i;

        return Padding(
          padding: const EdgeInsets.only(bottom: 6),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                width: 48,
                child: Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Text(
                    salonCrmHourLabel(hour),
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                      color: SalonCrmTheme.muted,
                    ),
                  ),
                ),
              ),
              Expanded(
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    final slotItems = _filteredItems.where((item) {
                      final t = item.startsAt;
                      return t != null &&
                          t.hour == hour &&
                          item.status != 'cancelled' &&
                          item.status != 'no_show';
                    }).toList()
                      ..sort((a, b) {
                        final at = a.startsAt!;
                        final bt = b.startsAt!;
                        final am = at.hour * 60 + at.minute;
                        final bm = bt.hour * 60 + bt.minute;
                        return am.compareTo(bm);
                      });

                    if (slotItems.isEmpty) {
                      return InkWell(
                        onTap:
                            _canWrite ? () => _openCreate(hour: hour) : null,
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          height: 44,
                          alignment: Alignment.centerLeft,
                          padding:
                              const EdgeInsets.symmetric(horizontal: 14),
                          decoration: BoxDecoration(
                            color: SalonCrmTheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color:
                                  SalonCrmTheme.line.withValues(alpha: 0.6),
                            ),
                          ),
                          child: Row(
                            children: [
                              if (_canWrite)
                                const Icon(Icons.add_rounded,
                                    size: 16, color: SalonCrmTheme.muted),
                              if (_canWrite) const SizedBox(width: 6),
                              Text(
                                _canWrite ? 'Müşteri ekle' : 'Boş',
                                style: SalonCrmTheme.caption,
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    const gap = 6.0;
                    final n = slotItems.length;
                    final cols = n == 1 ? 1 : (n == 2 ? 2 : 3);
                    final tileW =
                        (constraints.maxWidth - gap * (cols - 1)) / cols;

                    return Wrap(
                      spacing: gap,
                      runSpacing: gap,
                      children: [
                        ...slotItems.map(
                          (item) => SizedBox(
                            width: tileW,
                            child: _hourAppointmentTile(item),
                          ),
                        ),
                        if (_canWrite)
                          SizedBox(
                            width: tileW,
                            child: _addInHourChip(hour),
                          ),
                      ],
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _addInHourChip(int hour) {
    return InkWell(
      onTap: () => _openCreate(hour: hour),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        constraints: const BoxConstraints(minHeight: 64),
        alignment: Alignment.center,
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
        decoration: BoxDecoration(
          color: SalonCrmTheme.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: SalonCrmTheme.line.withValues(alpha: 0.85),
          ),
        ),
        child: const Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.add_rounded, size: 20, color: SalonCrmTheme.muted),
            SizedBox(height: 2),
            Text(
              'Ekle',
              style: TextStyle(
                fontSize: 11,
                height: 1.1,
                fontWeight: FontWeight.w700,
                color: SalonCrmTheme.muted,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _hourAppointmentTile(SalonCrmAppointmentItem item) {
    final time = item.startsAt == null
        ? '--:--'
        : DateFormat('HH:mm').format(item.startsAt!);
    final bgColor = item.isBlock
        ? SalonCrmTheme.dangerSoft
        : _statusBg(item.status);
    final accentColor = item.isBlock
        ? SalonCrmTheme.danger
        : _statusColor(item.status);
    final title = item.isBlock
        ? (item.notes?.isNotEmpty == true ? item.notes! : 'Mola')
        : item.customerName;

    return InkWell(
      onTap: () => _openDetail(item),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        constraints: const BoxConstraints(minHeight: 64),
        padding: const EdgeInsets.fromLTRB(10, 8, 8, 8),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(12),
          border: Border(
            left: BorderSide(color: accentColor, width: 3),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              time,
              style: TextStyle(
                fontWeight: FontWeight.w900,
                fontSize: 14,
                height: 1.15,
                color: item.isBlock ? SalonCrmTheme.danger : SalonCrmTheme.ink,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 12,
                height: 1.15,
                color: item.isBlock ? SalonCrmTheme.danger : SalonCrmTheme.ink,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              '${item.durationMinutes} dk',
              style: const TextStyle(
                fontSize: 11,
                height: 1.15,
                fontWeight: FontWeight.w600,
                color: SalonCrmTheme.muted,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _appointmentCard(SalonCrmAppointmentItem item) {
    final time = item.startsAt == null
        ? '--:--'
        : DateFormat('HH:mm').format(item.startsAt!);
    final bgColor = item.isBlock
        ? SalonCrmTheme.dangerSoft
        : _statusBg(item.status);
    final accentColor = item.isBlock
        ? SalonCrmTheme.danger
        : _statusColor(item.status);

    return InkWell(
      onTap: () => _openDetail(item),
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(14),
          border: Border(
            left: BorderSide(color: accentColor, width: 3),
          ),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.isBlock
                        ? (item.notes?.isNotEmpty == true
                            ? item.notes!
                            : 'Mola / kapalı')
                        : item.customerName,
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 14,
                      color: item.isBlock
                          ? SalonCrmTheme.danger
                          : SalonCrmTheme.ink,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    [
                      time,
                      '${item.durationMinutes} dk',
                      if (!item.isBlock && item.serviceName.isNotEmpty)
                        item.serviceName,
                      salonCrmAssigneeLabel(item),
                    ].join(' · '),
                    style: const TextStyle(
                      fontSize: 12,
                      color: SalonCrmTheme.muted,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: accentColor.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    item.isBlock
                        ? 'Kapalı'
                        : (item.isUnpaid
                            ? 'Veresiye'
                            : _statusLabel(item.status)),
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: accentColor,
                    ),
                  ),
                ),
                if (!item.isBlock && item.price > 0)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      '${item.price.toStringAsFixed(0)} ₺',
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 13,
                        color: SalonCrmTheme.ink,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(width: 4),
            const Icon(Icons.chevron_right_rounded,
                size: 18, color: SalonCrmTheme.muted),
          ],
        ),
      ),
    );
  }
}

class SalonCrmCreateAppointmentSheet extends StatefulWidget {
  const SalonCrmCreateAppointmentSheet({
    super.key,
    required this.service,
    required this.token,
    required this.initialDay,
    this.hostContext,
    this.initialHour = 10,
    this.existing,
    this.forceStaffId,
  });

  final SalonCrmService service;
  final String token;
  final BuildContext? hostContext;
  final DateTime initialDay;
  final int initialHour;
  final SalonCrmAppointmentItem? existing;
  /// Personel paneli: randevu her zaman bu personele atanır
  final int? forceStaffId;

  @override
  State<SalonCrmCreateAppointmentSheet> createState() =>
      _SalonCrmCreateAppointmentSheetState();
}

class _SalonCrmCreateAppointmentSheetState
    extends State<SalonCrmCreateAppointmentSheet> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  bool _loadingMeta = true;
  bool _isBlock = false;
  String _blockType = 'lunch';
  int _blockMinutes = 60;
  int _durationMinutes = 30;
  List<SalonCrmServiceItem> _services = [];
  List<SalonCrmStaffItem> _staff = [];
  List<SalonCrmCustomerItem> _customers = [];
  SalonCrmServiceItem? _selectedService;
  SalonCrmStaffItem? _selectedStaff;
  int? _selectedCustomerId;
  bool _saving = false;
  String? _submitError;
  late DateTime _startsAt;

  SalonCrmCustomerItem? get _selectedCustomer {
    if (_selectedCustomerId == null) return null;
    for (final c in _customers) {
      if (c.id == _selectedCustomerId) return c;
    }
    return null;
  }

  BuildContext get _messageContext => widget.hostContext ?? context;

  void _showError(Object error) {
    final msg = salonCrmErrorMessage(error);
    setState(() => _submitError = msg);
    salonCrmShowError(_messageContext, error);
  }

  int? get _resolvedStaffId {
    if (widget.forceStaffId != null) return widget.forceStaffId;
    if (_staff.isEmpty && widget.existing?.staffId != null) {
      return widget.existing!.staffId;
    }
    return _selectedStaff?.id;
  }

  @override
  void initState() {
    super.initState();
    final existing = widget.existing;
    if (existing?.startsAt != null) {
      _startsAt = existing!.startsAt!.toLocal();
    } else {
      _startsAt = DateTime(
        widget.initialDay.year,
        widget.initialDay.month,
        widget.initialDay.day,
        widget.initialHour.clamp(8, 21),
        0,
      );
    }
    if (existing != null) {
      _isBlock = existing.isBlock;
      _blockType = existing.blockType ?? 'lunch';
      _blockMinutes = existing.durationMinutes;
      _durationMinutes = existing.durationMinutes > 0
          ? existing.durationMinutes
          : 30;
      _nameCtrl.text = existing.customerName;
      _phoneCtrl.text =
          existing.customerPhone == '-' ? '' : existing.customerPhone;
      _notesCtrl.text = existing.notes ?? '';
    }
    _loadMeta();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadMeta() async {
    try {
      final services = await widget.service.fetchServices(widget.token);
      var staff = <SalonCrmStaffItem>[];
      try {
        staff = await widget.service.fetchStaff(widget.token);
      } catch (_) {
        staff = [];
      }
      final customers = await widget.service.fetchCustomers(
        widget.token,
        onlyStaffId: widget.forceStaffId,
      );
      if (!mounted) return;
      setState(() {
        _services = services.where((s) => s.isActive).toList();
        _staff = staff.where((s) => s.isActive).toList();
        _customers = customers;
        // Yeni randevuda hizmet zorunlu değil; ilk hizmeti otomatik seçme
        _selectedService = null;
        _selectedStaff = null;
        final existing = widget.existing;
        if (existing != null) {
          if (existing.serviceId != null) {
            for (final s in _services) {
              if (s.id == existing.serviceId) {
                _selectedService = s;
                break;
              }
            }
          }
          if (existing.staffId != null) {
            for (final s in _staff) {
              if (s.id == existing.staffId) {
                _selectedStaff = s;
                break;
              }
            }
          }
          if (existing.customerId != null) {
            _selectedCustomerId = existing.customerId;
            for (final c in _customers) {
              if (c.id == existing.customerId) {
                _nameCtrl.text = c.name;
                _phoneCtrl.text = c.phone;
                break;
              }
            }
          }
          if (existing.isBlock) {
            _selectedCustomerId = null;
            _selectedService = null;
          }
          if (!existing.isBlock && existing.durationMinutes > 0) {
            _durationMinutes = existing.durationMinutes;
          }
        }
        _loadingMeta = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loadingMeta = false);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  void _markBlock() {
    setState(() {
      _isBlock = true;
      _selectedCustomerId = null;
      _nameCtrl.text = '';
      _phoneCtrl.text = '';
      _selectedService = null;
      _submitError = null;
      _applyBlockDefaults();
    });
  }

  void _applyBlockDefaults() {
    _blockMinutes = switch (_blockType) {
      'lunch' => 60,
      'wedding' => 180,
      'leave' || 'closed' => 480,
      _ => 30,
    };
    _nameCtrl.text = switch (_blockType) {
      'lunch' => 'Öğle arası',
      'wedding' => 'Düğün / özel gün',
      'leave' => 'İzin',
      'closed' => 'Kapalı',
      _ => 'Mola / dolu',
    };
  }

  void _clearBlock() {
    setState(() {
      _isBlock = false;
      if (_nameCtrl.text.trim().toLowerCase() == 'dolu') {
        _nameCtrl.clear();
      }
      _selectedService = _services.isNotEmpty ? _services.first : null;
    });
  }

  Future<void> _pickDateTime() async {
    final date = await salonCrmPickDate(
      context,
      initial: _startsAt,
      firstDate: SalonCrmDates.today().subtract(const Duration(days: 1)),
      lastDate: SalonCrmDates.today().add(const Duration(days: 365)),
    );
    if (date == null || !mounted) return;
    final time = await salonCrmPickTime(
      context,
      initial: TimeOfDay.fromDateTime(_startsAt),
    );
    if (time == null || !mounted) return;
    setState(() {
      _startsAt = DateTime(
        date.year,
        date.month,
        date.day,
        time.hour,
        time.minute,
      );
    });
  }

  Future<void> _submit() async {
    if (_saving) return;

    if (_isBlock) {
      setState(() {
        _submitError = null;
        _saving = true;
      });
      salonCrmShowLoading(context);
      try {
        if (widget.existing != null) {
          await widget.service.updateAppointment(
            token: widget.token,
            id: widget.existing!.id,
            startsAt: _startsAt,
            isBlock: true,
            blockType: _blockType,
            customerName: _nameCtrl.text.trim().isEmpty
                ? 'Mola / dolu'
                : _nameCtrl.text.trim(),
            staffId: _resolvedStaffId,
            durationMinutes: _blockMinutes,
            notes: _notesCtrl.text.trim(),
            serviceName: _nameCtrl.text.trim().isEmpty
                ? 'Mola / dolu'
                : _nameCtrl.text.trim(),
          );
        } else {
          await widget.service.createAppointment(
            token: widget.token,
            startsAt: _startsAt,
            isBlock: true,
            blockType: _blockType,
            customerName: _nameCtrl.text.trim().isEmpty
                ? 'Mola / dolu'
                : _nameCtrl.text.trim(),
            staffId: _resolvedStaffId,
            durationMinutes: _blockMinutes,
            notes: _notesCtrl.text.trim(),
            serviceName: _nameCtrl.text.trim().isEmpty
                ? 'Mola / dolu'
                : _nameCtrl.text.trim(),
          );
        }
        if (!mounted) return;
        salonCrmCloseLoading(context);
        Navigator.pop(context, true);
      } catch (e) {
        if (!mounted) return;
        salonCrmCloseLoading(context);
        setState(() => _saving = false);
        _showError(e);
      }
      return;
    }

    final selected = _selectedCustomer;
    final name = selected?.name.trim() ?? _nameCtrl.text.trim();
    final phone = selected?.phone.trim() ?? _phoneCtrl.text.trim();
    if (name.isEmpty) {
      setState(() {
        _submitError =
            'Müşteri adı yazın, listeden seçin veya Dolu işaretleyin';
      });
      salonCrmShowError(
        _messageContext,
        _submitError ?? 'Müşteri seçin veya ad yazın',
      );
      return;
    }

    setState(() {
      _submitError = null;
      _saving = true;
    });
    salonCrmShowLoading(context);
    try {
      if (widget.existing != null) {
        await widget.service.updateAppointment(
          token: widget.token,
          id: widget.existing!.id,
          customerId: _selectedCustomerId,
          customerName: name,
          customerPhone: phone,
          startsAt: _startsAt,
          staffId: _resolvedStaffId,
          serviceId: _selectedService?.id,
          serviceName: _selectedService?.name ?? 'Hizmet',
          durationMinutes: _durationMinutes,
          notes: _notesCtrl.text.trim(),
        );
      } else {
        await widget.service.createAppointment(
          token: widget.token,
          customerId: _selectedCustomerId,
          customerName: name,
          customerPhone: phone,
          startsAt: _startsAt,
          staffId: _resolvedStaffId,
          serviceId: _selectedService?.id,
          serviceName: _selectedService?.name ?? 'Hizmet',
          durationMinutes: _durationMinutes,
          price: _selectedService?.price,
          notes: _notesCtrl.text.trim(),
        );
      }
      if (!mounted) return;
      salonCrmCloseLoading(context);
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      salonCrmCloseLoading(context);
      setState(() => _saving = false);
      _showError(e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottom),
      child: _loadingMeta
          ? const SizedBox(
              height: 180,
              child: Center(child: CircularProgressIndicator()),
            )
          : SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    widget.existing == null
                        ? 'Yeni randevu'
                        : 'Randevuyu düzenle',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _isBlock ? _clearBlock : _markBlock,
                          style: OutlinedButton.styleFrom(
                            foregroundColor: _isBlock
                                ? Colors.red.shade700
                                : SalonCrmTheme.ink,
                            side: BorderSide(
                              color: _isBlock
                                  ? Colors.red.shade700
                                  : SalonCrmTheme.line,
                            ),
                          ),
                          child: Text(
                            _isBlock
                                ? 'Kapalı saat seçili ✓'
                                : 'Mola / kapalı saat',
                            style:
                                const TextStyle(fontWeight: FontWeight.w700),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  if (!_isBlock) ...[
                    if (_customers.isNotEmpty)
                      DropdownButtonFormField<int?>(
                        value: _selectedCustomerId,
                        decoration: const InputDecoration(
                          labelText: 'Kayıtlı müşteri (opsiyonel)',
                          border: OutlineInputBorder(),
                        ),
                        items: [
                          const DropdownMenuItem<int?>(
                            value: null,
                            child: Text('Serbest yaz / seçme'),
                          ),
                          ..._customers.map(
                            (c) => DropdownMenuItem(
                              value: c.id,
                              child: Text('${c.name} · ${c.phone}'),
                            ),
                          ),
                        ],
                        onChanged: (v) {
                          setState(() {
                            _selectedCustomerId = v;
                            _submitError = null;
                            final customer = _selectedCustomer;
                            if (customer != null) {
                              _nameCtrl.text = customer.name;
                              _phoneCtrl.text = customer.phone;
                            } else {
                              _nameCtrl.clear();
                              _phoneCtrl.clear();
                            }
                          });
                        },
                      ),
                    if (_selectedCustomer?.notes != null &&
                        _selectedCustomer!.notes!.trim().isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        _selectedCustomer!.notes!,
                        style: const TextStyle(
                          fontSize: 12.5,
                          color: SalonCrmTheme.inkSoft,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                    if (_customers.isNotEmpty) const SizedBox(height: 10),
                    TextField(
                      controller: _nameCtrl,
                      enabled: _selectedCustomerId == null,
                      decoration: const InputDecoration(
                        labelText: 'Ad / not (örn. Dolu, Ahmet)',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _phoneCtrl,
                      enabled: _selectedCustomerId == null,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(
                        labelText: 'Telefon (opsiyonel)',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 10),
                    if (_services.isNotEmpty)
                      DropdownButtonFormField<SalonCrmServiceItem?>(
                        value: _selectedService,
                        decoration: const InputDecoration(
                          labelText: 'Hizmet (opsiyonel)',
                          border: OutlineInputBorder(),
                        ),
                        items: [
                          const DropdownMenuItem<SalonCrmServiceItem?>(
                            value: null,
                            child: Text('Seçilmedi'),
                          ),
                          ..._services.map(
                            (s) => DropdownMenuItem(
                              value: s,
                              child: Text(
                                '${s.name} · ${s.durationMinutes} dk',
                              ),
                            ),
                          ),
                        ],
                        onChanged: (v) => setState(() {
                          _selectedService = v;
                          if (v != null) {
                            _durationMinutes = v.durationMinutes;
                          }
                        }),
                      ),
                    if (_services.isNotEmpty) const SizedBox(height: 10),
                    DropdownButtonFormField<int>(
                      value: _durationMinutes,
                      decoration: const InputDecoration(
                        labelText: 'Süre',
                        border: OutlineInputBorder(),
                      ),
                      items: [
                        for (final m in {
                          10,
                          15,
                          20,
                          30,
                          45,
                          60,
                          90,
                          120,
                          _durationMinutes,
                        }.toList()
                          ..sort())
                          DropdownMenuItem(
                            value: m,
                            child: Text(m >= 60 && m % 30 == 0
                                ? (m == 60
                                    ? '1 saat'
                                    : m == 90
                                        ? '1.5 saat'
                                        : '${m ~/ 60} saat')
                                : '$m dk'),
                          ),
                      ],
                      onChanged: (v) =>
                          setState(() => _durationMinutes = v ?? 30),
                    ),
                  ] else ...[
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        for (final e in const [
                          ('lunch', 'Öğle arası'),
                          ('wedding', 'Düğün'),
                          ('leave', 'İzin'),
                          ('closed', 'Kapalı'),
                          ('other', 'Diğer'),
                        ])
                          ChoiceChip(
                            label: Text(e.$2),
                            selected: _blockType == e.$1,
                            onSelected: (_) => setState(() {
                              _blockType = e.$1;
                              _applyBlockDefaults();
                            }),
                          ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _nameCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Etiket',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 10),
                    DropdownButtonFormField<int>(
                      value: _blockMinutes,
                      decoration: const InputDecoration(
                        labelText: 'Süre',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 30, child: Text('30 dk')),
                        DropdownMenuItem(value: 60, child: Text('1 saat')),
                        DropdownMenuItem(
                            value: 90, child: Text('1.5 saat')),
                        DropdownMenuItem(value: 120, child: Text('2 saat')),
                        DropdownMenuItem(value: 180, child: Text('3 saat')),
                        DropdownMenuItem(value: 240, child: Text('4 saat')),
                        DropdownMenuItem(
                            value: 480, child: Text('Tüm gün')),
                      ],
                      onChanged: (v) =>
                          setState(() => _blockMinutes = v ?? 60),
                    ),
                    const SizedBox(height: 10),
                    const Text(
                      'Bu saat müşteriye kapanır. Öğle arası, izin, düğün veya kapalı gün için kullanın.',
                      style: TextStyle(
                        fontSize: 12.5,
                        color: SalonCrmTheme.muted,
                      ),
                    ),
                  ],
                  const SizedBox(height: 10),
                  if (widget.forceStaffId == null)
                    DropdownButtonFormField<SalonCrmStaffItem?>(
                      value: _selectedStaff,
                      decoration: const InputDecoration(
                        labelText: 'Kim yapacak?',
                        border: OutlineInputBorder(),
                      ),
                      items: [
                        const DropdownMenuItem<SalonCrmStaffItem?>(
                          value: null,
                          child: Text('Ben (kendim)'),
                        ),
                        ..._staff.map(
                          (s) => DropdownMenuItem(
                            value: s,
                            child: Text(s.name),
                          ),
                        ),
                      ],
                      onChanged: (v) => setState(() => _selectedStaff = v),
                    ),
                  if (widget.forceStaffId == null) const SizedBox(height: 10),
                  Material(
                    color: Colors.transparent,
                    child: InkWell(
                      onTap: _pickDateTime,
                      borderRadius: BorderRadius.circular(16),
                      child: Ink(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: SalonCrmTheme.accentSoft,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: SalonCrmTheme.accent.withValues(alpha: 0.55),
                          ),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: SalonCrmTheme.surface,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(
                                Icons.schedule_rounded,
                                color: SalonCrmTheme.ink,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Tarih ve saat',
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: SalonCrmTheme.inkSoft,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    '${SalonCrmDates.full(_startsAt)} · ${DateFormat('HH:mm').format(_startsAt)}',
                                    style: const TextStyle(
                                      fontSize: 15,
                                      fontWeight: FontWeight.w800,
                                      color: SalonCrmTheme.ink,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const Icon(
                              Icons.chevron_right_rounded,
                              color: SalonCrmTheme.inkSoft,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: _notesCtrl,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Not (opsiyonel)',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (_submitError != null) ...[
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.red.shade200),
                      ),
                      child: Text(
                        _submitError!,
                        style: TextStyle(
                          color: Colors.red.shade800,
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                  ],
                  ElevatedButton(
                    onPressed: _saving ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _isBlock
                          ? Colors.red.shade600
                          : SalonCrmTheme.accent,
                      foregroundColor:
                          _isBlock ? Colors.white : SalonCrmTheme.ink,
                      minimumSize: const Size.fromHeight(48),
                    ),
                    child: Text(
                      widget.existing == null
                          ? (_isBlock ? 'Kapalı saat kaydet' : 'Kaydet')
                          : 'Saati kaydet',
                      style: const TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}

class _StaffFilterChip extends StatelessWidget {
  const _StaffFilterChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => onTap(),
        showCheckmark: false,
        labelStyle: TextStyle(
          fontWeight: FontWeight.w700,
          fontSize: 12.5,
          color: selected ? SalonCrmTheme.ink : SalonCrmTheme.inkSoft,
        ),
        selectedColor: SalonCrmTheme.accent,
        backgroundColor: SalonCrmTheme.surface,
        side: BorderSide(
          color: selected
              ? SalonCrmTheme.accent
              : SalonCrmTheme.line.withValues(alpha: 0.85),
        ),
      ),
    );
  }
}
