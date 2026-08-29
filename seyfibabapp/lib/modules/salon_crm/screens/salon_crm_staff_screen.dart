import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_session.dart';
import '../services/salon_crm_service.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmStaffScreen extends StatefulWidget {
  const SalonCrmStaffScreen({super.key});

  @override
  State<SalonCrmStaffScreen> createState() => _SalonCrmStaffScreenState();
}

class _SalonCrmStaffScreenState extends State<SalonCrmStaffScreen> {
  final _service = SalonCrmService();
  bool _loading = true;
  String? _error;
  List<SalonCrmStaffItem> _staff = [];
  bool _canWrite = true;

  int get _activeCount => _staff.where((s) => s.isActive).length;

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
      final list = await _service.fetchStaff(token);
      if (!mounted) return;
      setState(() {
        _staff = list;
        _canWrite = status.access.canWrite;
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

  Future<void> _openAddSheet() async {
    if (!_canWrite) {
      Utils.errorSnackBar(context, 'CRM kilitli');
      return;
    }

    final nameCtrl = TextEditingController();
    final userCtrl = TextEditingController();
    final passCtrl = TextEditingController();
    final commissionCtrl = TextEditingController(text: '0');
    final salaryCtrl = TextEditingController(text: '0');
    var payType = 'percent';
    var payPeriod = 'monthly';

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
              child: SingleChildScrollView(
                child: Column(
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
                            color: const Color(0xFFD1FAE5),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Icon(
                            Icons.badge_rounded,
                            color: Color(0xFF059669),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'Personel ekle',
                          style: SalonCrmTheme.titleMd.copyWith(fontSize: 18),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    TextField(
                      controller: nameCtrl,
                      decoration: SalonCrmTheme.field('Ad soyad'),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: userCtrl,
                      decoration: SalonCrmTheme.field('Kullanıcı adı'),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: passCtrl,
                      obscureText: true,
                      decoration: SalonCrmTheme.field('Şifre'),
                    ),
                    const SizedBox(height: 14),
                    const Text(
                      'Ödeme tipi',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                        color: SalonCrmTheme.inkSoft,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        ChoiceChip(
                          label: const Text('Yüzdelik'),
                          selected: payType == 'percent',
                          onSelected: (_) =>
                              setModal(() => payType = 'percent'),
                        ),
                        ChoiceChip(
                          label: const Text('Net tutar'),
                          selected: payType == 'net',
                          onSelected: (_) => setModal(() => payType = 'net'),
                        ),
                        ChoiceChip(
                          label: const Text('Günlük'),
                          selected: payPeriod == 'daily',
                          onSelected: (_) =>
                              setModal(() => payPeriod = 'daily'),
                        ),
                        ChoiceChip(
                          label: const Text('Aylık'),
                          selected: payPeriod == 'monthly',
                          onSelected: (_) =>
                              setModal(() => payPeriod = 'monthly'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    if (payType == 'percent')
                      TextField(
                        controller: commissionCtrl,
                        keyboardType: TextInputType.number,
                        decoration: SalonCrmTheme.field(
                          'Komisyon % (personelin payı)',
                          hint: 'Örn. 40',
                        ),
                      )
                    else
                      TextField(
                        controller: salaryCtrl,
                        keyboardType: TextInputType.number,
                        decoration: SalonCrmTheme.field(
                          payPeriod == 'daily'
                              ? 'Günlük net maaş (₺)'
                              : 'Aylık net maaş (₺)',
                        ),
                      ),
                    const SizedBox(height: 18),
                    CrmPrimaryButton(
                      label: 'Personel ekle',
                      icon: Icons.check_rounded,
                      onPressed: () async {
                        final name = nameCtrl.text.trim();
                        final username = userCtrl.text.trim();
                        final password = passCtrl.text;
                        if (name.isEmpty ||
                            username.isEmpty ||
                            password.isEmpty) {
                          Utils.errorSnackBar(ctx, 'Tüm alanları doldurun');
                          return;
                        }
                        Utils.loadingDialog(ctx);
                        try {
                          final token = (await SalonCrmSession.token()) ?? '';
                          await _service.createStaff(
                            token: token,
                            name: name,
                            username: username,
                            password: password,
                            payType: payType,
                            payPeriod: payPeriod,
                            commissionPercent: payType == 'percent'
                                ? double.tryParse(commissionCtrl.text
                                        .replaceAll(',', '.')) ??
                                    0
                                : 0,
                            salaryAmount: payType == 'net'
                                ? double.tryParse(
                                        salaryCtrl.text.replaceAll(',', '.')) ??
                                    0
                                : 0,
                          );
                          if (!ctx.mounted) return;
                          Utils.closeDialog(ctx);
                          Navigator.pop(ctx, true);
                        } catch (e) {
                          if (!ctx.mounted) return;
                          Utils.closeDialog(ctx);
                          salonCrmShowError(ctx, e);
                        }
                      },
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );

    nameCtrl.dispose();
    userCtrl.dispose();
    passCtrl.dispose();
    commissionCtrl.dispose();
    salaryCtrl.dispose();

    if (saved == true && mounted) {
      await _load();
      if (mounted) Utils.showSnackBar(context, 'Personel eklendi');
    }
  }

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: 'Personel',
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
                'CRM kilitli — personel eklenemez. Geçmiş listelenir.',
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
        color: const Color(0xFFECFDF5),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: const Color(0xFFD1FAE5),
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(
                Icons.groups_rounded,
                color: Color(0xFF059669),
                size: 22,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Aktif personel',
                    style: SalonCrmTheme.caption.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  Text(
                    '$_activeCount',
                    style: const TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.6,
                      color: Color(0xFF059669),
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: SalonCrmTheme.surface.withValues(alpha: 0.85),
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                'Toplam ${_staff.length}',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: SalonCrmTheme.inkSoft,
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
        color: const Color(0xFFD1FAE5),
        borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
        child: InkWell(
          onTap: _openAddSheet,
          borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 14),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
              border: Border.all(
                color: const Color(0xFF059669).withValues(alpha: 0.25),
              ),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.person_add_rounded,
                    size: 18, color: Color(0xFF047857)),
                SizedBox(width: 8),
                Text(
                  'Personel ekle',
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 14.5,
                    color: Color(0xFF047857),
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
              TextButton(onPressed: _load, child: const Text('Tekrar dene')),
            ],
          ),
        ),
      );
    }
    if (_staff.isEmpty) {
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
                  color: const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Icon(Icons.badge_outlined,
                    size: 32, color: Color(0xFF059669)),
              ),
              const SizedBox(height: 14),
              Text(
                'Henüz personel yok',
                style: SalonCrmTheme.titleMd.copyWith(fontSize: 17),
              ),
              const SizedBox(height: 6),
              Text(
                _canWrite
                    ? 'Personel ekleyerek randevu ve maaş takibini başlatın.'
                    : 'Kayıtlı personel bulunmuyor.',
                textAlign: TextAlign.center,
                style: SalonCrmTheme.caption,
              ),
              if (_canWrite) ...[
                const SizedBox(height: 18),
                SizedBox(
                  width: 200,
                  child: CrmPrimaryButton(
                    label: 'Personel ekle',
                    icon: Icons.person_add_rounded,
                    onPressed: _openAddSheet,
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
      itemCount: _staff.length + 1,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, i) {
        if (i == 0) {
          return const Padding(
            padding: EdgeInsets.only(bottom: 4, top: 2),
            child: CrmSectionLabel('Ekip'),
          );
        }
        return _StaffCard(
          staff: _staff[i - 1],
          onTap: () => Navigator.pushNamed(
            context,
            RouteNames.salonCrmStaffDetailScreen,
            arguments: {
              'staff_id': _staff[i - 1].id,
              'is_owner': true,
            },
          ).then((_) => _load()),
        );
      },
    );
  }
}

class _StaffCard extends StatelessWidget {
  const _StaffCard({required this.staff, required this.onTap});

  final SalonCrmStaffItem staff;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return CrmSoftCard(
      padding: const EdgeInsets.all(14),
      onTap: onTap,
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: const Color(0xFFD1FAE5),
              borderRadius: BorderRadius.circular(15),
              image: staff.photo != null && staff.photo!.isNotEmpty
                  ? DecorationImage(
                      image: NetworkImage(RemoteUrls.imageUrl(staff.photo!)),
                      fit: BoxFit.cover,
                    )
                  : null,
            ),
            child: staff.photo == null || staff.photo!.isEmpty
                ? Center(
                    child: Text(
                      staff.name.isNotEmpty
                          ? staff.name[0].toUpperCase()
                          : 'P',
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 18,
                        color: Color(0xFF047857),
                      ),
                    ),
                  )
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
                    fontSize: 15,
                    color: SalonCrmTheme.ink,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '@${staff.username}',
                  style: SalonCrmTheme.caption,
                ),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    _StaffTag(
                      label: staff.paySummary,
                      color: const Color(0xFF047857),
                      bg: const Color(0xFFD1FAE5),
                    ),
                    if (staff.services.isNotEmpty)
                      _StaffTag(
                        label: '${staff.services.length} hizmet',
                        color: SalonCrmTheme.inkSoft,
                        bg: SalonCrmTheme.bgDeep,
                      ),
                    _StaffTag(
                      label: staff.isActive ? 'Aktif' : 'Pasif',
                      color: staff.isActive
                          ? SalonCrmTheme.success
                          : SalonCrmTheme.muted,
                      bg: staff.isActive
                          ? SalonCrmTheme.successSoft
                          : SalonCrmTheme.bgDeep,
                    ),
                  ],
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right_rounded, color: SalonCrmTheme.muted),
        ],
      ),
    );
  }
}

class _StaffTag extends StatelessWidget {
  const _StaffTag({
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
