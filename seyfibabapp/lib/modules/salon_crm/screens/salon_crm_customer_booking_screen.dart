import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_dates.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmCustomerBookingScreen extends StatefulWidget {
  const SalonCrmCustomerBookingScreen({super.key});

  @override
  State<SalonCrmCustomerBookingScreen> createState() =>
      _SalonCrmCustomerBookingScreenState();
}

class _SalonCrmCustomerBookingScreenState
    extends State<SalonCrmCustomerBookingScreen> {
  final _service = SalonCrmService();
  final _notesCtrl = TextEditingController();

  bool _loading = true;
  bool _saving = false;
  String? _error;
  String _crmToken = '';
  String _salonName = '';
  SalonCrmSalonProfile? _profile;
  List<SalonCrmServiceItem> _services = [];
  List<SalonCrmStaffItem> _staff = [];
  SalonCrmServiceItem? _selectedService;
  SalonCrmStaffItem? _selectedStaff;
  late DateTime _startsAt;

  @override
  void initState() {
    super.initState();
    final now = SalonCrmDates.now().add(const Duration(hours: 1));
    _startsAt = DateTime(
      now.year,
      now.month,
      now.day,
      now.hour,
      (now.minute ~/ 15) * 15,
    );
    _load();
  }

  @override
  void dispose() {
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _crmToken = (await SalonCrmSession.token()) ?? '';
      if (_crmToken.isEmpty) throw Exception('CRM girişi gerekli');
      final catalog = await _service.fetchCustomerCatalog(_crmToken);
      if (!mounted) return;
      setState(() {
        _salonName = catalog.salonName;
        _profile = catalog.salonProfile;
        _services = catalog.services.where((s) => s.isActive).toList();
        _staff = catalog.staff.where((s) => s.isActive).toList();
        _selectedService = _services.isNotEmpty ? _services.first : null;
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

  Future<void> _pickDateTime() async {
    final date = await salonCrmPickDate(
      context,
      initial: _startsAt.isAfter(SalonCrmDates.now())
          ? _startsAt
          : SalonCrmDates.now().add(const Duration(hours: 1)),
      firstDate: SalonCrmDates.today(),
      lastDate: SalonCrmDates.today().add(const Duration(days: 90)),
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
    if (_selectedService == null) {
      Utils.errorSnackBar(context, 'Hizmet seçin');
      return;
    }
    if (!_startsAt.isAfter(SalonCrmDates.now())) {
      Utils.errorSnackBar(context, 'Gelecek bir saat seçin');
      return;
    }
    if (_saving) return;
    setState(() => _saving = true);
    Utils.loadingDialog(context);
    try {
      await _service.createCustomerAppointment(
        token: _crmToken,
        startsAt: _startsAt,
        serviceId: _selectedService!.id,
        serviceName: _selectedService!.name,
        staffId: _selectedStaff?.id,
        durationMinutes: _selectedService!.durationMinutes,
        notes: _notesCtrl.text.trim(),
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        'Talebiniz alındı. Salon onaylayınca randevunuz kesinleşir.',
      );
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      setState(() => _saving = false);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final df = DateFormat('d MMM yyyy · HH:mm');
    return CrmScaffold(
      title: 'Online randevu',
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
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: SalonCrmTheme.body,
                        ),
                        TextButton(onPressed: _load, child: const Text('Yenile')),
                      ],
                    ),
                  ),
                )
              : _services.isEmpty
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text(
                          'Bu salonda henüz online randevu için hizmet yok.',
                          textAlign: TextAlign.center,
                          style: SalonCrmTheme.body,
                        ),
                      ),
                    )
                  : ListView(
                      padding: const EdgeInsets.fromLTRB(20, 8, 20, 36),
                      children: [
                        if (_profile != null &&
                            _profile!.showProfileToCustomers) ...[
                          _SalonProfileCard(profile: _profile!),
                          const SizedBox(height: 16),
                        ],
                        Text('Birkaç adımda hazır', style: SalonCrmTheme.titleMd),
                        const SizedBox(height: 8),
                        Text(
                          _salonName.isEmpty
                              ? 'Hizmetini seç, saatini ayarla.'
                              : '$_salonName için randevu oluştur.',
                          style: SalonCrmTheme.body,
                        ),
                        const SizedBox(height: 20),
                        CrmSoftCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              const CrmSectionLabel('Hizmet'),
                              DropdownButtonFormField<SalonCrmServiceItem>(
                                value: _selectedService,
                                decoration: SalonCrmTheme.field('Hizmet seç'),
                                items: _services
                                    .map(
                                      (s) => DropdownMenuItem(
                                        value: s,
                                        child: Text(
                                          '${s.name} · ${s.durationMinutes} dk'
                                          '${s.price > 0 ? ' · ${s.price.toStringAsFixed(0)} ₺' : ''}',
                                        ),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (v) =>
                                    setState(() => _selectedService = v),
                              ),
                              const SizedBox(height: 14),
                              const CrmSectionLabel('Personel'),
                              DropdownButtonFormField<SalonCrmStaffItem?>(
                                value: _selectedStaff,
                                decoration:
                                    SalonCrmTheme.field('İsteğe bağlı'),
                                items: [
                                  const DropdownMenuItem<SalonCrmStaffItem?>(
                                    value: null,
                                    child: Text('Fark etmez'),
                                  ),
                                  ..._staff.map(
                                    (s) => DropdownMenuItem<SalonCrmStaffItem?>(
                                      value: s,
                                      child: Row(
                                        children: [
                                          CircleAvatar(
                                            radius: 14,
                                            backgroundColor: SalonCrmTheme.line
                                                .withValues(alpha: 0.4),
                                            backgroundImage: s.photo != null &&
                                                    s.photo!.isNotEmpty
                                                ? NetworkImage(
                                                    RemoteUrls.imageUrl(
                                                      s.photo!,
                                                    ),
                                                  )
                                                : null,
                                            child: s.photo == null ||
                                                    s.photo!.isEmpty
                                                ? const Icon(
                                                    Icons.person,
                                                    size: 16,
                                                    color: SalonCrmTheme.muted,
                                                  )
                                                : null,
                                          ),
                                          const SizedBox(width: 10),
                                          Text(s.name),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                                onChanged: (v) =>
                                    setState(() => _selectedStaff = v),
                              ),
                              const SizedBox(height: 14),
                              const CrmSectionLabel('Tarih / saat'),
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
                                        color: SalonCrmTheme.accent
                                            .withValues(alpha: 0.55),
                                      ),
                                    ),
                                    child: Row(
                                      children: [
                                        Container(
                                          width: 44,
                                          height: 44,
                                          decoration: BoxDecoration(
                                            color: SalonCrmTheme.surface,
                                            borderRadius:
                                                BorderRadius.circular(12),
                                          ),
                                          child: const Icon(
                                            Icons.schedule_rounded,
                                            color: SalonCrmTheme.ink,
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
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
                                                df.format(_startsAt),
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
                              const SizedBox(height: 14),
                              TextField(
                                controller: _notesCtrl,
                                maxLines: 2,
                                decoration: SalonCrmTheme.field(
                                  'Not (isteğe bağlı)',
                                ),
                              ),
                              const SizedBox(height: 18),
                              CrmPrimaryButton(
                                label: 'Randevu al',
                                icon: Icons.check_rounded,
                                onPressed: _saving ? null : _submit,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
    );
  }
}

class _SalonProfileCard extends StatelessWidget {
  const _SalonProfileCard({required this.profile});

  final SalonCrmSalonProfile profile;

  @override
  Widget build(BuildContext context) {
    final coverUrl = profile.coverImage != null && profile.coverImage!.isNotEmpty
        ? RemoteUrls.imageUrl(profile.coverImage!)
        : null;
    final logoUrl = profile.logoImage != null && profile.logoImage!.isNotEmpty
        ? RemoteUrls.imageUrl(profile.logoImage!)
        : null;

    return CrmSoftCard(
      padding: EdgeInsets.zero,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (coverUrl != null && coverUrl.isNotEmpty)
            ClipRRect(
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(SalonCrmTheme.radius),
              ),
              child: Image.network(
                coverUrl,
                height: 120,
                fit: BoxFit.cover,
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: SalonCrmTheme.line.withValues(alpha: 0.4),
                  backgroundImage:
                      logoUrl != null && logoUrl.isNotEmpty
                          ? NetworkImage(logoUrl)
                          : null,
                  child: logoUrl == null || logoUrl.isEmpty
                      ? const Icon(
                          Icons.storefront_outlined,
                          color: SalonCrmTheme.muted,
                        )
                      : null,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        profile.name,
                        style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                      if (profile.phone != null &&
                          profile.phone!.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(profile.phone!, style: SalonCrmTheme.caption),
                      ],
                      if (profile.profileText != null &&
                          profile.profileText!.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(profile.profileText!, style: SalonCrmTheme.body),
                      ],
                    ],
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
