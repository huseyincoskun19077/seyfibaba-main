part of 'login_bloc.dart';

enum LoginInputType { email, phone }

class LoginModelState extends Equatable {
  final String text;
  final String password;
  final String langCode;
  final bool showPassword;
  final bool rememberMe;
  final LoginInputType loginInputType;
  final LoginState state;

  const LoginModelState({
    this.text = '',
    this.password = '',
    this.langCode = 'tr',
    this.showPassword = true,
    this.rememberMe = false,
    this.loginInputType = LoginInputType.email,
    this.state = const LoginStateInitial(),
  });

  LoginModelState copyWith({
    String? text,
    String? password,
    String? langCode,
    bool? showPassword,
    bool? rememberMe,
    LoginInputType? loginInputType,
    LoginState? state,
  }) {
    return LoginModelState(
      text: text ?? this.text,
      password: password ?? this.password,
      langCode: langCode ?? this.langCode,
      showPassword: showPassword ?? this.showPassword,
      rememberMe: rememberMe ?? this.rememberMe,
      loginInputType: loginInputType ?? this.loginInputType,
      state: state ?? this.state,
    );
  }

  /// API `email` alanına web ile aynı mantıkta değer gönderilir.
  String loginIdentifierForApi() {
    if (loginInputType == LoginInputType.phone) {
      final digits = text.replaceAll(RegExp(r'\D'), '');
      if (digits.length == 12 && digits.startsWith('90')) {
        return '+$digits';
      }
      if (digits.length == 10 && digits.startsWith('5')) {
        return '+90$digits';
      }
      final trimmed = text.trim();
      if (trimmed.startsWith('+')) return trimmed;
      return '+90$digits';
    }
    return text.trim();
  }

  Map<String, dynamic> toMap() {
    return {
      'email': loginIdentifierForApi(),
      'password': password,
    };
  }

  factory LoginModelState.fromMap(Map<String, dynamic> map) {
    return LoginModelState(
      text: map['text'] ?? '',
      password: map['password'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory LoginModelState.fromJson(String source) =>
      LoginModelState.fromMap(json.decode(source));

  @override
  String toString() =>
      'LoginModelState(username: $text, password: $password,langCode: $langCode, state: $state)';

  @override
  List<Object> get props =>
      [text, password, langCode, state, showPassword, rememberMe, loginInputType];
}

abstract class LoginState extends Equatable {
  const LoginState();

  @override
  List<Object> get props => [];
}

class LoginStateInitial extends LoginState {
  const LoginStateInitial();
}

class LoginStateFormInvalid extends LoginState {
  final Errors error;

  const LoginStateFormInvalid(this.error);

  @override
  List<Object> get props => [error];
}

class LoginStateLoading extends LoginState {
  const LoginStateLoading();
}

class GoogleStateLoading extends LoginState {
  const GoogleStateLoading();
}

class AppleStateLoading extends LoginState {
  const AppleStateLoading();
}

class FacebookStateLoading extends LoginState {
  const FacebookStateLoading();
}

class LoginStateLogOutLoading extends LoginState {
  const LoginStateLogOutLoading();
}

class LoginStateLoaded extends LoginState {
  final UserLoginResponseModel user;

  const LoginStateLoaded(this.user);

  @override
  List<Object> get props => [user];
}

class LoginStateUpdatedProfile extends LoginState {
  final UserLoginResponseModel user;

  const LoginStateUpdatedProfile(this.user);

  @override
  List<Object> get props => [user];
}

class LoginStateError extends LoginState {
  final String errorMsg;
  final int statusCode;

  const LoginStateError(this.errorMsg, this.statusCode);

  @override
  List<Object> get props => [errorMsg, statusCode];
}

class LoginStateSignOutError extends LoginState {
  final String errorMsg;
  final int statusCode;

  const LoginStateSignOutError(this.errorMsg, this.statusCode);

  @override
  List<Object> get props => [errorMsg, statusCode];
}

class AccountActivateSuccess extends LoginState {
  final String msg;

  const AccountActivateSuccess(this.msg);

  @override
  List<Object> get props => [msg];
}

class SendAccountCodeSuccess extends LoginState {
  final String msg;

  const SendAccountCodeSuccess(this.msg);

  @override
  List<Object> get props => [msg];
}

class LoginStateLogOut extends LoginState {
  final String msg;
  final int statusCode;

  const LoginStateLogOut(this.msg, this.statusCode);

  @override
  List<Object> get props => [msg, statusCode];
}
