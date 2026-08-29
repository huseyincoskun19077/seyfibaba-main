import 'dart:convert';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import '../../../core/data/datasources/network_parser.dart';
import '../../../core/remote_urls.dart';
import '../widgets/salon_crm_dates.dart';

class SalonCrmAccess {
  SalonCrmAccess({
    required this.isUnlocked,
    required this.reason,
    required this.canWrite,
    required this.canReadHistory,
    required this.inTrial,
    required this.period,
    required this.monthSpend,
    required this.threshold,
    required this.remainingToUnlock,
    required this.message,
    this.trialEndsAt,
  });

  final bool isUnlocked;
  final String reason;
  final bool canWrite;
  final bool canReadHistory;
  final bool inTrial;
  final String period;
  final double monthSpend;
  final int threshold;
  final double remainingToUnlock;
  final String message;
  final String? trialEndsAt;

  factory SalonCrmAccess.fromMap(Map<String, dynamic> map) {
    return SalonCrmAccess(
      isUnlocked: map['is_unlocked'] == true,
      reason: '${map['reason'] ?? ''}',
      canWrite: map['can_write'] == true,
      canReadHistory: map['can_read_history'] != false,
      inTrial: map['in_trial'] == true,
      period: '${map['period'] ?? ''}',
      monthSpend: double.tryParse('${map['month_spend'] ?? 0}') ?? 0,
      threshold: int.tryParse('${map['threshold'] ?? 10000}') ?? 10000,
      remainingToUnlock:
          double.tryParse('${map['remaining_to_unlock'] ?? 0}') ?? 0,
      message: '${map['message'] ?? ''}',
      trialEndsAt: map['trial_ends_at']?.toString(),
    );
  }
}

class SalonCrmSalonInfo {
  SalonCrmSalonInfo({
    required this.id,
    required this.name,
    required this.type,
    this.phone,
    this.trialEndsAt,
    this.openHour = 9,
    this.closeHour = 21,
  });

  final int id;
  final String name;
  final String type;
  final String? phone;
  final String? trialEndsAt;
  final int openHour;
  final int closeHour;

  factory SalonCrmSalonInfo.fromMap(Map<String, dynamic> map) {
    return SalonCrmSalonInfo(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      name: '${map['name'] ?? ''}',
      type: '${map['type'] ?? 'kuafor'}',
      phone: map['phone']?.toString(),
      trialEndsAt: map['trial_ends_at']?.toString(),
      openHour: int.tryParse('${map['open_hour'] ?? 9}') ?? 9,
      closeHour: int.tryParse('${map['close_hour'] ?? 21}') ?? 21,
    );
  }
}

class SalonCrmStatus {
  SalonCrmStatus({
    required this.hasSalon,
    required this.access,
    this.salon,
    this.staff,
  });

  final bool hasSalon;
  final SalonCrmAccess access;
  final SalonCrmSalonInfo? salon;
  final SalonCrmStaffItem? staff;

  factory SalonCrmStatus.fromMap(Map<String, dynamic> map) {
    final salonMap = map['salon'];
    final accessMap = map['access'];
    final staffMap = map['staff'];
    return SalonCrmStatus(
      hasSalon: map['has_salon'] == true,
      salon: salonMap is Map
          ? SalonCrmSalonInfo.fromMap(Map<String, dynamic>.from(salonMap))
          : null,
      access: accessMap is Map
          ? SalonCrmAccess.fromMap(Map<String, dynamic>.from(accessMap))
          : SalonCrmAccess.fromMap(const {}),
      staff: staffMap is Map
          ? SalonCrmStaffItem.fromMap(Map<String, dynamic>.from(staffMap))
          : null,
    );
  }
}

class SalonCrmStaffItem {
  SalonCrmStaffItem({
    required this.id,
    required this.name,
    required this.username,
    required this.isActive,
    this.photo,
    this.showPhotoToCustomers = true,
    this.commissionPercent = 0,
    this.payType = 'percent',
    this.payPeriod = 'monthly',
    this.salaryAmount = 0,
    this.services = const [],
  });

  final int id;
  final String name;
  final String username;
  final bool isActive;
  final String? photo;
  final bool showPhotoToCustomers;
  final double commissionPercent;
  final String payType;
  final String payPeriod;
  final double salaryAmount;
  final List<SalonCrmServiceItem> services;

  String get paySummary {
    final period = payPeriod == 'daily' ? 'günlük' : 'aylık';
    if (payType == 'net') {
      return 'Net ${salaryAmount.toStringAsFixed(0)} ₺ · $period';
    }
    return 'Yüzde %${commissionPercent.toStringAsFixed(0)} · $period';
  }

  factory SalonCrmStaffItem.fromMap(Map<String, dynamic> map) {
    final services = map['services'];
    return SalonCrmStaffItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      name: '${map['name'] ?? ''}',
      username: '${map['username'] ?? ''}',
      isActive: map['is_active'] != false,
      photo: map['photo']?.toString(),
      showPhotoToCustomers: map['show_photo_to_customers'] != false,
      commissionPercent:
          double.tryParse('${map['commission_percent'] ?? 0}') ?? 0,
      payType: '${map['pay_type'] ?? 'percent'}',
      payPeriod: '${map['pay_period'] ?? 'monthly'}',
      salaryAmount: double.tryParse('${map['salary_amount'] ?? 0}') ?? 0,
      services: services is List
          ? services
              .whereType<Map>()
              .map((e) =>
                  SalonCrmServiceItem.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : const [],
    );
  }
}

class SalonCrmSalaryPaymentItem {
  SalonCrmSalaryPaymentItem({
    required this.id,
    required this.staffId,
    required this.amount,
    required this.status,
    required this.statusLabel,
    required this.ownerConfirmed,
    required this.staffConfirmed,
    this.staffName,
    this.payType,
    this.payPeriod,
    this.periodKey,
    this.suggestedAmount = 0,
    this.paidAt,
    this.notes,
  });

  final int id;
  final int staffId;
  final String? staffName;
  final String? payType;
  final String? payPeriod;
  final String? periodKey;
  final double suggestedAmount;
  final double amount;
  final String status;
  final String statusLabel;
  final bool ownerConfirmed;
  final bool staffConfirmed;
  final String? paidAt;
  final String? notes;

  bool get isPaid => status == 'paid';

  factory SalonCrmSalaryPaymentItem.fromMap(Map<String, dynamic> map) {
    return SalonCrmSalaryPaymentItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      staffId: int.tryParse('${map['staff_id'] ?? 0}') ?? 0,
      staffName: map['staff_name']?.toString(),
      payType: map['pay_type']?.toString(),
      payPeriod: map['pay_period']?.toString(),
      periodKey: map['period_key']?.toString(),
      suggestedAmount:
          double.tryParse('${map['suggested_amount'] ?? 0}') ?? 0,
      amount: double.tryParse('${map['amount'] ?? 0}') ?? 0,
      status: '${map['status'] ?? 'pending'}',
      statusLabel: '${map['status_label'] ?? ''}',
      ownerConfirmed: map['owner_confirmed'] == true,
      staffConfirmed: map['staff_confirmed'] == true,
      paidAt: map['paid_at']?.toString(),
      notes: map['notes']?.toString(),
    );
  }
}

class SalonCrmStaffHourItem {
  SalonCrmStaffHourItem({
    required this.weekday,
    required this.startTime,
    required this.endTime,
    this.isOff = false,
  });

  final int weekday;
  final String startTime;
  final String endTime;
  final bool isOff;

  factory SalonCrmStaffHourItem.fromMap(Map<String, dynamic> map) {
    String hm(dynamic v, String fallback) {
      final s = '${v ?? fallback}';
      return s.length >= 5 ? s.substring(0, 5) : fallback;
    }

    return SalonCrmStaffHourItem(
      weekday: int.tryParse('${map['weekday'] ?? 1}') ?? 1,
      startTime: hm(map['start_time'], '09:00'),
      endTime: hm(map['end_time'], '21:00'),
      isOff: map['is_off'] == true,
    );
  }

  static List<SalonCrmStaffHourItem> defaults() {
    return [
      for (var day = 1; day <= 7; day++)
        SalonCrmStaffHourItem(
          weekday: day,
          startTime: '09:00',
          endTime: '21:00',
        ),
    ];
  }
}

class SalonCrmStaffDetail {
  SalonCrmStaffDetail({
    required this.staff,
    required this.payments,
    this.hours = const [],
    this.periodKey,
    this.periodLabel,
    this.suggestedAmount = 0,
    this.existingPayment,
  });

  final SalonCrmStaffItem staff;
  final List<SalonCrmSalaryPaymentItem> payments;
  final List<SalonCrmStaffHourItem> hours;
  final String? periodKey;
  final String? periodLabel;
  final double suggestedAmount;
  final SalonCrmSalaryPaymentItem? existingPayment;

  factory SalonCrmStaffDetail.fromMap(Map<String, dynamic> map) {
    final staffMap = map['staff'];
    final period = map['current_period'];
    final periodMap =
        period is Map ? Map<String, dynamic>.from(period) : const {};
    final existing = periodMap['existing_payment'];
    final list = map['payments'];
    final hours = map['hours'];
    return SalonCrmStaffDetail(
      staff: SalonCrmStaffItem.fromMap(
        staffMap is Map ? Map<String, dynamic>.from(staffMap) : {},
      ),
      hours: hours is List
          ? hours
              .whereType<Map>()
              .map((e) => SalonCrmStaffHourItem.fromMap(
                    Map<String, dynamic>.from(e),
                  ))
              .toList()
          : SalonCrmStaffHourItem.defaults(),
      periodKey: periodMap['key']?.toString(),
      periodLabel: periodMap['label']?.toString(),
      suggestedAmount:
          double.tryParse('${periodMap['suggested_amount'] ?? 0}') ?? 0,
      existingPayment: existing is Map
          ? SalonCrmSalaryPaymentItem.fromMap(Map<String, dynamic>.from(existing))
          : null,
      payments: list is List
          ? list
              .whereType<Map>()
              .map((e) => SalonCrmSalaryPaymentItem.fromMap(
                    Map<String, dynamic>.from(e),
                  ))
              .toList()
          : const [],
    );
  }
}

class SalonCrmJoinPreview {
  SalonCrmJoinPreview({
    required this.salonId,
    required this.salonName,
    required this.joinCode,
    this.type,
    this.phone,
    this.logoImage,
    this.profileText,
  });

  final int salonId;
  final String salonName;
  final String joinCode;
  final String? type;
  final String? phone;
  final String? logoImage;
  final String? profileText;

  factory SalonCrmJoinPreview.fromMap(Map<String, dynamic> map) {
    final salon = map['salon'];
    final s = salon is Map ? Map<String, dynamic>.from(salon) : map;
    return SalonCrmJoinPreview(
      salonId: int.tryParse('${s['id'] ?? 0}') ?? 0,
      salonName: '${s['name'] ?? ''}',
      joinCode: '${s['join_code'] ?? ''}',
      type: s['type']?.toString(),
      phone: s['phone']?.toString(),
      logoImage: s['logo_image']?.toString(),
      profileText: s['profile_text']?.toString(),
    );
  }
}

class SalonCrmSalonProfile {
  SalonCrmSalonProfile({
    required this.name,
    required this.type,
    this.phone,
    this.logoImage,
    this.coverImage,
    this.profileText,
    this.showProfileToCustomers = false,
    this.joinCode,
    this.openHour = 9,
    this.closeHour = 21,
  });

  final String name;
  final String type;
  final String? phone;
  final String? logoImage;
  final String? coverImage;
  final String? profileText;
  final bool showProfileToCustomers;
  final String? joinCode;
  final int openHour;
  final int closeHour;

  factory SalonCrmSalonProfile.fromMap(Map<String, dynamic> map) {
    return SalonCrmSalonProfile(
      name: '${map['name'] ?? ''}',
      type: '${map['type'] ?? 'kuafor'}',
      phone: map['phone']?.toString(),
      logoImage: map['logo_image']?.toString(),
      coverImage: map['cover_image']?.toString(),
      profileText: map['profile_text']?.toString(),
      showProfileToCustomers: map['show_profile_to_customers'] == true,
      joinCode: map['join_code']?.toString(),
      openHour: int.tryParse('${map['open_hour'] ?? 9}') ?? 9,
      closeHour: int.tryParse('${map['close_hour'] ?? 21}') ?? 21,
    );
  }
}

class SalonCrmServiceItem {
  SalonCrmServiceItem({
    required this.id,
    required this.name,
    required this.durationMinutes,
    required this.price,
    required this.isActive,
  });

  final int id;
  final String name;
  final int durationMinutes;
  final double price;
  final bool isActive;

  factory SalonCrmServiceItem.fromMap(Map<String, dynamic> map) {
    return SalonCrmServiceItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      name: '${map['name'] ?? ''}',
      durationMinutes: int.tryParse('${map['duration_minutes'] ?? 30}') ?? 30,
      price: double.tryParse('${map['price'] ?? 0}') ?? 0,
      isActive: map['is_active'] != false,
    );
  }
}

class SalonCrmAppointmentItem {
  SalonCrmAppointmentItem({
    required this.id,
    required this.serviceName,
    required this.customerName,
    required this.customerPhone,
    required this.startsAt,
    required this.durationMinutes,
    required this.price,
    required this.status,
    this.staffId,
    this.staffName,
    this.staffCommissionPercent = 0,
    this.serviceId,
    this.customerId,
    this.customerNotes,
    this.notes,
    this.isBlock = false,
    this.blockType,
    this.paymentMethod,
    this.paymentStatus,
    this.commissionPercent,
    this.staffShare,
    this.ownerShare,
  });

  final int id;
  final int? customerId;
  final int? staffId;
  final String? staffName;
  final double staffCommissionPercent;
  final int? serviceId;
  final String serviceName;
  final String customerName;
  final String customerPhone;
  final String? customerNotes;
  final DateTime? startsAt;
  final int durationMinutes;
  final double price;
  final String status;
  final String? notes;
  final bool isBlock;
  final String? blockType;
  final String? paymentMethod;
  final String? paymentStatus;
  final double? commissionPercent;
  final double? staffShare;
  final double? ownerShare;

  bool get isUnpaid => status == 'completed' && paymentStatus == 'unpaid';

  factory SalonCrmAppointmentItem.fromMap(Map<String, dynamic> map) {
    return SalonCrmAppointmentItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      customerId: int.tryParse('${map['customer_id'] ?? ''}'),
      staffId: int.tryParse('${map['staff_id'] ?? ''}'),
      staffName: map['staff_name']?.toString(),
      staffCommissionPercent:
          double.tryParse('${map['staff_commission_percent'] ?? 0}') ?? 0,
      serviceId: int.tryParse('${map['service_id'] ?? ''}'),
      serviceName: '${map['service_name'] ?? ''}',
      customerName: '${map['customer_name'] ?? ''}',
      customerPhone: '${map['customer_phone'] ?? ''}',
      customerNotes: map['customer_notes']?.toString(),
      startsAt: SalonCrmDates.fromApiDateTime('${map['starts_at'] ?? ''}'),
      durationMinutes: int.tryParse('${map['duration_minutes'] ?? 30}') ?? 30,
      price: double.tryParse('${map['price'] ?? 0}') ?? 0,
      status: '${map['status'] ?? 'scheduled'}',
      notes: map['notes']?.toString(),
      isBlock: map['is_block'] == true,
      blockType: map['block_type']?.toString(),
      paymentMethod: map['payment_method']?.toString(),
      paymentStatus: map['payment_status']?.toString(),
      commissionPercent: double.tryParse('${map['commission_percent'] ?? ''}'),
      staffShare: double.tryParse('${map['staff_share'] ?? ''}'),
      ownerShare: double.tryParse('${map['owner_share'] ?? ''}'),
    );
  }
}

class SalonCrmCustomerItem {
  SalonCrmCustomerItem({
    required this.id,
    required this.name,
    required this.phone,
    this.notes,
    this.staffId = 0,
    this.missedLast = false,
    this.noShowCount = 0,
    this.lastStatus,
    this.lastServiceName,
    this.lastStartsAt,
  });

  final int id;
  final String name;
  final String phone;
  final String? notes;
  /// 0 = patron defteri, >0 = personel defteri
  final int staffId;
  final bool missedLast;
  final int noShowCount;
  final String? lastStatus;
  final String? lastServiceName;
  final DateTime? lastStartsAt;

  factory SalonCrmCustomerItem.fromMap(Map<String, dynamic> map) {
    return SalonCrmCustomerItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      name: '${map['name'] ?? ''}',
      phone: '${map['phone'] ?? ''}',
      notes: map['notes']?.toString(),
      staffId: int.tryParse('${map['staff_id'] ?? 0}') ?? 0,
      missedLast: map['missed_last'] == true,
      noShowCount: int.tryParse('${map['no_show_count'] ?? 0}') ?? 0,
      lastStatus: map['last_status']?.toString(),
      lastServiceName: map['last_service_name']?.toString(),
      lastStartsAt: SalonCrmDates.fromApiDateTime('${map['last_starts_at'] ?? ''}'),
    );
  }
}

class SalonCrmDayOccupancy {
  SalonCrmDayOccupancy({
    required this.date,
    required this.total,
    required this.blocked,
    required this.scheduled,
    required this.label,
  });

  final String date;
  final int total;
  final int blocked;
  final int scheduled;
  final String label;

  factory SalonCrmDayOccupancy.fromMap(Map<String, dynamic> map) {
    return SalonCrmDayOccupancy(
      date: '${map['date'] ?? ''}',
      total: int.tryParse('${map['total'] ?? 0}') ?? 0,
      blocked: int.tryParse('${map['blocked'] ?? 0}') ?? 0,
      scheduled: int.tryParse('${map['scheduled'] ?? 0}') ?? 0,
      label: '${map['label'] ?? ''}',
    );
  }
}

class SalonCrmDaySummary {
  SalonCrmDaySummary({
    required this.completed,
    required this.noShow,
    required this.cancelled,
    required this.scheduled,
    required this.needsOutcome,
    this.message,
  });

  final int completed;
  final int noShow;
  final int cancelled;
  final int scheduled;
  final int needsOutcome;
  final String? message;

  factory SalonCrmDaySummary.fromMap(Map<String, dynamic>? map) {
    if (map == null) {
      return SalonCrmDaySummary(
        completed: 0,
        noShow: 0,
        cancelled: 0,
        scheduled: 0,
        needsOutcome: 0,
      );
    }
    return SalonCrmDaySummary(
      completed: int.tryParse('${map['completed'] ?? 0}') ?? 0,
      noShow: int.tryParse('${map['no_show'] ?? 0}') ?? 0,
      cancelled: int.tryParse('${map['cancelled'] ?? 0}') ?? 0,
      scheduled: int.tryParse('${map['scheduled'] ?? 0}') ?? 0,
      needsOutcome: int.tryParse('${map['needs_outcome'] ?? 0}') ?? 0,
      message: map['message']?.toString(),
    );
  }
}

class SalonCrmAppointmentsResult {
  SalonCrmAppointmentsResult({
    required this.appointments,
    required this.occupancy,
    this.daySummary,
  });

  final List<SalonCrmAppointmentItem> appointments;
  final List<SalonCrmDayOccupancy> occupancy;
  final SalonCrmDaySummary? daySummary;
}

class SalonCrmCustomerCatalog {
  SalonCrmCustomerCatalog({
    required this.salonName,
    required this.services,
    required this.staff,
    this.salonProfile,
  });

  final String salonName;
  final List<SalonCrmServiceItem> services;
  final List<SalonCrmStaffItem> staff;
  final SalonCrmSalonProfile? salonProfile;

  factory SalonCrmCustomerCatalog.fromMap(Map<String, dynamic> map) {
    final salon = map['salon'];
    final services = map['services'];
    final staff = map['staff'];
    final salonMap = salon is Map ? Map<String, dynamic>.from(salon) : null;
    return SalonCrmCustomerCatalog(
      salonName: salonMap?['name']?.toString() ?? '',
      salonProfile: salonMap != null
          ? SalonCrmSalonProfile.fromMap(salonMap)
          : null,
      services: services is List
          ? services
              .whereType<Map>()
              .map((e) =>
                  SalonCrmServiceItem.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : [],
      staff: staff is List
          ? staff
              .whereType<Map>()
              .map((e) =>
                  SalonCrmStaffItem.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : [],
    );
  }
}

class SalonCrmStaffPerfItem {
  SalonCrmStaffPerfItem({
    required this.staffName,
    required this.completed,
    required this.scheduled,
    required this.cancelled,
    required this.noShow,
    required this.appointmentRevenue,
    required this.ledgerIncome,
    required this.totalRevenue,
    this.staffId,
    this.isActive = true,
    this.commissionPercent = 0,
    this.staffShare = 0,
    this.ownerShare = 0,
    this.credit = 0,
  });

  final int? staffId;
  final String staffName;
  final bool isActive;
  final int completed;
  final int scheduled;
  final int cancelled;
  final int noShow;
  final double appointmentRevenue;
  final double ledgerIncome;
  final double totalRevenue;
  final double commissionPercent;
  final double staffShare;
  final double ownerShare;
  final double credit;

  factory SalonCrmStaffPerfItem.fromMap(Map<String, dynamic> map) {
    final appt = map['appointments'];
    final led = map['ledger'];
    final apptMap = appt is Map ? Map<String, dynamic>.from(appt) : const {};
    final ledMap = led is Map ? Map<String, dynamic>.from(led) : const {};
    return SalonCrmStaffPerfItem(
      staffId: int.tryParse('${map['staff_id'] ?? ''}'),
      staffName: '${map['staff_name'] ?? ''}',
      isActive: map['is_active'] != false,
      commissionPercent:
          double.tryParse('${map['commission_percent'] ?? 0}') ?? 0,
      completed: int.tryParse('${apptMap['completed'] ?? 0}') ?? 0,
      scheduled: int.tryParse('${apptMap['scheduled'] ?? 0}') ?? 0,
      cancelled: int.tryParse('${apptMap['cancelled'] ?? 0}') ?? 0,
      noShow: int.tryParse('${apptMap['no_show'] ?? 0}') ?? 0,
      appointmentRevenue:
          double.tryParse('${apptMap['revenue'] ?? 0}') ?? 0,
      ledgerIncome: double.tryParse('${ledMap['income'] ?? 0}') ?? 0,
      totalRevenue: double.tryParse('${map['total_revenue'] ?? 0}') ?? 0,
      staffShare: double.tryParse('${map['staff_share'] ?? apptMap['staff_share'] ?? 0}') ?? 0,
      ownerShare: double.tryParse('${map['owner_share'] ?? apptMap['owner_share'] ?? 0}') ?? 0,
      credit: double.tryParse('${apptMap['credit'] ?? 0}') ?? 0,
    );
  }
}

class SalonCrmPaymentBreakdown {
  SalonCrmPaymentBreakdown({
    required this.cash,
    required this.card,
    required this.iban,
    required this.credit,
    this.cashCount = 0,
    this.cardCount = 0,
    this.ibanCount = 0,
    this.creditCount = 0,
    this.collected = 0,
  });

  final double cash;
  final double card;
  final double iban;
  final double credit;
  final int cashCount;
  final int cardCount;
  final int ibanCount;
  final int creditCount;
  final double collected;

  bool get hasAny =>
      cash > 0 || card > 0 || iban > 0 || credit > 0;

  factory SalonCrmPaymentBreakdown.fromMap(Map<String, dynamic>? map) {
    if (map == null) {
      return SalonCrmPaymentBreakdown(
        cash: 0,
        card: 0,
        iban: 0,
        credit: 0,
      );
    }
    final cash = double.tryParse('${map['cash'] ?? 0}') ?? 0;
    final card = double.tryParse('${map['card'] ?? 0}') ?? 0;
    final iban = double.tryParse('${map['iban'] ?? 0}') ?? 0;
    return SalonCrmPaymentBreakdown(
      cash: cash,
      card: card,
      iban: iban,
      credit: double.tryParse('${map['credit'] ?? 0}') ?? 0,
      cashCount: int.tryParse('${map['cash_count'] ?? 0}') ?? 0,
      cardCount: int.tryParse('${map['card_count'] ?? 0}') ?? 0,
      ibanCount: int.tryParse('${map['iban_count'] ?? 0}') ?? 0,
      creditCount: int.tryParse('${map['credit_count'] ?? 0}') ?? 0,
      collected: double.tryParse('${map['collected'] ?? ''}') ??
          (cash + card + iban),
    );
  }
}

class SalonCrmPerformance {
  SalonCrmPerformance({
    required this.from,
    required this.to,
    required this.completed,
    required this.scheduled,
    required this.appointmentRevenue,
    required this.ledgerIncome,
    required this.totalRevenue,
    required this.staff,
    this.unassigned,
    this.staffShare = 0,
    this.ownerShare = 0,
    this.credit = 0,
    this.cancelled = 0,
    this.noShow = 0,
    this.ledgerExpense = 0,
    this.net = 0,
    this.came = 0,
    this.didNotCome = 0,
    this.uniqueCustomers = 0,
    this.repeatCustomers = 0,
    this.needsOutcome = 0,
    this.paidRevenue = 0,
    SalonCrmPaymentBreakdown? payments,
  }) : payments = payments ??
            SalonCrmPaymentBreakdown(
              cash: 0,
              card: 0,
              iban: 0,
              credit: 0,
            );

  final String from;
  final String to;
  final int completed;
  final int scheduled;
  final int cancelled;
  final int noShow;
  final double appointmentRevenue;
  final double ledgerIncome;
  final double ledgerExpense;
  final double totalRevenue;
  final double net;
  final double staffShare;
  final double ownerShare;
  final double credit;
  final double paidRevenue;
  final int came;
  final int didNotCome;
  final int uniqueCustomers;
  final int repeatCustomers;
  final int needsOutcome;
  final SalonCrmPaymentBreakdown payments;
  final List<SalonCrmStaffPerfItem> staff;
  final SalonCrmStaffPerfItem? unassigned;

  factory SalonCrmPerformance.fromMap(Map<String, dynamic> map) {
    final totals = map['totals'];
    final t = totals is Map ? Map<String, dynamic>.from(totals) : const {};
    final list = map['staff'];
    final un = map['unassigned'];
    final completed = int.tryParse('${t['completed'] ?? 0}') ?? 0;
    final noShow = int.tryParse('${t['no_show'] ?? 0}') ?? 0;
    final payMap = t['payments'];
    return SalonCrmPerformance(
      from: '${map['from'] ?? ''}',
      to: '${map['to'] ?? ''}',
      completed: completed,
      scheduled: int.tryParse('${t['scheduled'] ?? 0}') ?? 0,
      cancelled: int.tryParse('${t['cancelled'] ?? 0}') ?? 0,
      noShow: noShow,
      appointmentRevenue:
          double.tryParse('${t['appointment_revenue'] ?? 0}') ?? 0,
      ledgerIncome: double.tryParse('${t['ledger_income'] ?? 0}') ?? 0,
      ledgerExpense: double.tryParse('${t['ledger_expense'] ?? 0}') ?? 0,
      totalRevenue: double.tryParse('${t['total_revenue'] ?? 0}') ?? 0,
      net: double.tryParse('${t['net'] ?? 0}') ?? 0,
      staffShare: double.tryParse('${t['staff_share'] ?? 0}') ?? 0,
      ownerShare: double.tryParse('${t['owner_share'] ?? 0}') ?? 0,
      credit: double.tryParse('${t['credit'] ?? 0}') ?? 0,
      paidRevenue: double.tryParse('${t['paid_revenue'] ?? 0}') ?? 0,
      came: int.tryParse('${t['came'] ?? completed}') ?? completed,
      didNotCome: int.tryParse('${t['did_not_come'] ?? noShow}') ?? noShow,
      uniqueCustomers: int.tryParse('${t['unique_customers'] ?? 0}') ?? 0,
      repeatCustomers: int.tryParse('${t['repeat_customers'] ?? 0}') ?? 0,
      needsOutcome: int.tryParse('${t['needs_outcome'] ?? 0}') ?? 0,
      payments: SalonCrmPaymentBreakdown.fromMap(
        payMap is Map ? Map<String, dynamic>.from(payMap) : null,
      ),
      staff: list is List
          ? list
              .whereType<Map>()
              .map((e) =>
                  SalonCrmStaffPerfItem.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : [],
      unassigned: un is Map
          ? SalonCrmStaffPerfItem.fromMap(Map<String, dynamic>.from(un))
          : null,
    );
  }
}

class SalonCrmLedgerSummary {
  SalonCrmLedgerSummary({
    required this.income,
    required this.expense,
    required this.net,
  });

  final double income;
  final double expense;
  final double net;

  factory SalonCrmLedgerSummary.fromMap(Map<String, dynamic> map) {
    return SalonCrmLedgerSummary(
      income: double.tryParse('${map['income'] ?? 0}') ?? 0,
      expense: double.tryParse('${map['expense'] ?? 0}') ?? 0,
      net: double.tryParse('${map['net'] ?? 0}') ?? 0,
    );
  }
}

class SalonCrmLedgerEntryItem {
  SalonCrmLedgerEntryItem({
    required this.id,
    required this.type,
    required this.title,
    required this.amount,
    required this.entryDate,
    this.category,
    this.notes,
    this.staffId,
    this.staffName,
    this.source,
    this.paymentMethod,
    this.staffShare,
    this.ownerShare,
  });

  final int id;
  final String type;
  final String title;
  final double amount;
  final String entryDate;
  final String? category;
  final String? notes;
  final int? staffId;
  final String? staffName;
  final String? source;
  final String? paymentMethod;
  final double? staffShare;
  final double? ownerShare;

  bool get isIncome => type == 'income';
  bool get isMarketplace => source == 'marketplace';

  factory SalonCrmLedgerEntryItem.fromMap(Map<String, dynamic> map) {
    return SalonCrmLedgerEntryItem(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      type: '${map['type'] ?? 'income'}',
      title: '${map['title'] ?? ''}',
      amount: double.tryParse('${map['amount'] ?? 0}') ?? 0,
      entryDate: '${map['entry_date'] ?? ''}',
      category: map['category']?.toString(),
      notes: map['notes']?.toString(),
      staffId: int.tryParse('${map['staff_id'] ?? ''}'),
      staffName: map['staff_name']?.toString(),
      source: map['source']?.toString(),
      paymentMethod: map['payment_method']?.toString(),
      staffShare: double.tryParse('${map['staff_share'] ?? ''}'),
      ownerShare: double.tryParse('${map['owner_share'] ?? ''}'),
    );
  }
}

class SalonCrmLedgerDay {
  SalonCrmLedgerDay({
    required this.date,
    required this.summary,
    required this.entries,
  });

  final String date;
  final SalonCrmLedgerSummary summary;
  final List<SalonCrmLedgerEntryItem> entries;
}

class SalonCrmCalendarShare {
  SalonCrmCalendarShare({
    required this.token,
    required this.url,
    required this.horizon,
    required this.salonName,
    required this.personName,
    required this.personRole,
  });

  final String token;
  final String url;
  final String horizon;
  final String salonName;
  final String personName;
  final String personRole;

  factory SalonCrmCalendarShare.fromMap(Map<String, dynamic> map) {
    return SalonCrmCalendarShare(
      token: '${map['token'] ?? ''}',
      url: '${map['url'] ?? ''}',
      horizon: '${map['horizon'] ?? 'today_tomorrow'}',
      salonName: '${map['salon_name'] ?? ''}',
      personName: '${map['person_name'] ?? ''}',
      personRole: '${map['person_role'] ?? 'owner'}',
    );
  }
}

class SalonCrmService {
  SalonCrmService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Future<SalonCrmStatus> fetchStatus(String token) async {
    final uri = Uri.parse(RemoteUrls.salonCrmStatus);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _headers(token)),
    );
    return SalonCrmStatus.fromMap(Map<String, dynamic>.from(response));
  }

  Future<void> updateDeviceToken({
    required String token,
    required String deviceToken,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmDeviceToken);
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        uri,
        headers: _headers(token),
        body: jsonEncode({'device_token': deviceToken}),
      ),
    );
  }

  Future<void> syncPushToken(String crmToken) async {
    if (kDebugMode) return;
    try {
      final deviceToken = await FirebaseMessaging.instance.getToken();
      if (deviceToken == null || deviceToken.isEmpty || crmToken.isEmpty) {
        return;
      }
      await updateDeviceToken(token: crmToken, deviceToken: deviceToken);
    } catch (_) {}
  }

  Future<void> clearPushToken(String crmToken) async {
    try {
      if (crmToken.isEmpty) return;
      await updateDeviceToken(token: crmToken, deviceToken: '');
    } catch (_) {}
  }

  Future<Map<String, dynamic>> patronRegister({
    required String salonName,
    required String ownerName,
    required String username,
    required String password,
    String type = 'kuafor',
    String? phone,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmPatronRegister),
        headers: _jsonHeaders(),
        body: jsonEncode({
          'salon_name': salonName,
          'owner_name': ownerName,
          'username': username,
          'password': password,
          'type': type,
          if (phone != null && phone.isNotEmpty) 'phone': phone,
        }),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> patronLogin({
    required String username,
    required String password,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmPatronLogin),
        headers: _jsonHeaders(),
        body: jsonEncode({
          'username': username,
          'password': password,
        }),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> patronBootstrap(String shoppingToken) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmPatronBootstrap),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $shoppingToken',
        },
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> patronRegisterLinked({
    required String shoppingToken,
    required String salonName,
    String type = 'kuafor',
    String? phone,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmPatronRegisterLinked),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $shoppingToken',
        },
        body: jsonEncode({
          'salon_name': salonName,
          'type': type,
          if (phone != null && phone.isNotEmpty) 'phone': phone,
        }),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> patronSalonSummary(String shoppingToken) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.salonCrmPatronSalon),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $shoppingToken',
        },
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> staffLogin({
    required String username,
    required String password,
    String? salonUsername,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmStaffLogin),
        headers: _jsonHeaders(),
        body: jsonEncode({
          'username': username,
          'password': password,
          if (salonUsername != null && salonUsername.isNotEmpty)
            'salon_username': salonUsername,
        }),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<SalonCrmJoinPreview> fetchJoinPreview(String code) async {
    final normalized = code.trim().toUpperCase();
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.salonCrmJoinPreview(normalized)),
        headers: _jsonHeaders(),
      ),
    );
    return SalonCrmJoinPreview.fromMap(Map<String, dynamic>.from(response));
  }

  Future<Map<String, dynamic>> customerRegister({
    required String joinCode,
    required String name,
    required String phone,
    required String password,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmCustomerRegister),
        headers: _jsonHeaders(),
        body: jsonEncode({
          'join_code': joinCode.trim().toUpperCase(),
          'name': name,
          'phone': phone,
          'password': password,
        }),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<Map<String, dynamic>> customerLogin({
    required String joinCode,
    required String phone,
    required String password,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmCustomerLogin),
        headers: _jsonHeaders(),
        body: jsonEncode({
          'join_code': joinCode.trim().toUpperCase(),
          'phone': phone,
          'password': password,
        }),
      ),
    );
    return Map<String, dynamic>.from(response);
  }

  Future<SalonCrmCustomerCatalog> fetchCustomerCatalog(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.salonCrmCustomerCatalog),
        headers: _headers(token),
      ),
    );
    return SalonCrmCustomerCatalog.fromMap(Map<String, dynamic>.from(response));
  }

  Future<List<SalonCrmAppointmentItem>> fetchCustomerAppointments(
    String token,
  ) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.salonCrmCustomerAppointments),
        headers: _headers(token),
      ),
    );
    final map = Map<String, dynamic>.from(response);
    final list = map['appointments'];
    if (list is! List) return [];
    return list
        .whereType<Map>()
        .map((e) =>
            SalonCrmAppointmentItem.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<SalonCrmAppointmentItem> createCustomerAppointment({
    required String token,
    required DateTime startsAt,
    int? serviceId,
    String? serviceName,
    int? staffId,
    int? durationMinutes,
    String? notes,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmCustomerAppointments),
        headers: _headers(token),
        body: jsonEncode({
          'starts_at': SalonCrmDates.toApiDateTime(startsAt),
          if (serviceId != null) 'service_id': serviceId,
          if (serviceName != null && serviceName.isNotEmpty)
            'service_name': serviceName,
          if (staffId != null) 'staff_id': staffId,
          if (durationMinutes != null) 'duration_minutes': durationMinutes,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        }),
      ),
    );
    final map = Map<String, dynamic>.from(response);
    final appt = map['appointment'];
    if (appt is Map) {
      return SalonCrmAppointmentItem.fromMap(Map<String, dynamic>.from(appt));
    }
    throw Exception('Randevu oluşturulamadı');
  }

  Future<SalonCrmStatus> register({
    required String token,
    required String name,
    String type = 'kuafor',
    String? phone,
  }) async {
    throw Exception('CRM kaydı ayrı giriş ekranından yapılır');
  }

  Future<List<SalonCrmStaffItem>> fetchStaff(String token) async {
    final uri = Uri.parse(RemoteUrls.salonCrmStaff);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _headers(token)),
    );
    final map = Map<String, dynamic>.from(response);
    final list = map['staff'];
    if (list is! List) return [];
    return list
        .whereType<Map>()
        .map((e) => SalonCrmStaffItem.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<void> createStaff({
    required String token,
    required String name,
    required String username,
    required String password,
    double? commissionPercent,
    String payType = 'percent',
    String payPeriod = 'monthly',
    double? salaryAmount,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmStaff);
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        uri,
        headers: _headers(token),
        body: jsonEncode({
          'name': name,
          'username': username,
          'password': password,
          'pay_type': payType,
          'pay_period': payPeriod,
          if (commissionPercent != null) 'commission_percent': commissionPercent,
          if (salaryAmount != null) 'salary_amount': salaryAmount,
        }),
      ),
    );
  }

  Future<void> updateStaff({
    required String token,
    required int staffId,
    double? commissionPercent,
    String? payType,
    String? payPeriod,
    double? salaryAmount,
    bool? isActive,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.patch(
        Uri.parse(RemoteUrls.salonCrmStaffUpdate(staffId)),
        headers: _headers(token),
        body: jsonEncode({
          if (commissionPercent != null) 'commission_percent': commissionPercent,
          if (payType != null) 'pay_type': payType,
          if (payPeriod != null) 'pay_period': payPeriod,
          if (salaryAmount != null) 'salary_amount': salaryAmount,
          if (isActive != null) 'is_active': isActive,
        }),
      ),
    );
  }

  Future<void> deactivateStaff({
    required String token,
    required int staffId,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.delete(
        Uri.parse(RemoteUrls.salonCrmStaffUpdate(staffId)),
        headers: _headers(token),
      ),
    );
  }

  Future<void> syncStaffHours({
    required String token,
    required int staffId,
    required List<Map<String, dynamic>> hours,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmStaffHours(staffId)),
        headers: _headers(token),
        body: jsonEncode({'hours': hours}),
      ),
    );
  }

  Future<void> updateStaffCommission({
    required String token,
    required int staffId,
    required double commissionPercent,
  }) {
    return updateStaff(
      token: token,
      staffId: staffId,
      payType: 'percent',
      commissionPercent: commissionPercent,
    );
  }

  Future<SalonCrmStaffDetail> fetchStaffDetail(
    String token, {
    required int staffId,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.salonCrmStaffShow(staffId)),
        headers: _headers(token),
      ),
    );
    return SalonCrmStaffDetail.fromMap(Map<String, dynamic>.from(response));
  }

  Future<void> syncStaffServices({
    required String token,
    required int staffId,
    required List<Map<String, dynamic>> services,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmStaffServices(staffId)),
        headers: _headers(token),
        body: jsonEncode({'services': services}),
      ),
    );
  }

  Future<SalonCrmSalaryPaymentItem> createSalaryPayment({
    required String token,
    required int staffId,
    String? periodKey,
    double? amount,
    String? notes,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmSalaryPayments),
        headers: _headers(token),
        body: jsonEncode({
          'staff_id': staffId,
          if (periodKey != null && periodKey.isNotEmpty) 'period_key': periodKey,
          if (amount != null) 'amount': amount,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        }),
      ),
    );
    final map = Map<String, dynamic>.from(response);
    final payment = map['payment'];
    if (payment is Map) {
      return SalonCrmSalaryPaymentItem.fromMap(Map<String, dynamic>.from(payment));
    }
    throw Exception('Maaş ödemesi oluşturulamadı');
  }

  Future<SalonCrmSalaryPaymentItem> confirmSalaryPayment({
    required String token,
    required int paymentId,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.patch(
        Uri.parse(RemoteUrls.salonCrmSalaryPaymentConfirm(paymentId)),
        headers: _headers(token),
        body: jsonEncode({}),
      ),
    );
    final map = Map<String, dynamic>.from(response);
    final payment = map['payment'];
    if (payment is Map) {
      return SalonCrmSalaryPaymentItem.fromMap(Map<String, dynamic>.from(payment));
    }
    throw Exception('Onay kaydedilemedi');
  }

  Future<SalonCrmSalonProfile> fetchSalonProfile(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.salonCrmProfile),
        headers: _headers(token),
      ),
    );
    final map = Map<String, dynamic>.from(response);
    final profile = map['profile'];
    if (profile is Map) {
      return SalonCrmSalonProfile.fromMap(Map<String, dynamic>.from(profile));
    }
    throw Exception('Profil yüklenemedi');
  }

  Future<SalonCrmCalendarShare> fetchCalendarShare(String token) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(
        Uri.parse(RemoteUrls.salonCrmCalendarShare),
        headers: _headers(token),
      ),
    );
    return SalonCrmCalendarShare.fromMap(Map<String, dynamic>.from(response));
  }

  Future<SalonCrmCalendarShare> updateCalendarShare({
    required String token,
    required String horizon,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.patch(
        Uri.parse(RemoteUrls.salonCrmCalendarShare),
        headers: _headers(token),
        body: jsonEncode({'horizon': horizon}),
      ),
    );
    return SalonCrmCalendarShare.fromMap(Map<String, dynamic>.from(response));
  }

  Future<SalonCrmSalonProfile> updateSalonProfile({
    required String token,
    String? name,
    String? phone,
    String? profileText,
    bool? showProfileToCustomers,
    int? openHour,
    int? closeHour,
    String? logoImagePath,
    String? coverImagePath,
    bool removeLogo = false,
    bool removeCover = false,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.salonCrmProfile),
    );
    request.headers.addAll(_multipartHeaders(token));
    if (name != null) request.fields['name'] = name;
    if (phone != null) request.fields['phone'] = phone;
    if (profileText != null) request.fields['profile_text'] = profileText;
    if (showProfileToCustomers != null) {
      request.fields['show_profile_to_customers'] =
          showProfileToCustomers ? '1' : '0';
    }
    if (openHour != null) request.fields['open_hour'] = '$openHour';
    if (closeHour != null) request.fields['close_hour'] = '$closeHour';
    if (removeLogo) request.fields['remove_logo'] = '1';
    if (removeCover) request.fields['remove_cover'] = '1';
    if (logoImagePath != null && logoImagePath.isNotEmpty) {
      request.files.add(await _imagePart('logo_image', logoImagePath));
    }
    if (coverImagePath != null && coverImagePath.isNotEmpty) {
      request.files.add(await _imagePart('cover_image', coverImagePath));
    }

    final streamed = await request.send();
    final response = await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
    final map = Map<String, dynamic>.from(response);
    final profile = map['profile'];
    if (profile is Map) {
      return SalonCrmSalonProfile.fromMap(Map<String, dynamic>.from(profile));
    }
    throw Exception('Profil güncellenemedi');
  }

  Future<SalonCrmStaffItem> updateStaffPhoto({
    required String token,
    required int staffId,
    String? photoPath,
    bool? showPhotoToCustomers,
    bool removePhoto = false,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse(RemoteUrls.salonCrmStaffPhoto(staffId)),
    );
    request.headers.addAll(_multipartHeaders(token));
    if (removePhoto) request.fields['remove_photo'] = '1';
    if (showPhotoToCustomers != null) {
      request.fields['show_photo_to_customers'] =
          showPhotoToCustomers ? '1' : '0';
    }
    if (photoPath != null && photoPath.isNotEmpty) {
      request.files.add(await _imagePart('photo', photoPath));
    }

    final streamed = await request.send();
    final response = await NetworkParser.callClientWithCatchException(
      () => http.Response.fromStream(streamed),
    );
    final map = Map<String, dynamic>.from(response);
    final staff = map['staff'];
    if (staff is Map) {
      return SalonCrmStaffItem.fromMap(Map<String, dynamic>.from(staff));
    }
    throw Exception('Fotoğraf güncellenemedi');
  }

  Future<http.MultipartFile> _imagePart(String field, String path) async {
    return http.MultipartFile.fromPath(
      field,
      path,
      filename: path.replaceAll('\\', '/').split('/').last,
    );
  }

  Map<String, String> _multipartHeaders(String token) => {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
        'X-Salon-Crm-Token': token,
      };

  Future<List<SalonCrmServiceItem>> fetchServices(String token) async {
    final uri = Uri.parse(RemoteUrls.salonCrmServices);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _headers(token)),
    );
    final map = Map<String, dynamic>.from(response);
    final list = map['services'];
    if (list is! List) return [];
    return list
        .whereType<Map>()
        .map((e) => SalonCrmServiceItem.fromMap(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<SalonCrmServiceItem> createService({
    required String token,
    required String name,
    int durationMinutes = 30,
    double price = 0,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        Uri.parse(RemoteUrls.salonCrmServices),
        headers: _headers(token),
        body: jsonEncode({
          'name': name,
          'duration_minutes': durationMinutes,
          'price': price,
        }),
      ),
    );
    final map = Map<String, dynamic>.from(response);
    final service = map['service'];
    if (service is Map) {
      return SalonCrmServiceItem.fromMap(Map<String, dynamic>.from(service));
    }
    throw Exception('Hizmet eklenemedi');
  }

  Future<SalonCrmServiceItem> updateService({
    required String token,
    required int id,
    required String name,
    required int durationMinutes,
    required double price,
    bool? isActive,
  }) async {
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.patch(
        Uri.parse(RemoteUrls.salonCrmServiceUpdate(id)),
        headers: _headers(token),
        body: jsonEncode({
          'name': name,
          'duration_minutes': durationMinutes,
          'price': price,
          if (isActive != null) 'is_active': isActive,
        }),
      ),
    );
    final map = Map<String, dynamic>.from(response);
    final service = map['service'];
    if (service is Map) {
      return SalonCrmServiceItem.fromMap(Map<String, dynamic>.from(service));
    }
    throw Exception('Hizmet güncellenemedi');
  }

  Future<SalonCrmAppointmentsResult> fetchAppointments(
    String token, {
    required String date,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmAppointments).replace(
      queryParameters: {'date': date},
    );
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _headers(token)),
    );
    final map = Map<String, dynamic>.from(response);
    final list = map['appointments'];
    final occ = map['occupancy'];
    return SalonCrmAppointmentsResult(
      appointments: list is List
          ? list
              .whereType<Map>()
              .map((e) =>
                  SalonCrmAppointmentItem.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : [],
      occupancy: occ is List
          ? occ
              .whereType<Map>()
              .map((e) =>
                  SalonCrmDayOccupancy.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : [],
      daySummary: SalonCrmDaySummary.fromMap(
        map['day_summary'] is Map
            ? Map<String, dynamic>.from(map['day_summary'] as Map)
            : null,
      ),
    );
  }

  Future<void> createAppointment({
    required String token,
    required DateTime startsAt,
    String? customerName,
    String? customerPhone,
    int? customerId,
    int? staffId,
    int? serviceId,
    String? serviceName,
    int? durationMinutes,
    double? price,
    String? notes,
    bool isBlock = false,
    String? blockType,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmAppointments);
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        uri,
        headers: _headers(token),
        body: jsonEncode({
          'starts_at': SalonCrmDates.toApiDateTime(startsAt),
          'is_block': isBlock,
          if (blockType != null && blockType.isNotEmpty) 'block_type': blockType,
          if (customerId != null) 'customer_id': customerId,
          if (customerName != null && customerName.isNotEmpty)
            'customer_name': customerName,
          if (customerPhone != null && customerPhone.isNotEmpty)
            'customer_phone': customerPhone,
          if (staffId != null) 'staff_id': staffId,
          if (serviceId != null) 'service_id': serviceId,
          if (serviceName != null && serviceName.isNotEmpty)
            'service_name': serviceName,
          if (durationMinutes != null) 'duration_minutes': durationMinutes,
          if (price != null) 'price': price,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        }),
      ),
    );
  }

  Future<void> updateAppointment({
    required String token,
    required int id,
    required DateTime startsAt,
    String? customerName,
    String? customerPhone,
    int? customerId,
    int? staffId,
    int? serviceId,
    String? serviceName,
    int? durationMinutes,
    String? notes,
    bool isBlock = false,
    String? blockType,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.patch(
        Uri.parse(RemoteUrls.salonCrmAppointmentUpdate(id)),
        headers: _headers(token),
        body: jsonEncode({
          'starts_at': SalonCrmDates.toApiDateTime(startsAt),
          'is_block': isBlock,
          'staff_id': staffId,
          'customer_id': customerId,
          'service_id': serviceId,
          if (blockType != null && blockType.isNotEmpty) 'block_type': blockType,
          if (customerName != null && customerName.isNotEmpty)
            'customer_name': customerName,
          if (customerPhone != null && customerPhone.isNotEmpty)
            'customer_phone': customerPhone,
          if (serviceName != null && serviceName.isNotEmpty)
            'service_name': serviceName,
          if (durationMinutes != null) 'duration_minutes': durationMinutes,
          'notes': notes,
        }),
      ),
    );
  }

  Future<List<SalonCrmCustomerItem>> fetchCustomers(
    String token, {
    String q = '',
    int? onlyStaffId,
  }) async {
    final params = <String, String>{};
    if (q.trim().isNotEmpty) params['q'] = q.trim();
    final uri = params.isEmpty
        ? Uri.parse(RemoteUrls.salonCrmCustomers)
        : Uri.parse(RemoteUrls.salonCrmCustomers).replace(queryParameters: params);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _headers(token)),
    );
    final map = Map<String, dynamic>.from(response);
    final list = map['customers'];
    if (list is! List) return [];
    var items = list
        .whereType<Map>()
        .map((e) => SalonCrmCustomerItem.fromMap(Map<String, dynamic>.from(e)))
        .toList();
    // Personel: yalnızca sahiplik bilgisi geldiyse filtrele (eski API'de boşaltma)
    if (onlyStaffId != null && onlyStaffId > 0) {
      final hasOwnership = items.any((c) => c.staffId > 0);
      if (hasOwnership) {
        items = items.where((c) => c.staffId == onlyStaffId).toList();
      }
    }
    return items;
  }

  Future<SalonCrmCustomerItem> createCustomer({
    required String token,
    required String name,
    required String phone,
    String? notes,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmCustomers);
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.post(
        uri,
        headers: _headers(token),
        body: jsonEncode({
          'name': name,
          'phone': phone,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        }),
      ),
    );
    final map = Map<String, dynamic>.from(response);
    final customer = map['customer'];
    if (customer is Map) {
      return SalonCrmCustomerItem.fromMap(Map<String, dynamic>.from(customer));
    }
    throw Exception('Müşteri oluşturulamadı');
  }

  Future<void> updateCustomerNotes({
    required String token,
    required int id,
    required String notes,
  }) async {
    await NetworkParser.callClientWithCatchException(
      () => _client.patch(
        Uri.parse(RemoteUrls.salonCrmCustomerUpdate(id)),
        headers: _headers(token),
        body: jsonEncode({'notes': notes}),
      ),
    );
  }

  Future<void> updateAppointmentStatus({
    required String token,
    required int id,
    required String status,
    double? price,
    String? paymentMethod,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmAppointmentStatus(id));
    await NetworkParser.callClientWithCatchException(
      () => _client.patch(
        uri,
        headers: _headers(token),
        body: jsonEncode({
          'status': status,
          if (price != null) 'price': price,
          if (paymentMethod != null && paymentMethod.isNotEmpty)
            'payment_method': paymentMethod,
        }),
      ),
    );
  }

  Future<SalonCrmLedgerDay> fetchLedger(
    String token, {
    required String date,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmLedger).replace(
      queryParameters: {'date': date},
    );
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _headers(token)),
    );
    final map = Map<String, dynamic>.from(response);
    final summaryMap = map['summary'];
    final list = map['entries'];
    return SalonCrmLedgerDay(
      date: '${map['date'] ?? date}',
      summary: summaryMap is Map
          ? SalonCrmLedgerSummary.fromMap(Map<String, dynamic>.from(summaryMap))
          : SalonCrmLedgerSummary(income: 0, expense: 0, net: 0),
      entries: list is List
          ? list
              .whereType<Map>()
              .map((e) =>
                  SalonCrmLedgerEntryItem.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : [],
    );
  }

  Future<SalonCrmPerformance> fetchPerformance(
    String token, {
    required String from,
    required String to,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmPerformance).replace(
      queryParameters: {'from': from, 'to': to},
    );
    final response = await NetworkParser.callClientWithCatchException(
      () => _client.get(uri, headers: _headers(token)),
    );
    return SalonCrmPerformance.fromMap(Map<String, dynamic>.from(response));
  }

  Future<void> createLedgerEntry({
    required String token,
    required String type,
    required String title,
    required double amount,
    String? entryDate,
    String? category,
    int? staffId,
    String? notes,
  }) async {
    final uri = Uri.parse(RemoteUrls.salonCrmLedger);
    await NetworkParser.callClientWithCatchException(
      () => _client.post(
        uri,
        headers: _headers(token),
        body: jsonEncode({
          'type': type,
          'title': title,
          'amount': amount,
          if (entryDate != null) 'entry_date': entryDate,
          if (category != null && category.isNotEmpty) 'category': category,
          if (staffId != null) 'staff_id': staffId,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
        }),
      ),
    );
  }

  Map<String, String> _jsonHeaders() => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      };

  Map<String, String> _headers(String token) => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
        'X-Salon-Crm-Token': token,
      };
}
