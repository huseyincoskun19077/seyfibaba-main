import 'dart:convert';
import 'package:equatable/equatable.dart';

class SetPasswordModel extends Equatable {
  final String code;
  final String email;
  final String phone;
  final String password;
  final String passwordConfirmation;
  const SetPasswordModel({
    required this.code,
    this.email = '',
    this.phone = '',
    required this.password,
    required this.passwordConfirmation,
  });

  SetPasswordModel copyWith({
    String? code,
    String? email,
    String? phone,
    String? password,
    String? passwordConfirmation,
  }) {
    return SetPasswordModel(
      code: code ?? this.code,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      password: password ?? this.password,
      passwordConfirmation: passwordConfirmation ?? this.passwordConfirmation,
    );
  }

  Map<String, dynamic> toMap() {
    final result = <String, dynamic>{
      'password': password,
      'password_confirmation': passwordConfirmation,
      'otp_verified_token': code,
    };
    if (phone.isNotEmpty) result['phone'] = phone;
    if (email.isNotEmpty) result['email'] = email;
    return result;
  }

  factory SetPasswordModel.fromMap(Map<String, dynamic> map) {
    return SetPasswordModel(
      code: map['code'] ?? '',
      email: map['email'] ?? '',
      password: map['password'] ?? '',
      passwordConfirmation: map['password_confirmation'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory SetPasswordModel.fromJson(String source) =>
      SetPasswordModel.fromMap(json.decode(source));

  @override
  String toString() {
    return 'SetPasswordModel(code: $code, email: $email, password: $password, password_confirmation: $passwordConfirmation)';
  }

  @override
  List<Object> get props => [code, email, password, passwordConfirmation];
}
