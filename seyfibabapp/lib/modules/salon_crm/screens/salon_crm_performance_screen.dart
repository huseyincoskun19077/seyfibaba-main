import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_dates.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmPerformanceScreen extends StatefulWidget {
  const SalonCrmPerformanceScreen({super.key, this.embedded = false});

  final bool embedded;

  @override
  State<SalonCrmPerformanceScreen> createState() =>
      _SalonCrmPerformanceScreenState();
}

class _SalonCrmPerformanceScreenState extends State<SalonCrmPerformanceScreen> {
  final _service = SalonCrmService();
  /// 'day' | 'week' | 'month'
  String _period = 'day';
  DateTime _day = SalonCrmDates.today();
  DateTime _month =
      DateTime(SalonCrmDates.now().year, SalonCrmDates.now().month);
  DateTime _weekStart = SalonCrmDates.today();
  bool _loading = true;
  String? _error;
  SalonCrmPerformance? _data;
  String _role = 'owner';

  String get _fromKey {
    if (_period == 'day') {
      return DateFormat('yyyy-MM-dd').format(_day);
    }
    if (_period == 'week') {
      return DateFormat('yyyy-MM-dd').format(_weekStart);
    }
    return DateFormat('yyyy-MM-dd').format(DateTime(_month.year, _month.month, 1));
  }

  String get _toKey {
    if (_period == 'day') {
      return DateFormat('yyyy-MM-dd').format(_day);
    }
    if (_period == 'week') {
      return DateFormat('yyyy-MM-dd')
          .format(_weekStart.add(const Duration(days: 6)));
    }
    final last = DateTime(_month.year, _month.month + 1, 0);
    return DateFormat('yyyy-MM-dd').format(last);
  }

  bool get _isCurrentRange {
    final now = SalonCrmDates.now();
    if (_period == 'day') {
      return SalonCrmDates.dateKey(_day) == SalonCrmDates.dateKey(SalonCrmDates.today());
    }
    if (_period == 'week') {
      return SalonCrmDates.dateKey(_weekStart) ==
          SalonCrmDates.dateKey(_currentWeekStart());
    }
    return _month.year == now.year && _month.month == now.month;
  }

  DateTime _currentWeekStart() {
    final today = SalonCrmDates.today();
    // Monday start
    return today.subtract(Duration(days: today.weekday - 1));
  }

  double _myEarnings(SalonCrmPerformance data) {
    // Personel: komisyon; patron: salona kalan
    if (_role == 'staff') return data.staffShare;
    return data.ownerShare;
  }

  @override
  void initState() {
    super.initState();
    _day = SalonCrmDates.today();
    _weekStart = _currentWeekStart();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final session = await SalonCrmSession.read();
      final token = session?['token'] ?? '';
      if (token.isEmpty) throw Exception('CRM girişi gerekli');
      final data = await _service.fetchPerformance(
        token,
        from: _fromKey,
        to: _toKey,
      );
      if (!mounted) return;
      setState(() {
        _role = session?['role'] ?? 'owner';
        _data = data;
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

  void _shiftRange(int delta) {
    setState(() {
      if (_period == 'day') {
        _day = _day.add(Duration(days: delta));
      } else if (_period == 'week') {
        _weekStart = _weekStart.add(Duration(days: 7 * delta));
      } else {
        _month = DateTime(_month.year, _month.month + delta);
      }
    });
    _load();
  }

  void _goCurrentRange() {
    final now = SalonCrmDates.now();
    setState(() {
      if (_period == 'day') {
        _day = SalonCrmDates.today();
      } else if (_period == 'week') {
        _weekStart = _currentWeekStart();
      } else {
        _month = DateTime(now.year, now.month);
      }
    });
    _load();
  }

  void _setPeriod(String period) {
    if (_period == period) return;
    setState(() {
      _period = period;
      final now = SalonCrmDates.now();
      if (period == 'day') {
        _day = SalonCrmDates.today();
      } else if (period == 'week') {
        _weekStart = _currentWeekStart();
      } else {
        _month = DateTime(now.year, now.month);
      }
    });
    _load();
  }

  List<SalonCrmStaffPerfItem> get _rows {
    final data = _data;
    if (data == null) return const [];

    final rows = <SalonCrmStaffPerfItem>[...data.staff];
    final un = data.unassigned;
    if (un != null && _hasActivity(un)) {
      rows.add(
        SalonCrmStaffPerfItem(
          staffId: un.staffId,
          staffName: 'Ben (salon)',
          isActive: true,
          completed: un.completed,
          scheduled: un.scheduled,
          cancelled: un.cancelled,
          noShow: un.noShow,
          appointmentRevenue: un.appointmentRevenue,
          ledgerIncome: un.ledgerIncome,
          totalRevenue: un.totalRevenue,
          commissionPercent: un.commissionPercent,
          staffShare: un.staffShare,
          ownerShare: un.ownerShare,
          credit: un.credit,
        ),
      );
    }

    rows.sort((a, b) {
      final byRevenue = b.totalRevenue.compareTo(a.totalRevenue);
      if (byRevenue != 0) return byRevenue;
      return a.staffName.compareTo(b.staffName);
    });
    return rows;
  }

  bool _hasActivity(SalonCrmStaffPerfItem item) {
    return item.completed > 0 ||
        item.scheduled > 0 ||
        item.cancelled > 0 ||
        item.noShow > 0 ||
        item.appointmentRevenue > 0 ||
        item.ledgerIncome > 0 ||
        item.totalRevenue > 0 ||
        item.credit > 0;
  }

  String _money(double value) => '${value.toStringAsFixed(0)} ₺';

  @override
  Widget build(BuildContext context) {
    final data = _data;
    final rows = _rows;
    final activeRows = rows.where(_hasActivity).toList();
    final idleRows = rows.where((e) => !_hasActivity(e)).toList();

    return CrmScaffold(
      title: 'Performans',
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
          _periodHeader(),
          Expanded(child: _body(data, activeRows, idleRows)),
        ],
      ),
    );
  }

  Widget _periodHeader() {
    final rangeLabel = _period == 'day'
        ? '${_day.day} ${SalonCrmDates.months[_day.month - 1]} ${_day.year}'
        : _period == 'week'
            ? '${_weekStart.day} ${SalonCrmDates.months[_weekStart.month - 1]} – ${_weekStart.add(const Duration(days: 6)).day} ${SalonCrmDates.months[_weekStart.add(const Duration(days: 6)).month - 1]}'
            : SalonCrmDates.monthYear(_month);
    final backLabel = _period == 'day'
        ? 'Bugüne dön'
        : _period == 'week'
            ? 'Bu haftaya dön'
            : 'Bu aya dön';
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 4, 12, 8),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _PeriodChip(
                  label: 'Günlük',
                  selected: _period == 'day',
                  onTap: _loading ? null : () => _setPeriod('day'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _PeriodChip(
                  label: 'Haftalık',
                  selected: _period == 'week',
                  onTap: _loading ? null : () => _setPeriod('week'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _PeriodChip(
                  label: 'Aylık',
                  selected: _period == 'month',
                  onTap: _loading ? null : () => _setPeriod('month'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          CrmSoftCard(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
            child: Row(
              children: [
                IconButton(
                  onPressed: _loading ? null : () => _shiftRange(-1),
                  icon: const Icon(Icons.chevron_left_rounded, size: 22),
                  color: SalonCrmTheme.ink,
                ),
                Expanded(
                  child: Column(
                    children: [
                      Text(
                        rangeLabel,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 15,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                      if (!_isCurrentRange)
                        GestureDetector(
                          onTap: _loading ? null : _goCurrentRange,
                          child: Text(
                            backLabel,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF6366F1),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: _loading ? null : () => _shiftRange(1),
                  icon: const Icon(Icons.chevron_right_rounded, size: 22),
                  color: SalonCrmTheme.ink,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _body(
    SalonCrmPerformance? data,
    List<SalonCrmStaffPerfItem> activeRows,
    List<SalonCrmStaffPerfItem> idleRows,
  ) {
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

    return RefreshIndicator(
      color: SalonCrmTheme.accent,
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 28),
        children: [
          if (data != null) ...[
            _summaryHero(data),
            const SizedBox(height: 12),
            _attendanceRow(data),
            const SizedBox(height: 8),
            _financeRow(data),
            const SizedBox(height: 8),
            _statRow(data),
            const SizedBox(height: 12),
            _paymentsCard(data),
            const SizedBox(height: 12),
            _shareRow(data),
            const SizedBox(height: 18),
          ],
          const CrmSectionLabel('Ekip performansı'),
          if (activeRows.isEmpty && idleRows.isEmpty)
            CrmSoftCard(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      color: const Color(0xFFEEF2FF),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: const Icon(
                      Icons.bar_chart_rounded,
                      color: Color(0xFF6366F1),
                      size: 28,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    _period == 'day'
                        ? 'Bugün için veri yok'
                        : _period == 'week'
                            ? 'Bu hafta için veri yok'
                            : 'Bu ay için veri yok',
                    style: SalonCrmTheme.titleMd.copyWith(fontSize: 16),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Tamamlanan randevu veya kasa kaydı olunca burada görünür.',
                    textAlign: TextAlign.center,
                    style: SalonCrmTheme.caption,
                  ),
                ],
              ),
            )
          else ...[
            if (activeRows.isEmpty)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: CrmSoftCard(
                  padding: const EdgeInsets.all(14),
                  child: Text(
                    _period == 'day'
                        ? 'Bugün henüz tamamlanan iş yok.'
                        : _period == 'week'
                            ? 'Bu hafta henüz tamamlanan iş yok.'
                            : 'Bu ay henüz tamamlanan iş yok.',
                    style: SalonCrmTheme.body,
                  ),
                ),
              ),
            ...activeRows.map(
              (item) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: _StaffPerfCard(item: item, money: _money),
              ),
            ),
            if (idleRows.isNotEmpty) ...[
              const SizedBox(height: 6),
              const CrmSectionLabel('Hareket yok'),
              ...idleRows.map(
                (item) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: _IdleStaffCard(item: item),
                ),
              ),
            ],
          ],
        ],
      ),
    );
  }

  Widget _summaryHero(SalonCrmPerformance data) {
    final ciro = data.ledgerExpense > 0 ? data.net : data.totalRevenue;
    final ciroLabel = data.ledgerExpense > 0 ? 'Net kasa' : 'Toplam ciro';
    final earnings = _myEarnings(data);
    return CrmSoftCard(
      padding: const EdgeInsets.all(18),
      color: const Color(0xFFEEF2FF),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: const Color(0xFFC7D2FE),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.insights_rounded,
                  color: Color(0xFF4338CA),
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      ciroLabel,
                      style: SalonCrmTheme.caption.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    Text(
                      _money(ciro),
                      style: const TextStyle(
                        fontSize: 26,
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.6,
                        color: Color(0xFF4338CA),
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: SalonCrmTheme.surface.withValues(alpha: 0.9),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '${data.came} geldi',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: SalonCrmTheme.inkSoft,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: SalonCrmTheme.surface.withValues(alpha: 0.85),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                const Icon(
                  Icons.account_balance_wallet_rounded,
                  size: 18,
                  color: Color(0xFF059669),
                ),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'Toplam kazancım',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: SalonCrmTheme.inkSoft,
                    ),
                  ),
                ),
                Text(
                  _money(earnings),
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF059669),
                  ),
                ),
              ],
            ),
          ),
          if (data.ledgerExpense > 0) ...[
            const SizedBox(height: 8),
            Text(
              'Gelir ${_money(data.appointmentRevenue + data.ledgerIncome)} − Gider ${_money(data.ledgerExpense)}',
              style: SalonCrmTheme.caption,
            ),
          ],
        ],
      ),
    );
  }

  Widget _attendanceRow(SalonCrmPerformance data) {
    return Row(
      children: [
        Expanded(
          child: _MiniStat(
            label: 'Geldi',
            value: '${data.came}',
            color: SalonCrmTheme.success,
            bg: SalonCrmTheme.successSoft,
            icon: Icons.person_rounded,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _MiniStat(
            label: 'Gelmedi',
            value: '${data.didNotCome}',
            color: SalonCrmTheme.danger,
            bg: SalonCrmTheme.dangerSoft,
            icon: Icons.person_off_rounded,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _MiniStat(
            label: 'Tekrarlayan',
            value: '${data.repeatCustomers}',
            color: const Color(0xFF7C3AED),
            bg: const Color(0xFFF5F3FF),
            icon: Icons.replay_rounded,
          ),
        ),
      ],
    );
  }

  Widget _financeRow(SalonCrmPerformance data) {
    return Row(
      children: [
        Expanded(
          child: _MiniStat(
            label: 'Diğer gelir',
            value: _money(data.ledgerIncome),
            color: SalonCrmTheme.success,
            bg: SalonCrmTheme.successSoft,
            icon: Icons.trending_up_rounded,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _MiniStat(
            label: 'Kasa gider',
            value: _money(data.ledgerExpense),
            color: const Color(0xFFDC2626),
            bg: const Color(0xFFFEF2F2),
            icon: Icons.trending_down_rounded,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _MiniStat(
            label: data.needsOutcome > 0 ? 'Sonuç yok' : 'Müşteri',
            value: data.needsOutcome > 0
                ? '${data.needsOutcome}'
                : '${data.uniqueCustomers}',
            color: data.needsOutcome > 0
                ? const Color(0xFFD97706)
                : const Color(0xFF2563EB),
            bg: data.needsOutcome > 0
                ? const Color(0xFFFFF7ED)
                : const Color(0xFFEFF6FF),
            icon: data.needsOutcome > 0
                ? Icons.help_outline_rounded
                : Icons.groups_rounded,
          ),
        ),
      ],
    );
  }

  Widget _statRow(SalonCrmPerformance data) {
    return Row(
      children: [
        Expanded(
          child: _MiniStat(
            label: 'Tamamlanan',
            value: '${data.completed}',
            color: SalonCrmTheme.success,
            bg: SalonCrmTheme.successSoft,
            icon: Icons.check_circle_rounded,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _MiniStat(
            label: 'Planlanan',
            value: '${data.scheduled}',
            color: const Color(0xFF2563EB),
            bg: const Color(0xFFEFF6FF),
            icon: Icons.event_available_rounded,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _MiniStat(
            label: 'Randevu',
            value: _money(data.appointmentRevenue),
            color: const Color(0xFFD97706),
            bg: const Color(0xFFFFF7ED),
            icon: Icons.payments_rounded,
          ),
        ),
      ],
    );
  }

  Widget _paymentsCard(SalonCrmPerformance data) {
    final p = data.payments;
    return CrmSoftCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: const Color(0xFFECFDF5),
                  borderRadius: BorderRadius.circular(11),
                ),
                child: const Icon(
                  Icons.account_balance_wallet_rounded,
                  size: 18,
                  color: Color(0xFF059669),
                ),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Ödeme dağılımı',
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                    color: SalonCrmTheme.ink,
                  ),
                ),
              ),
              Text(
                _money(p.collected),
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 14,
                  color: Color(0xFF059669),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Tahsil edilen (nakit + kart + IBAN)',
            style: SalonCrmTheme.caption,
          ),
          const SizedBox(height: 12),
          _PaymentLine(
            label: 'Nakit',
            amount: p.cash,
            count: p.cashCount,
            color: const Color(0xFF059669),
            money: _money,
          ),
          const SizedBox(height: 8),
          _PaymentLine(
            label: 'Kredi kartı',
            amount: p.card,
            count: p.cardCount,
            color: const Color(0xFF2563EB),
            money: _money,
          ),
          const SizedBox(height: 8),
          _PaymentLine(
            label: 'IBAN',
            amount: p.iban,
            count: p.ibanCount,
            color: const Color(0xFF7C3AED),
            money: _money,
          ),
          const SizedBox(height: 8),
          _PaymentLine(
            label: 'Veresiye',
            amount: p.credit,
            count: p.creditCount,
            color: const Color(0xFFD97706),
            money: _money,
          ),
        ],
      ),
    );
  }

  Widget _shareRow(SalonCrmPerformance data) {
    // Patron için “salon payı” yanıltıcı; asıl bilgi personel komisyonu ve salona kalan.
    final showStaffCut = data.staffShare > 0;
    final showOwnerRemain = data.ownerShare > 0 || showStaffCut;
    if (!showStaffCut && data.credit <= 0 && data.ledgerIncome <= 0) {
      return const SizedBox.shrink();
    }

    return CrmSoftCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        children: [
          if (showOwnerRemain) ...[
            _ShareLine(
              label: 'Salona kalan',
              value: _money(data.ownerShare),
              color: SalonCrmTheme.ink,
            ),
            const SizedBox(height: 2),
            Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'Tahsil edilen − personel komisyonu',
                style: SalonCrmTheme.caption,
              ),
            ),
          ],
          if (showStaffCut) ...[
            if (showOwnerRemain) const SizedBox(height: 8),
            _ShareLine(
              label: 'Personel komisyonu',
              value: _money(data.staffShare),
              color: const Color(0xFF059669),
            ),
          ],
          if (data.credit > 0) ...[
            const SizedBox(height: 8),
            _ShareLine(
              label: 'Veresiye',
              value: _money(data.credit),
              color: const Color(0xFFD97706),
            ),
          ],
          if (data.ledgerIncome > 0) ...[
            const SizedBox(height: 8),
            _ShareLine(
              label: 'Diğer kasa geliri',
              value: _money(data.ledgerIncome),
              color: const Color(0xFF6366F1),
            ),
          ],
        ],
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  const _MiniStat({
    required this.label,
    required this.value,
    required this.color,
    required this.bg,
    required this.icon,
  });

  final String label;
  final String value;
  final Color color;
  final Color bg;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.12)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(height: 8),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: color.withValues(alpha: 0.85),
            ),
          ),
        ],
      ),
    );
  }
}

class _ShareLine extends StatelessWidget {
  const _ShareLine({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w600,
              color: SalonCrmTheme.inkSoft,
            ),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _StaffPerfCard extends StatelessWidget {
  const _StaffPerfCard({
    required this.item,
    required this.money,
  });

  final SalonCrmStaffPerfItem item;
  final String Function(double) money;

  bool get _isOwnerSelf => item.staffId == null;

  @override
  Widget build(BuildContext context) {
    return CrmSoftCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: _isOwnerSelf
                      ? SalonCrmTheme.accentSoft
                      : const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Text(
                  item.staffName.isNotEmpty
                      ? item.staffName[0].toUpperCase()
                      : '?',
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 16,
                    color: _isOwnerSelf
                        ? SalonCrmTheme.ink
                        : const Color(0xFF047857),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.staffName,
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 15,
                        color: SalonCrmTheme.ink,
                      ),
                    ),
                    if (!_isOwnerSelf && item.commissionPercent > 0)
                      Text(
                        'Komisyon %${item.commissionPercent.toStringAsFixed(0)}',
                        style: SalonCrmTheme.caption,
                      ),
                  ],
                ),
              ),
              Text(
                money(item.totalRevenue),
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 15,
                  color: SalonCrmTheme.ink,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: [
              _PerfTag(
                label: '${item.completed} tamam',
                color: SalonCrmTheme.success,
                bg: SalonCrmTheme.successSoft,
              ),
              if (item.scheduled > 0)
                _PerfTag(
                  label: '${item.scheduled} plan',
                  color: const Color(0xFF2563EB),
                  bg: const Color(0xFFEFF6FF),
                ),
              if (item.cancelled > 0)
                _PerfTag(
                  label: '${item.cancelled} iptal',
                  color: SalonCrmTheme.danger,
                  bg: SalonCrmTheme.dangerSoft,
                ),
              if (item.noShow > 0)
                _PerfTag(
                  label: '${item.noShow} gelmedi',
                  color: const Color(0xFFD97706),
                  bg: const Color(0xFFFFF7ED),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: SalonCrmTheme.bg,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                _MiniLine(
                  label: 'Randevu cirosu',
                  value: money(item.appointmentRevenue),
                ),
                if (item.ownerShare > 0 || item.staffShare > 0) ...[
                  const SizedBox(height: 6),
                  _MiniLine(
                    label: 'Salona kalan',
                    value: money(item.ownerShare),
                  ),
                  const SizedBox(height: 6),
                  _MiniLine(
                    label: 'Personel komisyonu',
                    value: money(item.staffShare),
                    color: const Color(0xFF059669),
                  ),
                ],
                if (item.credit > 0) ...[
                  const SizedBox(height: 6),
                  _MiniLine(
                    label: 'Veresiye',
                    value: money(item.credit),
                    color: const Color(0xFFD97706),
                  ),
                ],
                if (item.ledgerIncome > 0) ...[
                  const SizedBox(height: 6),
                  _MiniLine(
                    label: 'Diğer kasa',
                    value: money(item.ledgerIncome),
                    color: const Color(0xFF6366F1),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _IdleStaffCard extends StatelessWidget {
  const _IdleStaffCard({required this.item});

  final SalonCrmStaffPerfItem item;

  @override
  Widget build(BuildContext context) {
    return CrmSoftCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      color: SalonCrmTheme.surface.withValues(alpha: 0.7),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: SalonCrmTheme.bgDeep,
              borderRadius: BorderRadius.circular(11),
            ),
            child: Text(
              item.staffName.isNotEmpty
                  ? item.staffName[0].toUpperCase()
                  : '?',
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                color: SalonCrmTheme.muted,
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              item.staffName,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                color: SalonCrmTheme.inkSoft,
              ),
            ),
          ),
          Text(
            item.isActive ? 'Aktif' : 'Pasif',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: item.isActive ? SalonCrmTheme.muted : SalonCrmTheme.danger,
            ),
          ),
        ],
      ),
    );
  }
}

class _PerfTag extends StatelessWidget {
  const _PerfTag({
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

class _MiniLine extends StatelessWidget {
  const _MiniLine({
    required this.label,
    required this.value,
    this.color = SalonCrmTheme.ink,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 12.5,
              fontWeight: FontWeight.w600,
              color: SalonCrmTheme.muted,
            ),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _PaymentLine extends StatelessWidget {
  const _PaymentLine({
    required this.label,
    required this.amount,
    required this.count,
    required this.color,
    required this.money,
  });

  final String label;
  final double amount;
  final int count;
  final Color color;
  final String Function(double) money;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: SalonCrmTheme.inkSoft,
            ),
          ),
        ),
        if (count > 0)
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: Text(
              '$count adet',
              style: SalonCrmTheme.caption,
            ),
          ),
        Text(
          money(amount),
          style: TextStyle(
            fontSize: 13.5,
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _PeriodChip extends StatelessWidget {
  const _PeriodChip({
    required this.label,
    required this.selected,
    this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: selected ? const Color(0xFFEEF2FF) : SalonCrmTheme.surface,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: selected ? const Color(0xFF6366F1) : SalonCrmTheme.line,
            ),
          ),
          child: Text(
            label,
            style: TextStyle(
              fontWeight: FontWeight.w800,
              fontSize: 13,
              color: selected ? const Color(0xFF4338CA) : SalonCrmTheme.inkSoft,
            ),
          ),
        ),
      ),
    );
  }
}
