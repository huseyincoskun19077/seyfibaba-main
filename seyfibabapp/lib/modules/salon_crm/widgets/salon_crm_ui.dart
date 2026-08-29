import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/error/exception.dart';
import '../../../utils/utils.dart';
import '../../../widgets/seyfibaba_loading.dart';
import 'salon_crm_dates.dart';
import 'salon_crm_theme.dart';

class CrmScaffold extends StatelessWidget {
  const CrmScaffold({
    super.key,
    required this.body,
    this.title,
    this.actions,
    this.floatingActionButton,
    this.bottomNavigationBar,
    this.showBack = true,
    this.onBack,
  });

  final Widget body;
  final String? title;
  final List<Widget>? actions;
  final Widget? floatingActionButton;
  final Widget? bottomNavigationBar;
  final bool showBack;
  final VoidCallback? onBack;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: SalonCrmTheme.bg,
      floatingActionButton: floatingActionButton,
      bottomNavigationBar: bottomNavigationBar,
      body: Container(
        decoration: SalonCrmTheme.pageGlow,
        child: SafeArea(
          child: Column(
            children: [
              if (title != null || showBack || (actions?.isNotEmpty ?? false))
                Padding(
                  padding: const EdgeInsets.fromLTRB(8, 4, 8, 0),
                  child: Row(
                    children: [
                      if (showBack)
                        IconButton(
                          onPressed: onBack ?? () => Navigator.maybePop(context),
                          icon: const Icon(Icons.arrow_back_rounded),
                          color: SalonCrmTheme.ink,
                        )
                      else
                        const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          title ?? '',
                          style: const TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.w700,
                            color: SalonCrmTheme.ink,
                          ),
                        ),
                      ),
                      ...?actions,
                    ],
                  ),
                ),
              Expanded(child: body),
            ],
          ),
        ),
      ),
    );
  }
}

class CrmPrimaryButton extends StatelessWidget {
  const CrmPrimaryButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.icon,
  });

  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 52,
      child: ElevatedButton(
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          elevation: 0,
          backgroundColor: SalonCrmTheme.accent,
          foregroundColor: SalonCrmTheme.ink,
          disabledBackgroundColor: SalonCrmTheme.line,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 20),
              const SizedBox(width: 8),
            ],
            Text(
              label,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 15.5,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class CrmSoftCard extends StatelessWidget {
  const CrmSoftCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.onTap,
    this.color,
    this.margin,
  });

  final Widget child;
  final EdgeInsets padding;
  final VoidCallback? onTap;
  final Color? color;
  final EdgeInsetsGeometry? margin;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      decoration: SalonCrmTheme.surfaceCard(color: color),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(SalonCrmTheme.radius),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(SalonCrmTheme.radius),
          child: Padding(padding: padding, child: child),
        ),
      ),
    );
  }
}

class CrmRoleTile extends StatelessWidget {
  const CrmRoleTile({
    super.key,
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return CrmSoftCard(
      onTap: onTap,
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: SalonCrmTheme.accentSoft,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: SalonCrmTheme.ink, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: SalonCrmTheme.ink,
                  ),
                ),
                const SizedBox(height: 4),
                Text(subtitle, style: SalonCrmTheme.caption),
              ],
            ),
          ),
          const Icon(
            Icons.arrow_forward_ios_rounded,
            size: 16,
            color: SalonCrmTheme.muted,
          ),
        ],
      ),
    );
  }
}

class CrmMenuTile extends StatelessWidget {
  const CrmMenuTile({
    super.key,
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: CrmSoftCard(
        onTap: onTap,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: SalonCrmTheme.bgDeep,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: SalonCrmTheme.ink, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      color: SalonCrmTheme.ink,
                      fontSize: 15,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(subtitle, style: SalonCrmTheme.caption),
                ],
              ),
            ),
            const Icon(
              Icons.chevron_right_rounded,
              color: SalonCrmTheme.muted,
            ),
          ],
        ),
      ),
    );
  }
}

class CrmStatusChip extends StatelessWidget {
  const CrmStatusChip({
    super.key,
    required this.label,
    this.positive = true,
  });

  final String label;
  final bool positive;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: positive ? SalonCrmTheme.successSoft : SalonCrmTheme.dangerSoft,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: positive ? SalonCrmTheme.success : SalonCrmTheme.danger,
        ),
      ),
    );
  }
}

class CrmSectionLabel extends StatelessWidget {
  const CrmSectionLabel(this.text, {super.key});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, top: 6),
      child: Text(
        text,
        style: const TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w700,
          color: SalonCrmTheme.inkSoft,
          letterSpacing: 0.1,
        ),
      ),
    );
  }
}

String? salonCrmWhatsAppDigits(String? phone) {
  if (phone == null) return null;
  var digits = phone.replaceAll(RegExp(r'[^0-9]'), '');
  if (digits.isEmpty) return null;
  if (digits.startsWith('00')) digits = digits.substring(2);
  if (digits.startsWith('0')) digits = '90${digits.substring(1)}';
  if (digits.length == 10) digits = '90$digits';
  if (digits.length < 11) return null;
  return digits;
}

bool salonCrmHasWhatsApp(String? phone) =>
    salonCrmWhatsAppDigits(phone) != null;

String salonCrmReminderMessage({
  required String customerName,
  required String salonName,
  DateTime? startsAt,
}) {
  final who = customerName.trim();
  final salon = salonName.trim().isEmpty ? 'salon' : salonName.trim();
  final greeting = who.isEmpty ? 'Merhaba' : 'Merhaba $who';
  if (startsAt == null) {
    return '$greeting, $salon randevunuzu hatırlatmak isteriz.';
  }
  final local = startsAt.isUtc
      ? startsAt.toUtc().add(SalonCrmDates.turkeyOffset)
      : startsAt;
  final wall = DateTime(
    local.year,
    local.month,
    local.day,
    local.hour,
    local.minute,
  );
  return '$greeting, $salon randevunuz ${SalonCrmDates.full(wall)} saat ${DateFormat('HH:mm').format(wall)}.';
}

Future<void> salonCrmOpenWhatsApp(
  BuildContext context, {
  required String phone,
  required String text,
}) async {
  final digits = salonCrmWhatsAppDigits(phone);
  if (digits == null) {
    Utils.errorSnackBar(context, 'Telefon numarası yok');
    return;
  }
  final uri = Uri.parse(
    'https://wa.me/$digits?text=${Uri.encodeComponent(text)}',
  );
  final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
  if (!ok && context.mounted) {
    Utils.errorSnackBar(context, 'WhatsApp açılamadı');
  }
}

String salonCrmHourLabel(int hour) =>
    '${hour.toString().padLeft(2, '0')}:00';

Future<DateTime?> salonCrmPickDate(
  BuildContext context, {
  required DateTime initial,
  DateTime? firstDate,
  DateTime? lastDate,
}) {
  final first = firstDate ?? SalonCrmDates.today().subtract(const Duration(days: 14));
  final last = lastDate ?? SalonCrmDates.today().add(const Duration(days: 60));
  final days = <DateTime>[];
  var cursor = DateTime(first.year, first.month, first.day);
  final end = DateTime(last.year, last.month, last.day);
  while (!cursor.isAfter(end)) {
    days.add(cursor);
    cursor = cursor.add(const Duration(days: 1));
  }
  var selectedIndex = days.indexWhere((d) => SalonCrmDates.sameDay(d, initial));
  if (selectedIndex < 0) selectedIndex = 0;
  final wheel = FixedExtentScrollController(initialItem: selectedIndex);

  return showModalBottomSheet<DateTime>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) {
      return StatefulBuilder(
        builder: (ctx, setLocal) {
          final selected = days[selectedIndex.clamp(0, days.length - 1)];
          return Container(
            decoration: const BoxDecoration(
              color: SalonCrmTheme.surface,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: SalonCrmTheme.line,
                        borderRadius: BorderRadius.circular(99),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'Gün seç',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 18,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        SalonCrmDates.full(selected),
                        style: const TextStyle(
                          color: SalonCrmTheme.inkSoft,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      height: 220,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          Container(
                            height: 48,
                            margin: const EdgeInsets.symmetric(horizontal: 8),
                            decoration: BoxDecoration(
                              color: SalonCrmTheme.accentSoft,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color:
                                    SalonCrmTheme.accent.withValues(alpha: 0.45),
                              ),
                            ),
                          ),
                          ListWheelScrollView.useDelegate(
                            itemExtent: 48,
                            diameterRatio: 1.35,
                            perspective: 0.002,
                            physics: const FixedExtentScrollPhysics(),
                            controller: wheel,
                            onSelectedItemChanged: (i) {
                              setLocal(() => selectedIndex = i);
                            },
                            childDelegate: ListWheelChildBuilderDelegate(
                              childCount: days.length,
                              builder: (context, i) {
                                final day = days[i];
                                final active = i == selectedIndex;
                                return Center(
                                  child: Text(
                                    SalonCrmDates.full(day),
                                    style: TextStyle(
                                      fontSize: active ? 17 : 15,
                                      fontWeight: active
                                          ? FontWeight.w800
                                          : FontWeight.w500,
                                      color: active
                                          ? SalonCrmTheme.ink
                                          : SalonCrmTheme.muted,
                                    ),
                                  ),
                                );
                              },
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: TextButton(
                            onPressed: () => Navigator.pop(ctx),
                            child: const Text(
                              'Vazgeç',
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                color: SalonCrmTheme.inkSoft,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          flex: 2,
                          child: ElevatedButton(
                            onPressed: () => Navigator.pop(ctx, selected),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: SalonCrmTheme.accent,
                              foregroundColor: SalonCrmTheme.ink,
                              minimumSize: const Size.fromHeight(48),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                              elevation: 0,
                            ),
                            child: const Text(
                              'Günü seç',
                              style: TextStyle(fontWeight: FontWeight.w800),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      );
    },
  ).whenComplete(wheel.dispose);
}

Future<TimeOfDay?> salonCrmPickTime(
  BuildContext context, {
  required TimeOfDay initial,
}) {
  var hour = initial.hour;
  var minute = initial.minute.clamp(0, 59);
  final hourWheel = FixedExtentScrollController(initialItem: hour);
  final minuteWheel = FixedExtentScrollController(initialItem: minute);

  return showModalBottomSheet<TimeOfDay>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) {
      return StatefulBuilder(
        builder: (ctx, setLocal) {
          String two(int n) => n.toString().padLeft(2, '0');
          return Container(
            decoration: const BoxDecoration(
              color: SalonCrmTheme.surface,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: SalonCrmTheme.line,
                        borderRadius: BorderRadius.circular(99),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'Saati kaydır',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 18,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        '${two(hour)}:${two(minute)}',
                        style: const TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.w800,
                          color: SalonCrmTheme.ink,
                          letterSpacing: 1,
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'Dakika dakika seçebilirsiniz (örn. 12:10, 12:20)',
                        style: TextStyle(
                          fontSize: 12,
                          color: SalonCrmTheme.muted,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    SizedBox(
                      height: 180,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          Container(
                            height: 48,
                            margin: const EdgeInsets.symmetric(horizontal: 24),
                            decoration: BoxDecoration(
                              color: SalonCrmTheme.accentSoft,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color:
                                    SalonCrmTheme.accent.withValues(alpha: 0.45),
                              ),
                            ),
                          ),
                          Row(
                            children: [
                              Expanded(
                                child: ListWheelScrollView.useDelegate(
                                  itemExtent: 44,
                                  diameterRatio: 1.2,
                                  perspective: 0.002,
                                  physics: const FixedExtentScrollPhysics(),
                                  controller: hourWheel,
                                  onSelectedItemChanged: (i) {
                                    setLocal(() => hour = i);
                                  },
                                  childDelegate: ListWheelChildBuilderDelegate(
                                    childCount: 24,
                                    builder: (context, i) {
                                      final active = i == hour;
                                      return Center(
                                        child: Text(
                                          two(i),
                                          style: TextStyle(
                                            fontSize: active ? 26 : 18,
                                            fontWeight: active
                                                ? FontWeight.w800
                                                : FontWeight.w500,
                                            color: active
                                                ? SalonCrmTheme.ink
                                                : SalonCrmTheme.muted,
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                                ),
                              ),
                              const Text(
                                ':',
                                style: TextStyle(
                                  fontSize: 28,
                                  fontWeight: FontWeight.w900,
                                  color: SalonCrmTheme.ink,
                                ),
                              ),
                              Expanded(
                                child: ListWheelScrollView.useDelegate(
                                  itemExtent: 44,
                                  diameterRatio: 1.2,
                                  perspective: 0.002,
                                  physics: const FixedExtentScrollPhysics(),
                                  controller: minuteWheel,
                                  onSelectedItemChanged: (i) {
                                    setLocal(() => minute = i);
                                  },
                                  childDelegate: ListWheelChildBuilderDelegate(
                                    childCount: 60,
                                    builder: (context, i) {
                                      final active = i == minute;
                                      return Center(
                                        child: Text(
                                          two(i),
                                          style: TextStyle(
                                            fontSize: active ? 26 : 18,
                                            fontWeight: active
                                                ? FontWeight.w800
                                                : FontWeight.w500,
                                            color: active
                                                ? SalonCrmTheme.ink
                                                : SalonCrmTheme.muted,
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: TextButton(
                            onPressed: () => Navigator.pop(ctx),
                            child: const Text(
                              'Vazgeç',
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                color: SalonCrmTheme.inkSoft,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          flex: 2,
                          child: ElevatedButton(
                            onPressed: () => Navigator.pop(
                              ctx,
                              TimeOfDay(hour: hour, minute: minute),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: SalonCrmTheme.accent,
                              foregroundColor: SalonCrmTheme.ink,
                              minimumSize: const Size.fromHeight(48),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                              elevation: 0,
                            ),
                            child: const Text(
                              'Saati seç',
                              style: TextStyle(fontWeight: FontWeight.w800),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      );
    },
  ).whenComplete(() {
    hourWheel.dispose();
    minuteWheel.dispose();
  });
}

void salonCrmShowLoading(BuildContext context) {
  showDialog(
    context: context,
    useRootNavigator: true,
    barrierDismissible: false,
    barrierColor: Colors.black.withValues(alpha: 0.35),
    builder: (_) => const Center(
      child: Material(
        type: MaterialType.transparency,
        child: SeyfibabaLoadingCard(),
      ),
    ),
  );
}

void salonCrmCloseLoading(BuildContext context) {
  final nav = Navigator.of(context, rootNavigator: true);
  if (nav.canPop()) {
    nav.pop();
  }
}

String salonCrmErrorMessage(Object e) {
  if (e is InvalidInputException) {
    final msgs = e.errors.message;
    if (msgs.isNotEmpty) return msgs.first;
    // validation field errors
    final map = e.errors.toMap();
    for (final entry in map.entries) {
      final v = entry.value;
      if (v is List && v.isNotEmpty) {
        return v.first.toString();
      }
      if (v is String && v.isNotEmpty) return v;
    }
    return 'İşlem yapılamadı';
  }
  if (e is ServerException) {
    return e.message.isNotEmpty ? e.message : 'Sunucu hatası';
  }
  final raw = e.toString();
  if (raw.startsWith('Exception: ')) {
    return raw.substring('Exception: '.length);
  }
  if (raw.startsWith('Instance of ')) {
    return 'İşlem yapılamadı';
  }
  return raw.isNotEmpty ? raw : 'İşlem yapılamadı';
}

void salonCrmShowError(BuildContext context, Object error) {
  Utils.errorSnackBar(context, salonCrmErrorMessage(error));
}

class CrmBottomNavItem {
  const CrmBottomNavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
  });

  final IconData icon;
  final IconData activeIcon;
  final String label;
}

/// Alışveriş bottom nav ile aynı dil: yüzen, yuvarlak menü çubuğu.
class CrmBottomNav extends StatelessWidget {
  const CrmBottomNav({
    super.key,
    required this.items,
    required this.currentIndex,
    required this.onTap,
  });

  final List<CrmBottomNavItem> items;
  final int currentIndex;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      minimum: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 0, 14, 6),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: SalonCrmTheme.surface,
            borderRadius: BorderRadius.circular(28),
            border: Border.all(
              color: SalonCrmTheme.line.withValues(alpha: 0.85),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.08),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.03),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(28),
            child: SizedBox(
              height: 64,
              child: Row(
                children: List.generate(items.length, (i) {
                  final item = items[i];
                  final selected = currentIndex == i;
                  return Expanded(
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () => onTap(i),
                        borderRadius: BorderRadius.circular(22),
                        splashColor:
                            SalonCrmTheme.accent.withValues(alpha: 0.18),
                        highlightColor:
                            SalonCrmTheme.accent.withValues(alpha: 0.08),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 2,
                            vertical: 6,
                          ),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              AnimatedContainer(
                                duration: const Duration(milliseconds: 220),
                                curve: Curves.easeOutCubic,
                                width: selected ? 52 : 40,
                                height: 34,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  color: selected
                                      ? SalonCrmTheme.accent
                                          .withValues(alpha: 0.28)
                                      : Colors.transparent,
                                  borderRadius: BorderRadius.circular(18),
                                ),
                                child: Icon(
                                  selected ? item.activeIcon : item.icon,
                                  size: 22,
                                  color: selected
                                      ? SalonCrmTheme.ink
                                      : SalonCrmTheme.muted,
                                ),
                              ),
                              const SizedBox(height: 3),
                              AnimatedDefaultTextStyle(
                                duration: const Duration(milliseconds: 200),
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: selected
                                      ? FontWeight.w700
                                      : FontWeight.w500,
                                  color: selected
                                      ? SalonCrmTheme.ink
                                      : SalonCrmTheme.muted,
                                  height: 1.1,
                                ),
                                child: Text(
                                  item.label,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                }),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
