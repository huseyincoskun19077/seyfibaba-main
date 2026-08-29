part of 'sign_up_bloc.dart';

class SignUpModelState extends Equatable {
  final int agree;
  final String name;
  final String email;
  final String phone;
  final String countryCode;
  final String phoneNumber;
  final String password;
  final bool showPassword;
  final bool showConPassword;
  final bool active;
  final String passwordConfirmation;
  final bool awaitingOtp;
  final SignUpState state;

  const SignUpModelState({
    this.agree = 0,
    this.name = '',
    this.email = '',
    this.phone = '',
    this.countryCode = '',
    this.phoneNumber = '',
    this.password = '',
    this.passwordConfirmation = '',
    this.showPassword = false,
    this.showConPassword = false,
    this.active = false,
    this.awaitingOtp = false,
    this.state = const SignUpStateInitial(),
  });

  SignUpModelState copyWith({
    int? agree,
    String? name,
    String? email,
    String? phone,
    String? countryCode,
    String? phoneNumber,
    String? password,
    String? passwordConfirmation,
    bool? showPassword,
    bool? showConPassword,
    bool? active,
    bool? awaitingOtp,
    SignUpState? state,
  }) {
    return SignUpModelState(
      agree: agree ?? this.agree,
      name: name ?? this.name,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      countryCode: countryCode ?? this.countryCode,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      password: password ?? this.password,
      showPassword: showPassword ?? this.showPassword,
      showConPassword: showConPassword ?? this.showConPassword,
      active: active ?? this.active,
      awaitingOtp: awaitingOtp ?? this.awaitingOtp,
      passwordConfirmation: passwordConfirmation ?? this.passwordConfirmation,
      state: state ?? this.state,
    );
  }

  String get fullPhone {
    final raw = phone.isNotEmpty ? '$countryCode$phone' : '';
    return raw.replaceAll(' ', '');
  }

  Map<String, dynamic> toMap({String? otpVerifiedToken}) {
    final result = <String, dynamic>{};

    result.addAll({'agree': agree.toString()});
    result.addAll({'name': name.trim()});
    result.addAll({'email': email.trim()});
    result.addAll({'phone': fullPhone});
    result.addAll({'password': password});
    result.addAll({'password_confirmation': passwordConfirmation});
    if (otpVerifiedToken != null && otpVerifiedToken.isNotEmpty) {
      result.addAll({'otp_verified_token': otpVerifiedToken});
    }

    return result;
  }

  String toJson() => json.encode(toMap());

  @override
  String toString() {
    return 'SignUpModelState(agree: $agree, name: $name, email: $email, phone: $phone, countryCode: $countryCode, password: $password, passwordConfirmation: $passwordConfirmation, state: $state)';
  }

  @override
  List<Object> get props {
    return [
      agree,
      name,
      email,
      phone,
      countryCode,
      phoneNumber,
      password,
      passwordConfirmation,
      state,
      showPassword,
      showConPassword,
      active,
      awaitingOtp,
    ];
  }
}

abstract class SignUpState extends Equatable {
  const SignUpState();

  @override
  List<Object> get props => [];
}

class SignUpStateInitial extends SignUpState {
  const SignUpStateInitial();
}

class SignUpStateLoading extends SignUpState {
  const SignUpStateLoading();
}

class SignUpStateLoaded extends SignUpState {
  const SignUpStateLoaded(this.msg);

  final String msg;

  @override
  List<Object> get props => [msg];
}

class SignUpStateLoggedIn extends SignUpState {
  const SignUpStateLoggedIn(this.user);

  final UserLoginResponseModel user;

  @override
  List<Object> get props => [user];
}

class SignUpStateFormValidationError extends SignUpState {
  final Errors errors;

  const SignUpStateFormValidationError(this.errors);

  @override
  List<Object> get props => [errors];
}

class SignUpStateFormError extends SignUpState {
  final String errorMsg;
  final int statusCode;

  const SignUpStateFormError(this.errorMsg, this.statusCode);

  @override
  List<Object> get props => [errorMsg, statusCode];
}
