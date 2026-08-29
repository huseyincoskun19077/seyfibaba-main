import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../core/router_name.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmCustomerHomeScreen extends StatefulWidget {
  const SalonCrmCustomerHomeScreen({super.key});

  @override
  State<SalonCrmCustomerHomeScreen> createState() =>
      _SalonCrmCustomerHomeScreenState();
}

class _SalonCrmCustomerHomeScreenState
    extends State<SalonCrmCustomerHomeScreen> {
  final _service = SalonCrmService();
  String _name = '';
  String _salon = '';
  bool _loading = true;
  String? _error;
  List<SalonCrmAppointmentItem> _appointments = [];

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
      final s = await SalonCrmSession.read();
      final token = s?['token'] ?? await SalonCrmSession.token() ?? '';
      if (token.isEmpty) throw Exception('CRM girişi gerekli');
      final list = await _service.fetchCustomerAppointments(token);
      if (!mounted) return;
      setState(() {
        _name = s?['display_name'] ?? '';
        _salon = s?['salon_name'] ?? '';
        _appointments = list;
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

  Future<void> _openBooking() async {
    final created = await Navigator.pushNamed(
      context,
      RouteNames.salonCrmCustomerBookingScreen,
    );
    if (created == true) await _load();
  }

  Future<void> _logout() async {
    final token = (await SalonCrmSession.token()) ?? '';
    if (token.isNotEmpty) {
      await SalonCrmService().clearPushToken(token);
    }
    await SalonCrmSession.clear();
    if (!mounted) return;
    if (Navigator.canPop(context)) {
      Navigator.pop(context);
    } else {
      Navigator.pushNamedAndRemoveUntil(
        context,
        RouteNames.salonHubScreen,
        (route) => route.isFirst,
      );
    }
  }

  Future<void> _switchSalon() async {
    final token = (await SalonCrmSession.token()) ?? '';
    if (token.isNotEmpty) {
      await SalonCrmService().clearPushToken(token);
    }
    await SalonCrmSession.clearCustomerAll();
    if (!mounted) return;
    Navigator.pushNamedAndRemoveUntil(
      context,
      RouteNames.salonCrmCustomerLinkScreen,
      (route) => route.isFirst,
    );
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'pending':
        return 'Onay bekliyor';
      case 'completed':
        return 'Tamamlandı';
      case 'cancelled':
        return 'İptal';
      case 'no_show':
        return 'Gelmedi';
      default:
        return 'Planlandı';
    }
  }

  @override
  Widget build(BuildContext context) {
    final df = DateFormat('d MMM yyyy · HH:mm');
    return CrmScaffold(
      title: 'Müşteri',
      actions: [
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
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openBooking,
        elevation: 1,
        backgroundColor: SalonCrmTheme.accent,
        foregroundColor: SalonCrmTheme.ink,
        icon: const Icon(Icons.event_available_rounded),
        label: const Text(
          'Randevu al',
          style: TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
      body: RefreshIndicator(
        color: SalonCrmTheme.accent,
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 110),
          children: [
            Text(
              _name.isEmpty ? 'Hoş geldin' : 'Merhaba, $_name',
              style: SalonCrmTheme.titleMd,
            ),
            if (_salon.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(_salon, style: SalonCrmTheme.body),
              TextButton(
                onPressed: _switchSalon,
                child: const Text(
                  'Başka berbere geç',
                  style: TextStyle(
                    color: SalonCrmTheme.inkSoft,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
            const SizedBox(height: 20),
            CrmSoftCard(
              onTap: _openBooking,
              color: SalonCrmTheme.accentSoft.withValues(alpha: 0.65),
              child: Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: SalonCrmTheme.surface,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: const Icon(
                      Icons.calendar_month_rounded,
                      color: SalonCrmTheme.ink,
                    ),
                  ),
                  const SizedBox(width: 14),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Online randevu al',
                          style: TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 16,
                            color: SalonCrmTheme.ink,
                          ),
                        ),
                        SizedBox(height: 3),
                        Text(
                          'Hizmet ve saat seç, hemen oluştur',
                          style: TextStyle(
                            color: SalonCrmTheme.inkSoft,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Icon(
                    Icons.arrow_forward_rounded,
                    color: SalonCrmTheme.ink,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 22),
            const CrmSectionLabel('Randevularım'),
            if (_loading)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 40),
                child: Center(
                  child: CircularProgressIndicator(color: SalonCrmTheme.accent),
                ),
              )
            else if (_error != null)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 24),
                child: Column(
                  children: [
                    Text(
                      _error!,
                      textAlign: TextAlign.center,
                      style: SalonCrmTheme.body,
                    ),
                    TextButton(onPressed: _load, child: const Text('Yenile')),
                  ],
                ),
              )
            else if (_appointments.isEmpty)
              CrmSoftCard(
                child: Text(
                  'Henüz randevun yok. Yukarıdaki butondan kolayca alabilirsin.',
                  style: SalonCrmTheme.body,
                ),
              )
            else
              ..._appointments.map((a) {
                final when = a.startsAt != null
                    ? df.format(a.startsAt!.toLocal())
                    : '-';
                return Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: CrmSoftCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                a.serviceName.isEmpty
                                    ? 'Randevu'
                                    : a.serviceName,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                  color: SalonCrmTheme.ink,
                                  fontSize: 15.5,
                                ),
                              ),
                            ),
                            CrmStatusChip(
                              label: _statusLabel(a.status),
                              positive: a.status == 'completed' ||
                                  a.status == 'scheduled',
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(when, style: SalonCrmTheme.caption),
                        if (a.staffName != null &&
                            a.staffName!.isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(a.staffName!, style: SalonCrmTheme.caption),
                        ],
                      ],
                    ),
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }
}
