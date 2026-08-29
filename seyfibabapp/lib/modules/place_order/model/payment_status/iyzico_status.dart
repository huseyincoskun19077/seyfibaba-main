import 'dart:convert';

import 'package:equatable/equatable.dart';

class IyzicoStatus extends Equatable {
  final int status;
  final int marketplaceMode;
  final int isTestMode;

  const IyzicoStatus({
    required this.status,
    this.marketplaceMode = 0,
    this.isTestMode = 0,
  });

  IyzicoStatus copyWith({
    int? status,
    int? marketplaceMode,
    int? isTestMode,
  }) {
    return IyzicoStatus(
      status: status ?? this.status,
      marketplaceMode: marketplaceMode ?? this.marketplaceMode,
      isTestMode: isTestMode ?? this.isTestMode,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'status': status,
      'marketplace_mode': marketplaceMode,
      'is_test_mode': isTestMode,
    };
  }

  factory IyzicoStatus.fromMap(Map<String, dynamic> map) {
    return IyzicoStatus(
      status: map['status'] != null ? int.parse(map['status'].toString()) : 0,
      marketplaceMode: map['marketplace_mode'] != null
          ? int.parse(map['marketplace_mode'].toString())
          : 0,
      isTestMode: map['is_test_mode'] != null
          ? int.parse(map['is_test_mode'].toString())
          : 0,
    );
  }

  String toJson() => json.encode(toMap());

  factory IyzicoStatus.fromJson(String source) =>
      IyzicoStatus.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props => [status, marketplaceMode, isTestMode];
}
