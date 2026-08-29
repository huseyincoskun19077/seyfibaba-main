// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';

class PusherInfo extends Equatable {
  final String appKey;
  final String appCluster;

  const PusherInfo({
    required this.appKey,
    required this.appCluster,
  });

  PusherInfo copyWith({
    String? appKey,
    String? appCluster,
  }) {
    return PusherInfo(
      appKey: appKey ?? this.appKey,
      appCluster: appCluster ?? this.appCluster,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'app_key': appKey,
      'app_cluster': appCluster,
    };
  }

  factory PusherInfo.fromMap(Map<String, dynamic> map) {
    return PusherInfo(
      appKey: map['app_key']?.toString() ?? '',
      appCluster: map['app_cluster']?.toString() ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory PusherInfo.fromJson(String source) =>
      PusherInfo.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props => [appKey, appCluster];
}
