import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../utils/utils.dart';
import '../services/salon_crm_session.dart';
import '../services/salon_crm_service.dart';
import '../widgets/salon_crm_dates.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmLedgerScreen extends StatefulWidget {
  const SalonCrmLedgerScreen({super.key, this.embedded = false});

  final bool embedded;

  @override
  State<SalonCrmLedgerScreen> createState() => _SalonCrmLedgerScreenState();
}

class _SalonCrmLedgerScreenState extends State<SalonCrmLedgerScreen> {
  final _service = SalonCrmService();
  DateTime _day = SalonCrmDates.today();
  bool _loading = true;
  bool _canWrite = true;
  String? _error;
  SalonCrmLedgerSummary _summary =
      SalonCrmLedgerSummary(income: 0, expense: 0, net: 0);
  List<SalonCrmLedgerEntryItem> _entries = [];
  String _crmToken = '';

  String get _dateKey => SalonCrmDates.dateKey(_day);

  bool get _isToday => SalonCrmDates.sameDay(_day, SalonCrmDates.today());

  String _money(double value) => '${value.toStringAsFixed(0)} ₺';

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
      final status = await _service.fetchStatus(_crmToken);
      final day = await _service.fetchLedger(_crmToken, date: _dateKey);
      if (!mounted) return;
      setState(() {
        _canWrite = status.access.canWrite;
        _summary = day.summary;
        _entries = day.entries;
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

  Future<void> _pickDay() async {
    final picked = await salonCrmPickDate(
      context,
      initial: _day,
      firstDate: DateTime(2024),
      lastDate: SalonCrmDates.today().add(const Duration(days: 365)),
    );
    if (picked == null) return;
    setState(() => _day = picked);
    await _load();
  }

  Future<void> _shiftDay(int delta) async {
    setState(() => _day = _day.add(Duration(days: delta)));
    await _load();
  }

  Future<void> _goToday() async {
    setState(() => _day = SalonCrmDates.today());
    await _load();
  }

  Future<void> _openAdd({required String type}) async {
    if (!_canWrite) {
      Utils.errorSnackBar(context, 'CRM kilitli');
      return;
    }
    final titleCtrl = TextEditingController();
    final amountCtrl = TextEditingController();
    final notesCtrl = TextEditingController();
    String? category = type == 'income' ? 'Hizmet' : 'Genel';

    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: SalonCrmTheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (ctx) {
        final bottom = MediaQuery.of(ctx).viewInsets.bottom;
        return StatefulBuilder(
          builder: (ctx, setModal) {
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
                          color: type == 'income'
                              ? SalonCrmTheme.successSoft
                              : SalonCrmTheme.dangerSoft,
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: Icon(
                          type == 'income'
                              ? Icons.arrow_upward_rounded
                              : Icons.arrow_downward_rounded,
                          color: type == 'income'
                              ? SalonCrmTheme.success
                              : SalonCrmTheme.danger,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          type == 'income' ? 'Gelir ekle' : 'Gider ekle',
                          style: SalonCrmTheme.titleMd.copyWith(fontSize: 18),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  TextField(
                    controller: titleCtrl,
                    decoration: SalonCrmTheme.field(
                      type == 'income'
                          ? 'Açıklama (örn. Saç kesimi)'
                          : 'Açıklama (örn. Boya alımı)',
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: amountCtrl,
                    keyboardType:
                        const TextInputType.numberWithOptions(decimal: true),
                    decoration: SalonCrmTheme.field('Tutar (₺)'),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: category,
                    decoration: SalonCrmTheme.field('Kategori'),
                    items: (type == 'income'
                            ? const ['Hizmet', 'Ürün satışı', 'Diğer']
                            : const [
                                'Genel',
                                'Malzeme',
                                'Kira',
                                'Maaş',
                                'Diğer',
                              ])
                        .map(
                          (c) => DropdownMenuItem(value: c, child: Text(c)),
                        )
                        .toList(),
                    onChanged: (v) => setModal(() => category = v),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: notesCtrl,
                    maxLines: 2,
                    decoration: SalonCrmTheme.field('Not (opsiyonel)'),
                  ),
                  const SizedBox(height: 18),
                  CrmPrimaryButton(
                    label: 'Kaydet',
                    icon: Icons.check_rounded,
                    onPressed: () async {
                      final title = titleCtrl.text.trim();
                      final amount = double.tryParse(
                        amountCtrl.text.trim().replaceAll(',', '.'),
                      );
                      if (title.isEmpty || amount == null || amount <= 0) {
                        Utils.errorSnackBar(ctx, 'Açıklama ve tutar gerekli');
                        return;
                      }
                      Utils.loadingDialog(ctx);
                      try {
                        await _service.createLedgerEntry(
                          token: _crmToken,
                          type: type,
                          title: title,
                          amount: amount,
                          entryDate: _dateKey,
                          category: category,
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
      },
    );

    titleCtrl.dispose();
    amountCtrl.dispose();
    notesCtrl.dispose();

    if (saved == true && mounted) await _load();
  }

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: 'Kasa',
      showBack: !widget.embedded,
      actions: [
        IconButton(
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh_rounded, size: 22),
          color: SalonCrmTheme.ink,
        ),
      ],
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _weekStrip(),
          _dateHeader(),
          if (!_canWrite) _lockBanner(),
          if (!_loading && _error == null) ...[
            _summaryHero(),
            if (_canWrite) _actionRow(),
          ],
          Expanded(child: _body()),
        ],
      ),
    );
  }

  Widget _weekStrip() {
    final start = _day.subtract(Duration(days: _day.weekday - 1));
    return SizedBox(
      height: 74,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(12, 4, 12, 0),
        itemCount: 7,
        itemBuilder: (_, i) {
          final d = start.add(Duration(days: i));
          final selected = SalonCrmDates.sameDay(d, _day);
          final isToday = SalonCrmDates.sameDay(d, SalonCrmDates.today());
          return GestureDetector(
            onTap: () {
              setState(() => _day = d);
              _load();
            },
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              width: 48,
              margin: const EdgeInsets.symmetric(horizontal: 4),
              decoration: BoxDecoration(
                color: selected ? SalonCrmTheme.accent : SalonCrmTheme.surface,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: isToday && !selected
                      ? SalonCrmTheme.accent
                      : SalonCrmTheme.line.withValues(alpha: 0.85),
                  width: isToday && !selected ? 1.4 : 1,
                ),
                boxShadow: selected ? SalonCrmTheme.softShadow : null,
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    SalonCrmDates.weekdayShort(d),
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: selected
                          ? SalonCrmTheme.ink.withValues(alpha: 0.7)
                          : SalonCrmTheme.muted,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${d.day}',
                    style: TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                      color: selected ? SalonCrmTheme.ink : SalonCrmTheme.inkSoft,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _dateHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 6, 12, 8),
      child: Row(
        children: [
          IconButton(
            onPressed: _loading ? null : () => _shiftDay(-1),
            icon: const Icon(Icons.chevron_left_rounded, size: 22),
            color: SalonCrmTheme.ink,
          ),
          Expanded(
            child: GestureDetector(
              onTap: _pickDay,
              child: Column(
                children: [
                  Text(
                    SalonCrmDates.dayLabel(_day),
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: SalonCrmTheme.muted,
                    ),
                  ),
                  Text(
                    SalonCrmDates.full(_day),
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 15,
                      color: SalonCrmTheme.ink,
                    ),
                  ),
                ],
              ),
            ),
          ),
          IconButton(
            onPressed: _isToday || _loading ? null : _goToday,
            icon: const Icon(Icons.today_rounded, size: 20),
            color: _isToday ? SalonCrmTheme.muted : SalonCrmTheme.ink,
            tooltip: 'Bugün',
          ),
          IconButton(
            onPressed: _loading ? null : () => _shiftDay(1),
            icon: const Icon(Icons.chevron_right_rounded, size: 22),
            color: SalonCrmTheme.ink,
          ),
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
                'CRM kilitli — yeni kayıt eklenemez. Geçmiş görüntülenir.',
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
    final netPositive = _summary.net >= 0;
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      child: CrmSoftCard(
        padding: const EdgeInsets.all(18),
        color: netPositive
            ? const Color(0xFFF0FDF4)
            : SalonCrmTheme.dangerSoft.withValues(alpha: 0.45),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: netPositive
                        ? SalonCrmTheme.successSoft
                        : SalonCrmTheme.dangerSoft,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(
                    Icons.account_balance_wallet_rounded,
                    color: netPositive
                        ? SalonCrmTheme.success
                        : SalonCrmTheme.danger,
                    size: 22,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Gün sonu net',
                        style: SalonCrmTheme.caption.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      Text(
                        _money(_summary.net),
                        style: TextStyle(
                          fontSize: 26,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -0.6,
                          color: netPositive
                              ? SalonCrmTheme.success
                              : SalonCrmTheme.danger,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: SalonCrmTheme.surface.withValues(alpha: 0.85),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    '${_entries.length} kayıt',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: SalonCrmTheme.inkSoft,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: _SummaryPill(
                    label: 'Gelir',
                    value: _money(_summary.income),
                    icon: Icons.arrow_upward_rounded,
                    color: SalonCrmTheme.success,
                    bg: SalonCrmTheme.successSoft,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _SummaryPill(
                    label: 'Gider',
                    value: _money(_summary.expense),
                    icon: Icons.arrow_downward_rounded,
                    color: SalonCrmTheme.danger,
                    bg: SalonCrmTheme.dangerSoft,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _actionRow() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      child: Row(
        children: [
          Expanded(
            child: _LedgerActionButton(
              label: 'Gelir',
              icon: Icons.add_rounded,
              color: SalonCrmTheme.success,
              bg: SalonCrmTheme.successSoft,
              onTap: () => _openAdd(type: 'income'),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _LedgerActionButton(
              label: 'Gider',
              icon: Icons.remove_rounded,
              color: SalonCrmTheme.danger,
              bg: SalonCrmTheme.dangerSoft,
              onTap: () => _openAdd(type: 'expense'),
            ),
          ),
        ],
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
              const Icon(
                Icons.error_outline_rounded,
                size: 40,
                color: SalonCrmTheme.danger,
              ),
              const SizedBox(height: 8),
              Text(
                _error!,
                textAlign: TextAlign.center,
                style: SalonCrmTheme.body,
              ),
              const SizedBox(height: 12),
              TextButton(onPressed: _load, child: const Text('Tekrar dene')),
            ],
          ),
        ),
      );
    }
    if (_entries.isEmpty) {
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
                child: const Icon(
                  Icons.receipt_long_rounded,
                  size: 32,
                  color: SalonCrmTheme.muted,
                ),
              ),
              const SizedBox(height: 14),
              Text(
                'Bu gün için kayıt yok',
                style: SalonCrmTheme.titleMd.copyWith(fontSize: 17),
              ),
              const SizedBox(height: 6),
              Text(
                _canWrite
                    ? 'Gelir veya gider ekleyerek kasayı takip edin.'
                    : 'Seçili günde hareket bulunmuyor.',
                textAlign: TextAlign.center,
                style: SalonCrmTheme.caption,
              ),
              if (_canWrite) ...[
                const SizedBox(height: 18),
                SizedBox(
                  width: 200,
                  child: CrmPrimaryButton(
                    label: 'Gelir ekle',
                    icon: Icons.add_rounded,
                    onPressed: () => _openAdd(type: 'income'),
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
      itemCount: _entries.length + 1,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, i) {
        if (i == 0) {
          return const Padding(
            padding: EdgeInsets.only(bottom: 4, top: 2),
            child: CrmSectionLabel('Hareketler'),
          );
        }
        return _LedgerEntryCard(
          entry: _entries[i - 1],
          money: _money,
        );
      },
    );
  }
}

class _SummaryPill extends StatelessWidget {
  const _SummaryPill({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
    required this.bg,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final Color bg;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: bg.withValues(alpha: 0.65),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: color.withValues(alpha: 0.85),
                  ),
                ),
                Text(
                  value,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: color,
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

class _LedgerActionButton extends StatelessWidget {
  const _LedgerActionButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.bg,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final Color color;
  final Color bg;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: bg,
      borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
            border: Border.all(color: color.withValues(alpha: 0.18)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 18, color: color),
              const SizedBox(width: 8),
              Text(
                label,
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 14.5,
                  color: color,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LedgerEntryCard extends StatelessWidget {
  const _LedgerEntryCard({
    required this.entry,
    required this.money,
  });

  final SalonCrmLedgerEntryItem entry;
  final String Function(double) money;

  String? get _paymentLabel {
    switch (entry.paymentMethod) {
      case 'cash':
        return 'Nakit';
      case 'card':
        return 'Kart';
      case 'iban':
        return 'IBAN';
      case 'credit':
        return 'Veresiye';
      default:
        return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    final color =
        entry.isIncome ? SalonCrmTheme.success : SalonCrmTheme.danger;
    final bg =
        entry.isIncome ? SalonCrmTheme.successSoft : SalonCrmTheme.dangerSoft;

    return CrmSoftCard(
      padding: const EdgeInsets.all(14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 42,
            height: 42,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: bg,
              borderRadius: BorderRadius.circular(13),
            ),
            child: Icon(
              entry.isIncome
                  ? Icons.arrow_upward_rounded
                  : Icons.arrow_downward_rounded,
              color: color,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  entry.title,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                    color: SalonCrmTheme.ink,
                  ),
                ),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    _EntryTag(
                      label: entry.isIncome ? 'Gelir' : 'Gider',
                      color: color,
                      bg: bg,
                    ),
                    if (entry.isMarketplace)
                      const _EntryTag(
                        label: 'Seyfibaba',
                        color: Color(0xFF6366F1),
                        bg: Color(0xFFEEF2FF),
                      ),
                    if (!entry.isMarketplace &&
                        entry.category != null &&
                        entry.category!.isNotEmpty)
                      _EntryTag(
                        label: entry.category!,
                        color: SalonCrmTheme.inkSoft,
                        bg: SalonCrmTheme.bgDeep,
                      ),
                    if (entry.staffName != null && entry.staffName!.isNotEmpty)
                      _EntryTag(
                        label: entry.staffName!,
                        color: SalonCrmTheme.inkSoft,
                        bg: SalonCrmTheme.bgDeep,
                      ),
                    if (_paymentLabel != null)
                      _EntryTag(
                        label: _paymentLabel!,
                        color: SalonCrmTheme.inkSoft,
                        bg: SalonCrmTheme.bgDeep,
                      ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(
            '${entry.isIncome ? '+' : '-'} ${money(entry.amount)}',
            style: TextStyle(
              fontWeight: FontWeight.w800,
              fontSize: 15,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

class _EntryTag extends StatelessWidget {
  const _EntryTag({
    required this.label,
    required this.color,
    required this.bg,
  });

  final String label;
  final Color color;
  final Color bg;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}
