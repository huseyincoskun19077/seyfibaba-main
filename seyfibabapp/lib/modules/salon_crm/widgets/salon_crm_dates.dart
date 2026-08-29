class SalonCrmDates {
  SalonCrmDates._();

  /// Türkiye sabit UTC+3 (DST yok). Randevu / “bugün” duvar saati.
  static const turkeyOffset = Duration(hours: 3);

  static const months = [
    'Ocak',
    'Şubat',
    'Mart',
    'Nisan',
    'Mayıs',
    'Haziran',
    'Temmuz',
    'Ağustos',
    'Eylül',
    'Ekim',
    'Kasım',
    'Aralık',
  ];

  static const weekdaysShort = [
    'Pzt',
    'Sal',
    'Çar',
    'Per',
    'Cum',
    'Cmt',
    'Paz',
  ];

  /// Cihaz diliminden bağımsız Türkiye “şimdi”.
  static DateTime now() {
    final turkey = DateTime.now().toUtc().add(turkeyOffset);
    return DateTime(
      turkey.year,
      turkey.month,
      turkey.day,
      turkey.hour,
      turkey.minute,
      turkey.second,
      turkey.millisecond,
    );
  }

  /// Türkiye’de bugünün günü (saat 00:00).
  static DateTime today() {
    final n = now();
    return DateTime(n.year, n.month, n.day);
  }

  static String dateKey([DateTime? d]) {
    final x = d ?? today();
    String two(int n) => n.toString().padLeft(2, '0');
    return '${x.year}-${two(x.month)}-${two(x.day)}';
  }

  static String monthYear(DateTime d) => '${months[d.month - 1]} ${d.year}';

  static String weekdayShort(DateTime d) => weekdaysShort[d.weekday - 1];

  static String dayLabel(DateTime d, {DateTime? now}) {
    final todayRef = now ?? today();
    final a = DateTime(d.year, d.month, d.day);
    final b = DateTime(todayRef.year, todayRef.month, todayRef.day);
    final diff = a.difference(b).inDays;
    if (diff == 0) return 'Bugün';
    if (diff == 1) return 'Yarın';
    if (diff == -1) return 'Dün';
    return weekdayShort(d);
  }

  static String dayMonth(DateTime d) =>
      '${d.day.toString().padLeft(2, '0')} ${months[d.month - 1]}';

  static String full(DateTime d) =>
      '${weekdayShort(d)} ${d.day} ${months[d.month - 1]} ${d.year}';

  static bool sameDay(DateTime a, DateTime b) =>
      a.year == b.year && a.month == b.month && a.day == b.day;

  /// API'ye gönder: seçilen saat Türkiye duvar saati (timezone yok → kayma olmaz).
  static String toApiDateTime(DateTime value) {
    final local = value.isUtc ? value.toLocal() : value;
    String two(int n) => n.toString().padLeft(2, '0');
    return '${local.year}-${two(local.month)}-${two(local.day)} '
        '${two(local.hour)}:${two(local.minute)}:${two(local.second)}';
  }

  /// API'den al: gelen değeri duvar saati olarak göster (UTC'ye çevirme).
  static DateTime? fromApiDateTime(String? raw) {
    if (raw == null || raw.trim().isEmpty) return null;
    var s = raw.trim().replaceFirst(' ', 'T');
    final hasTz = s.endsWith('Z') ||
        s.endsWith('z') ||
        RegExp(r'[+-]\d{2}:?\d{2}$').hasMatch(s);
    if (hasTz) {
      final parsed = DateTime.tryParse(s);
      if (parsed == null) return null;
      final turkey = parsed.toUtc().add(turkeyOffset);
      return DateTime(
        turkey.year,
        turkey.month,
        turkey.day,
        turkey.hour,
        turkey.minute,
        turkey.second,
      );
    }
    s = s.split('.').first;
    final parsed = DateTime.tryParse(s);
    if (parsed == null) return null;
    return DateTime(
      parsed.year,
      parsed.month,
      parsed.day,
      parsed.hour,
      parsed.minute,
      parsed.second,
    );
  }
}
