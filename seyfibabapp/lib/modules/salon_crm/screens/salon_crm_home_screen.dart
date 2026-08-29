import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../../main_page/main_controller.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_dates.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';
import 'salon_crm_appointments_screen.dart';
import 'salon_crm_ledger_screen.dart';
import 'salon_crm_performance_screen.dart';

class SalonCrmHomeScreen extends StatefulWidget {
  const SalonCrmHomeScreen({super.key});

  @override
  State<SalonCrmHomeScreen> createState() => _SalonCrmHomeScreenState();
}

class _SalonCrmHomeScreenState extends State<SalonCrmHomeScreen> {
  final _service = SalonCrmService();
  bool _loading = true;
  String? _error;
  SalonCrmStatus? _status;
  String _role = 'owner';
  String _displayName = '';
  int _tab = 1; // Panel (Menü = 0)
  String _crmToken = '';
  bool _canWrite = false;
  List<SalonCrmAppointmentItem> _todayItems = [];

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
      final session = await SalonCrmSession.read();
      final token = session?['token'] ?? '';
      if (token.isEmpty) {
        if (!mounted) return;
        _goToMainHub();
        return;
      }
      final status = await _service.fetchStatus(token);
      final todayKey = SalonCrmDates.dateKey();
      final today = await _service.fetchAppointments(token, date: todayKey);
      if (!mounted) return;
      setState(() {
        _status = status;
        _role = session?['role'] ?? 'owner';
        _displayName = session?['display_name'] ?? '';
        _crmToken = token;
        _canWrite = status.access.canWrite;
        _todayItems = today.appointments;
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

  Future<void> _logout() async {
    final token = (await SalonCrmSession.token()) ?? '';
    if (token.isNotEmpty) {
      await _service.clearPushToken(token);
    }
    await SalonCrmSession.clear();
    if (!mounted) return;
    _goToMainHub();
  }

  void _goToMainHub() {
    MainController().naveListener.sink.add(0);
    final nav = Navigator.of(context);
    var foundMain = false;
    nav.popUntil((route) {
      if (route.settings.name == RouteNames.mainPage) {
        foundMain = true;
        return true;
      }
      return route.isFirst;
    });
    if (!foundMain && mounted) {
      nav.pushNamedAndRemoveUntil(
        RouteNames.mainPage,
        (route) => false,
      );
    }
  }

  Future<void> _openCreate({
    required DateTime day,
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
        initialDay: existing?.startsAt?.toLocal() ?? day,
        initialHour: existing?.startsAt?.toLocal().hour ?? hour,
        existing: existing,
        forceStaffId: _role == 'staff' ? _status?.staff?.id : null,
      ),
    );
    if (created == true && mounted) await _load();
  }

  List<SalonCrmAppointmentItem> get _todayMine {
    return _todayItems.where((e) {
      if (e.status == 'cancelled' || e.status == 'no_show') return false;
      if (_role == 'staff') {
        return e.staffId != null && e.staffId == _status?.staff?.id;
      }
      return e.staffId == null;
    }).toList();
  }

  Future<void> _openDetail(SalonCrmAppointmentItem item) async {
    if (item.isBlock == false && item.status == 'cancelled') return;
    final result = await showSalonCrmAppointmentDetail(
      context,
      item: item,
      service: _service,
      token: _crmToken,
      canWrite: _canWrite,
      salonName: _status?.salon?.name ?? '',
      viewerRole: _role,
      viewerStaffId: _status?.staff?.id,
    );
    if (!mounted) return;
    if (result == 'reload') {
      await _load();
    } else if (result == 'reschedule') {
      await _openCreate(
        day: item.startsAt?.toLocal() ?? SalonCrmDates.now(),
        existing: item,
      );
    }
  }

  Future<void> _onHourTap({
    required DateTime day,
    required int hour,
    required List<SalonCrmAppointmentItem> mine,
  }) async {
    final slot = mine.where((e) {
      final t = e.startsAt?.toLocal();
      return t != null &&
          t.hour == hour &&
          e.status != 'cancelled' &&
          e.status != 'no_show';
    }).toList();
    if (slot.isEmpty) {
      await _openCreate(day: day, hour: hour);
      return;
    }
    await _openDetail(slot.first);
  }

  String _greeting() {
    final hour = SalonCrmDates.now().hour;
    if (hour < 6) return 'İyi geceler';
    if (hour < 12) return 'Günaydın';
    if (hour < 18) return 'İyi günler';
    return 'İyi akşamlar';
  }

  @override
  Widget build(BuildContext context) {
    final isOwner = _role == 'owner';
    final tabs = <CrmBottomNavItem>[
      const CrmBottomNavItem(
        icon: Icons.apps_outlined,
        activeIcon: Icons.apps_rounded,
        label: 'Menü',
      ),
      const CrmBottomNavItem(
        icon: Icons.home_outlined,
        activeIcon: Icons.home_rounded,
        label: 'Panel',
      ),
      const CrmBottomNavItem(
        icon: Icons.calendar_month_outlined,
        activeIcon: Icons.calendar_month_rounded,
        label: 'Randevu',
      ),
      if (isOwner)
        const CrmBottomNavItem(
          icon: Icons.account_balance_wallet_outlined,
          activeIcon: Icons.account_balance_wallet_rounded,
          label: 'Kasa',
        ),
      const CrmBottomNavItem(
        icon: Icons.bar_chart_outlined,
        activeIcon: Icons.bar_chart_rounded,
        label: 'Performans',
      ),
    ];
    final safeTab = _tab < 1 ? 1 : _tab.clamp(1, tabs.length - 1);
    final pages = <Widget>[
      _buildPanel(isOwner),
      const SalonCrmAppointmentsScreen(embedded: true),
      if (isOwner) const SalonCrmLedgerScreen(embedded: true),
      const SalonCrmPerformanceScreen(embedded: true),
    ];

    return Scaffold(
      backgroundColor: SalonCrmTheme.bg,
      bottomNavigationBar: CrmBottomNav(
        items: tabs,
        currentIndex: safeTab,
        onTap: (i) {
          if (i == 0) {
            _goToMainHub();
            return;
          }
          setState(() => _tab = i);
        },
      ),
      body: IndexedStack(
        index: safeTab - 1,
        children: pages,
      ),
    );
  }

  Widget _buildMenu(bool isOwner) {
    return CrmScaffold(
      title: 'Menü',
      onBack: _goToMainHub,
      actions: [
        IconButton(
          onPressed: _logout,
          icon: const Icon(Icons.logout_rounded, size: 22),
          color: SalonCrmTheme.ink,
          tooltip: 'Çıkış',
        ),
      ],
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
        children: [
          CrmSoftCard(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: SalonCrmTheme.accentSoft,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Icon(
                    Icons.content_cut_rounded,
                    color: SalonCrmTheme.ink,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _status?.salon?.name ?? 'Salon',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        _displayName.isEmpty
                            ? (_role == 'staff' ? 'Personel' : 'Patron')
                            : _displayName,
                        style: SalonCrmTheme.caption,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          if (isOwner) ...[
            CrmMenuTile(
              icon: Icons.people_rounded,
              title: 'Müşteriler',
              subtitle: 'Kayıtlı müşteri listesi',
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmCustomersScreen,
              ),
            ),
            CrmMenuTile(
              icon: Icons.badge_rounded,
              title: 'Personel',
              subtitle: 'Ekip ve yetkiler',
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmStaffScreen,
              ),
            ),
            CrmMenuTile(
              icon: Icons.content_cut_rounded,
              title: 'Hizmetler',
              subtitle: 'İsim, süre ve fiyat',
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmServicesScreen,
              ),
            ),
            CrmMenuTile(
              icon: Icons.qr_code_2_rounded,
              title: 'Müşteri kodu',
              subtitle: 'QR ve bağlantı kodu',
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmCustomerCodeScreen,
              ),
            ),
            CrmMenuTile(
              icon: Icons.event_available_rounded,
              title: 'Takvim paylaş',
              subtitle: 'Müşteri dolu saatleri linkten görür',
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmCalendarShareScreen,
              ),
            ),
            CrmMenuTile(
              icon: Icons.storefront_rounded,
              title: 'Salon profili',
              subtitle: 'Ad, fotoğraf ve saatler',
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmProfileScreen,
              ),
            ),
          ],
          if (!isOwner && _status?.staff != null) ...[
            CrmMenuTile(
              icon: Icons.people_rounded,
              title: 'Müşterilerim',
              subtitle: 'Sadece kendi müşterilerin',
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmCustomersScreen,
              ),
            ),
            CrmMenuTile(
              icon: Icons.event_available_rounded,
              title: 'Takvimimi paylaş',
              subtitle: 'Müşteri dolu saatleri linkten görür',
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmCalendarShareScreen,
              ),
            ),
            CrmMenuTile(
              icon: Icons.payments_outlined,
              title: 'Maaşım',
              subtitle: 'Ödeme onayları',
              onTap: () {
                final s = _status!.staff!;
                Navigator.pushNamed(
                  context,
                  RouteNames.salonCrmStaffDetailScreen,
                  arguments: {
                    'staff_id': s.id,
                    'is_owner': false,
                  },
                );
              },
            ),
            CrmMenuTile(
              icon: Icons.photo_camera_outlined,
              title: 'Fotoğrafım',
              subtitle: 'Müşterilere görünürlük',
              onTap: () {
                final s = _status!.staff!;
                Navigator.pushNamed(
                  context,
                  RouteNames.salonCrmMyPhotoScreen,
                  arguments: {
                    'staff_id': s.id,
                    'staff_name': s.name,
                    'photo': s.photo,
                    'show_photo_to_customers': s.showPhotoToCustomers,
                    'can_write': _status!.access.canWrite,
                  },
                );
              },
            ),
          ],
          CrmMenuTile(
            icon: Icons.shopping_bag_rounded,
            title: 'Alışverişe dön',
            subtitle: 'Seyfibaba mağaza',
            onTap: _goToMainHub,
          ),
          CrmMenuTile(
            icon: Icons.logout_rounded,
            title: 'CRM çıkış',
            subtitle: 'Salon oturumunu kapat',
            onTap: _logout,
          ),
        ],
      ),
    );
  }

  Widget _buildPanel(bool isOwner) {
    return CrmScaffold(
      title: _role == 'staff' ? 'Personel paneli' : 'Salon paneli',
      onBack: _goToMainHub,
      actions: [
        IconButton(
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh_rounded, size: 22),
          color: SalonCrmTheme.ink,
        ),
        TextButton(
          onPressed: _logout,
          child: const Text(
            'Çıkış',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: SalonCrmTheme.inkSoft,
            ),
          ),
        ),
      ],
      body: _body(),
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
                  size: 48, color: SalonCrmTheme.danger),
              const SizedBox(height: 12),
              Text(_error!,
                  textAlign: TextAlign.center, style: SalonCrmTheme.body),
              const SizedBox(height: 16),
              CrmPrimaryButton(
                label: 'Tekrar dene',
                icon: Icons.refresh_rounded,
                onPressed: _load,
              ),
            ],
          ),
        ),
      );
    }

    final status = _status;
    if (status == null || !status.hasSalon) {
      return const Center(child: Text('Salon bulunamadı. Tekrar giriş yapın.'));
    }

    final isOwner = _role == 'owner';

    // İstatistikler
    final activeItems = _todayItems
        .where((e) =>
            !e.isBlock &&
            e.status != 'cancelled' &&
            e.status != 'no_show')
        .toList();
    final completedItems =
        activeItems.where((e) => e.status == 'completed').toList();
    final pendingItems =
        activeItems.where((e) => e.status == 'pending').toList();
    final todayRevenue = completedItems.fold<double>(
      0,
      (sum, e) => sum + e.price,
    );
    final now = SalonCrmDates.now();
    final needsOutcomeItems = _todayItems.where((e) {
      if (e.isBlock) return false;
      if (e.status != 'scheduled' && e.status != 'pending') return false;
      final start = e.startsAt;
      if (start == null) return false;
      final end = start.add(Duration(minutes: e.durationMinutes));
      return end.isBefore(now);
    }).toList();

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
      children: [
        // Karşılama
        Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFFD54F), Color(0xFFFFB300)],
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Center(
                child: Text(
                  _displayName.isNotEmpty
                      ? _displayName[0].toUpperCase()
                      : (isOwner ? 'P' : 'S'),
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _greeting(),
                    style: SalonCrmTheme.caption,
                  ),
                  Text(
                    _displayName.isEmpty
                        ? (status.salon?.name ?? 'Salon')
                        : _displayName,
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                      color: SalonCrmTheme.ink,
                      letterSpacing: -0.3,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        if (needsOutcomeItems.isNotEmpty) ...[
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF7ED),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFFDBA74)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.warning_amber_rounded,
                    color: Color(0xFFD97706), size: 22),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    needsOutcomeItems.length == 1
                        ? '1 randevu süresi geçti; geldi / gelmedi bilgisi girilmedi.'
                        : '${needsOutcomeItems.length} randevu süresi geçti; geldi / gelmedi bilgisi girilmedi.',
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                      color: SalonCrmTheme.ink,
                      height: 1.35,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 20),

        // İstatistik Kartları
        Row(
          children: [
            _StatCard(
              icon: Icons.calendar_today_rounded,
              label: 'Randevu',
              value: '${activeItems.length}',
              color: const Color(0xFF3B82F6),
              bgColor: const Color(0xFFEFF6FF),
            ),
            const SizedBox(width: 10),
            _StatCard(
              icon: Icons.check_circle_rounded,
              label: 'Tamamlanan',
              value: '${completedItems.length}',
              color: SalonCrmTheme.success,
              bgColor: SalonCrmTheme.successSoft,
            ),
            const SizedBox(width: 10),
            _StatCard(
              icon: Icons.hourglass_bottom_rounded,
              label: 'Bekleyen',
              value: '${pendingItems.length}',
              color: const Color(0xFFD97706),
              bgColor: const Color(0xFFFFF7ED),
            ),
          ],
        ),
        const SizedBox(height: 10),
        if (isOwner) ...[
          _StaffManageBanner(
            onTap: () => Navigator.pushNamed(
              context,
              RouteNames.salonCrmStaffScreen,
            ),
          ),
          const SizedBox(height: 10),
        ],
        if (todayRevenue > 0 || isOwner)
          CrmSoftCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            color: const Color(0xFFF0FDF4),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: SalonCrmTheme.successSoft,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.payments_rounded,
                      color: SalonCrmTheme.success, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Bugünkü gelir',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: SalonCrmTheme.inkSoft,
                        ),
                      ),
                      Text(
                        '${todayRevenue.toStringAsFixed(0)} ₺',
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                          color: SalonCrmTheme.success,
                          letterSpacing: -0.5,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '${completedItems.length} işlem',
                  style: SalonCrmTheme.caption,
                ),
              ],
            ),
          ),
        const SizedBox(height: 12),

        // Erişim durumu - küçük rozet
        _AccessBadge(
          status: status,
          onTap: () => Navigator.pushNamed(
            context,
            RouteNames.salonCrmProfileScreen,
          ),
        ),
        const SizedBox(height: 16),

        // Bugünkü ekip iş özeti
        if (isOwner) ...[
          _TodayStaffWork(
            items: _todayItems,
            onManage: () => Navigator.pushNamed(
              context,
              RouteNames.salonCrmStaffScreen,
            ),
          ),
          const SizedBox(height: 14),
        ],

        // Bugünkü randevu tablosu
        _DayCustomerTable(
          title: isOwner ? 'Bugünkü randevularım' : 'Bugünkü randevuların',
          day: SalonCrmDates.today(),
          items: _todayMine,
          canWrite: _canWrite,
          openHour: status.salon?.openHour ?? 9,
          closeHour: status.salon?.closeHour ?? 21,
          onAddHour: (hour) => _onHourTap(
            day: SalonCrmDates.today(),
            hour: hour,
            mine: _todayMine,
          ),
          onOpenItem: _openDetail,
        ),
        const SizedBox(height: 22),

        // Hızlı erişim
        const _SectionHeader(title: 'Hızlı Erişim', icon: Icons.bolt_rounded),
        const SizedBox(height: 10),
        _QuickActionGrid(
          actions: [
            if (isOwner) ...[
              _QuickAction(
                icon: Icons.content_cut_rounded,
                label: 'Hizmetler',
                color: const Color(0xFF8B5CF6),
                onTap: () => Navigator.pushNamed(
                  context,
                  RouteNames.salonCrmServicesScreen,
                ),
              ),
              _QuickAction(
                icon: Icons.qr_code_2_rounded,
                label: 'Müşteri Kodu',
                color: const Color(0xFF0EA5E9),
                onTap: () => Navigator.pushNamed(
                  context,
                  RouteNames.salonCrmCustomerCodeScreen,
                ),
              ),
              _QuickAction(
                icon: Icons.event_available_rounded,
                label: 'Takvim paylaş',
                color: const Color(0xFF7C3AED),
                onTap: () => Navigator.pushNamed(
                  context,
                  RouteNames.salonCrmCalendarShareScreen,
                ),
              ),
              _QuickAction(
                icon: Icons.storefront_rounded,
                label: 'Salon Profili',
                color: const Color(0xFFF59E0B),
                onTap: () => Navigator.pushNamed(
                  context,
                  RouteNames.salonCrmProfileScreen,
                ),
              ),
            ],
            _QuickAction(
              icon: Icons.people_rounded,
              label: isOwner ? 'Müşteriler' : 'Müşterilerim',
              color: const Color(0xFF2563EB),
              onTap: () => Navigator.pushNamed(
                context,
                RouteNames.salonCrmCustomersScreen,
              ),
            ),
            if (!isOwner && _status?.staff != null) ...[
              _QuickAction(
                icon: Icons.event_available_rounded,
                label: 'Takvim paylaş',
                color: const Color(0xFF7C3AED),
                onTap: () => Navigator.pushNamed(
                  context,
                  RouteNames.salonCrmCalendarShareScreen,
                ),
              ),
              _QuickAction(
                icon: Icons.payments_outlined,
                label: 'Maaşım',
                color: const Color(0xFF10B981),
                onTap: () {
                  final s = _status!.staff!;
                  Navigator.pushNamed(
                    context,
                    RouteNames.salonCrmStaffDetailScreen,
                    arguments: {
                      'staff_id': s.id,
                      'is_owner': false,
                    },
                  );
                },
              ),
              _QuickAction(
                icon: Icons.photo_camera_outlined,
                label: 'Fotoğrafım',
                color: const Color(0xFFEC4899),
                onTap: () {
                  final s = _status!.staff!;
                  Navigator.pushNamed(
                    context,
                    RouteNames.salonCrmMyPhotoScreen,
                    arguments: {
                      'staff_id': s.id,
                      'staff_name': s.name,
                      'photo': s.photo,
                      'show_photo_to_customers': s.showPhotoToCustomers,
                      'can_write': _status!.access.canWrite,
                    },
                  );
                },
              ),
            ],
            _QuickAction(
              icon: Icons.shopping_bag_rounded,
              label: 'Alışveriş',
              color: const Color(0xFFEF4444),
              onTap: _goToMainHub,
            ),
            _QuickAction(
              icon: Icons.calendar_month_rounded,
              label: 'Tüm Randevular',
              color: const Color(0xFF6366F1),
              onTap: () => setState(() => _tab = 2),
            ),
          ],
        ),
        if (!status.access.canWrite) ...[
          const SizedBox(height: 12),
          CrmSoftCard(
            color: SalonCrmTheme.dangerSoft,
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                const Icon(Icons.lock_rounded,
                    color: SalonCrmTheme.danger, size: 22),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'CRM Kilitli',
                        style: TextStyle(
                          fontWeight: FontWeight.w700,
                          color: SalonCrmTheme.danger,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Alışveriş yaparak CRM\'i aktif edin. Geçmiş veriler korunur.',
                        style: SalonCrmTheme.caption,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),
          CrmPrimaryButton(
            label: 'Alışverişe git',
            icon: Icons.shopping_bag_outlined,
            onPressed: () {
              Navigator.pop(context);
              MainController().naveListener.sink.add(1);
            },
          ),
        ],
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
    required this.bgColor,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color color;
  final Color bgColor;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: CrmSoftCard(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: bgColor,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: color, size: 18),
            ),
            const SizedBox(height: 10),
            Text(
              value,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: color,
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: SalonCrmTheme.muted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title, required this.icon});

  final String title;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18, color: SalonCrmTheme.inkSoft),
        const SizedBox(width: 8),
        Text(
          title,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: SalonCrmTheme.ink,
            letterSpacing: -0.2,
          ),
        ),
      ],
    );
  }
}

class _QuickAction {
  const _QuickAction({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
}

class _QuickActionGrid extends StatelessWidget {
  const _QuickActionGrid({required this.actions});

  final List<_QuickAction> actions;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: actions.map((action) {
        return SizedBox(
          width: (MediaQuery.of(context).size.width - 60) / 3,
          child: Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: action.onTap,
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 16),
                decoration: BoxDecoration(
                  color: SalonCrmTheme.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: SalonCrmTheme.line.withValues(alpha: 0.8),
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.03),
                      blurRadius: 8,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: action.color.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Icon(action.icon, color: action.color, size: 22),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      action.label,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: SalonCrmTheme.ink,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _StaffManageBanner extends StatelessWidget {
  const _StaffManageBanner({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return CrmSoftCard(
      onTap: onTap,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      color: const Color(0xFFECFDF5),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF6EE7B7), Color(0xFF34D399)],
              ),
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF059669).withValues(alpha: 0.25),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: const Icon(Icons.badge_rounded, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 14),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Personel Yönetimi',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: SalonCrmTheme.ink,
                  ),
                ),
                SizedBox(height: 3),
                Text(
                  'Ekip, maaş, hizmet fiyatları ve giriş bilgileri',
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                    color: SalonCrmTheme.inkSoft,
                  ),
                ),
              ],
            ),
          ),
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: const Color(0xFFD1FAE5),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.arrow_forward_rounded,
              size: 18,
              color: Color(0xFF047857),
            ),
          ),
        ],
      ),
    );
  }
}

class _TodayStaffWork extends StatelessWidget {
  const _TodayStaffWork({
    required this.items,
    this.onManage,
  });

  final List<SalonCrmAppointmentItem> items;
  final VoidCallback? onManage;

  @override
  Widget build(BuildContext context) {
    final work = <String, _StaffWorkRow>{};
    for (final item in items) {
      if (item.isBlock) continue;
      if (item.status == 'cancelled' || item.status == 'no_show') continue;
      final key = item.staffId?.toString() ?? 'owner';
      final name = item.staffId == null
          ? 'Ben'
          : (item.staffName?.trim().isNotEmpty == true
              ? item.staffName!
              : 'Personel');
      final row = work.putIfAbsent(key, () => _StaffWorkRow(name: name));
      row.jobs += 1;
      if (item.status == 'completed') {
        row.done += 1;
        row.revenue += item.price;
      } else if (item.status == 'pending') {
        row.pending += 1;
      }
    }
    final rows = work.values.toList()
      ..sort((a, b) => a.name.compareTo(b.name));

    return CrmSoftCard(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
      color: const Color(0xFFECFDF5),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.groups_rounded,
                    color: Color(0xFF059669), size: 22),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Ekip İş Özeti',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: SalonCrmTheme.ink,
                      ),
                    ),
                    Text(
                      'Bugünkü personel performansı',
                      style: TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                        color: SalonCrmTheme.inkSoft,
                      ),
                    ),
                  ],
                ),
              ),
              if (onManage != null)
                TextButton.icon(
                  onPressed: onManage,
                  icon: const Icon(Icons.settings_rounded, size: 16),
                  label: const Text('Yönet'),
                  style: TextButton.styleFrom(
                    foregroundColor: const Color(0xFF047857),
                    textStyle: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 14),
          if (rows.isEmpty)
            Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Text('Bugün henüz iş yok.', style: SalonCrmTheme.body),
            )
          else
            ...rows.map(
              (row) => Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                decoration: BoxDecoration(
                  color: SalonCrmTheme.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: const Color(0xFF059669).withValues(alpha: 0.12),
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: const Color(0xFFD1FAE5),
                        borderRadius: BorderRadius.circular(11),
                      ),
                      child: Center(
                        child: Text(
                          row.name.isNotEmpty
                              ? row.name[0].toUpperCase()
                              : '?',
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF047857),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        row.name,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          '${row.done}/${row.jobs} tamamlandı',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: SalonCrmTheme.success,
                          ),
                        ),
                        if (row.revenue > 0)
                          Text(
                            '${row.revenue.toStringAsFixed(0)} ₺',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: SalonCrmTheme.inkSoft,
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _StaffWorkRow {
  _StaffWorkRow({required this.name});

  final String name;
  int jobs = 0;
  int done = 0;
  int pending = 0;
  double revenue = 0;
}

class _DayCustomerTable extends StatelessWidget {
  const _DayCustomerTable({
    required this.title,
    required this.day,
    required this.items,
    required this.canWrite,
    required this.openHour,
    required this.closeHour,
    required this.onAddHour,
    required this.onOpenItem,
  });

  final String title;
  final DateTime day;
  final List<SalonCrmAppointmentItem> items;
  final bool canWrite;
  final int openHour;
  final int closeHour;
  final ValueChanged<int> onAddHour;
  final ValueChanged<SalonCrmAppointmentItem> onOpenItem;

  List<SalonCrmAppointmentItem> _slotItems(int hour) {
    return items.where((e) {
      final t = e.startsAt?.toLocal();
      return t != null &&
          t.hour == hour &&
          e.status != 'cancelled' &&
          e.status != 'no_show';
    }).toList();
  }

  Color _slotColor(int hour) {
    final slot = _slotItems(hour);
    if (slot.any((e) => e.isBlock)) return SalonCrmTheme.dangerSoft;
    if (slot.any((e) => e.status == 'pending')) {
      return const Color(0xFFFFE4C7);
    }
    if (slot.isNotEmpty) return SalonCrmTheme.successSoft;
    return SalonCrmTheme.accentSoft;
  }

  Color _slotText(int hour) {
    final slot = _slotItems(hour);
    if (slot.any((e) => e.isBlock)) return SalonCrmTheme.danger;
    if (slot.any((e) => e.status == 'pending')) {
      return const Color(0xFF9A4A00);
    }
    if (slot.isNotEmpty) return SalonCrmTheme.success;
    return SalonCrmTheme.ink;
  }

  @override
  Widget build(BuildContext context) {
    final rows = [...items]..sort((a, b) {
        final at = a.startsAt ?? DateTime(2000);
        final bt = b.startsAt ?? DateTime(2000);
        return at.compareTo(bt);
      });

    return CrmSoftCard(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: SalonCrmTheme.ink,
                      ),
                    ),
                    Text(
                      SalonCrmDates.full(day),
                      style: SalonCrmTheme.caption,
                    ),
                  ],
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '${rows.where((e) => !e.isBlock).length} kişi',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 12,
                    color: Color(0xFF3B82F6),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            decoration: BoxDecoration(
              color: SalonCrmTheme.bg,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                const Padding(
                  padding: EdgeInsets.fromLTRB(10, 8, 10, 6),
                  child: Row(
                    children: [
                      SizedBox(
                        width: 52,
                        child: Text(
                          'Saat',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: SalonCrmTheme.muted,
                          ),
                        ),
                      ),
                      Expanded(
                        child: Text(
                          'Müşteri',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: SalonCrmTheme.muted,
                          ),
                        ),
                      ),
                      SizedBox(
                        width: 64,
                        child: Text(
                          'Kim',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: SalonCrmTheme.muted,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1, color: SalonCrmTheme.line),
                if (rows.isEmpty)
                  Padding(
                    padding: const EdgeInsets.all(14),
                    child: Text(
                      'Henüz müşteri yok. Aşağıdan saat seç.',
                      style: SalonCrmTheme.caption,
                    ),
                  )
                else
                  ...rows.map((item) {
                    final time = item.startsAt == null
                        ? '--:--'
                        : DateFormat('HH:mm')
                            .format(item.startsAt!.toLocal());
                    return InkWell(
                      onTap: () => onOpenItem(item),
                      child: Container(
                        color: item.isBlock
                            ? SalonCrmTheme.dangerSoft
                            : (item.status == 'pending'
                                ? const Color(0xFFFFE4C7)
                                : null),
                        padding:
                            const EdgeInsets.fromLTRB(10, 10, 10, 10),
                        child: Row(
                          children: [
                            SizedBox(
                              width: 52,
                              child: Text(
                                time,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                            Expanded(
                              child: Column(
                                crossAxisAlignment:
                                    CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item.isBlock
                                        ? (item.notes?.isNotEmpty == true
                                            ? item.notes!
                                            : 'Mola / kapalı')
                                        : item.customerName,
                                    style: TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 13.5,
                                      color: item.isBlock
                                          ? SalonCrmTheme.danger
                                          : SalonCrmTheme.ink,
                                    ),
                                  ),
                                  Text(
                                    item.isBlock
                                        ? 'Kapalı saat · ücret yok'
                                        : (item.status == 'pending'
                                            ? 'Talep · onay bekliyor'
                                            : (item.status == 'completed'
                                                ? 'Tamamlandı${item.price > 0 ? ' · ${item.price.toStringAsFixed(0)} ₺' : ''}'
                                                : (item.serviceName
                                                        .isEmpty
                                                    ? 'Onaylı'
                                                    : item
                                                        .serviceName))),
                                    style: const TextStyle(
                                      fontSize: 11.5,
                                      color: SalonCrmTheme.muted,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Text(
                              salonCrmAssigneeLabel(item),
                              textAlign: TextAlign.right,
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: SalonCrmTheme.inkSoft,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
              ],
            ),
          ),
          if (canWrite) ...[
            const SizedBox(height: 10),
            Wrap(
              spacing: 10,
              runSpacing: 6,
              children: const [
                _HourLegend(color: SalonCrmTheme.accentSoft, label: 'Boş'),
                _HourLegend(color: Color(0xFFFFE4C7), label: 'Talep'),
                _HourLegend(
                    color: SalonCrmTheme.successSoft, label: 'Onaylı'),
                _HourLegend(
                    color: SalonCrmTheme.dangerSoft, label: 'Mola'),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              'Boş saate dokun ekle, dolu saate dokun detay gör',
              style: SalonCrmTheme.caption,
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [
                for (var hour = openHour; hour <= closeHour; hour++)
                  Material(
                    color: _slotColor(hour),
                    borderRadius: BorderRadius.circular(20),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(20),
                      onTap: () => onAddHour(hour),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                        child: Text(
                          salonCrmHourLabel(hour),
                          style: TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 12.5,
                            color: _slotText(hour),
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _HourLegend extends StatelessWidget {
  const _HourLegend({required this.color, required this.label});

  final Color color;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(4),
            border: Border.all(color: SalonCrmTheme.line),
          ),
        ),
        const SizedBox(width: 6),
        Text(label, style: SalonCrmTheme.caption),
      ],
    );
  }
}

class _AccessBadge extends StatelessWidget {
  const _AccessBadge({required this.status, this.onTap});

  final SalonCrmStatus status;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final access = status.access;
    final unlocked = access.isUnlocked;

    return CrmSoftCard(
      onTap: onTap,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Icon(
            unlocked ? Icons.verified_rounded : Icons.lock_rounded,
            color: unlocked ? SalonCrmTheme.success : SalonCrmTheme.danger,
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              unlocked ? 'CRM Aktif' : 'CRM Kilitli',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 14,
                color: unlocked ? SalonCrmTheme.success : SalonCrmTheme.danger,
              ),
            ),
          ),
          Text(
            'Detay',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: SalonCrmTheme.muted,
            ),
          ),
          const SizedBox(width: 4),
          const Icon(Icons.chevron_right_rounded,
              size: 18, color: SalonCrmTheme.muted),
        ],
      ),
    );
  }
}
