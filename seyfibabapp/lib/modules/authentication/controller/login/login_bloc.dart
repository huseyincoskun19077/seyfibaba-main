import 'dart:async';
import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';

import '../../../../core/error/failure.dart';
import '../../../profile/controllers/repository/profile_repository.dart';
import '../../../profile/model/country_model.dart';
import '../../../profile/model/user_with_country_response.dart';
import '../../models/auth_error_model.dart';
import '../../models/user_login_response_model.dart';
import '../../repository/auth_repository.dart';

part 'login_event.dart';

part 'login_state.dart';

class LoginBloc extends Bloc<LoginEvent, LoginModelState> {
  final AuthRepository _authRepository;
  final ProfileRepository _profileRepository;
  final formKey = GlobalKey<FormState>();

  final List<CountryModel> countries = [];

  UserLoginResponseModel? _user;

  bool get isLogedIn => _user != null && _user!.accessToken.isNotEmpty;

  UserLoginResponseModel? get userInfo => _user;

  set user(UserLoginResponseModel userData) => _user = userData;

  LoginBloc({
    required AuthRepository authRepository,
    required ProfileRepository profileRepository,
  })  : _authRepository = authRepository,
        _profileRepository = profileRepository,
        super(const LoginModelState()) {
    on<LoginEventLoginType>((event, emit) {
      emit(state.copyWith(
        loginInputType: event.loginInputType,
        text: '',
        state: const LoginStateInitial(),
      ));
    });
    on<LoginEvenEmailOrPhone>((event, emit) {
      emit(state.copyWith(text: event.text, state: const LoginStateInitial()));
    });
    on<LoginEventPassword>((event, emit) {
      emit(state.copyWith(
          password: event.password, state: const LoginStateInitial()));
    });
    on<ShowPasswordEvent>((event, emit) {
      emit(state.copyWith(
          showPassword: !(event.showPassword),
          state: const LoginStateInitial()));
    });
    on<RememberMeEvent>((event, emit) {
      emit(state.copyWith(
          rememberMe: !(event.rememberMe), state: const LoginStateInitial()));
    });
    on<LoginEventLanguageCode>((event, emit) {
      // debugPrint('added-new-lang-code ${event.langCode}');
      emit(state.copyWith(langCode: event.langCode));
    });
    on<LoginEventSubmit>(_submitLoginForm);
    on<GoogleSignInEvent>(_googleSocialAuth);
    on<AppleSignInEvent>(_appleSocialAuth);
    on<SentAccountActivateCodeSubmit>(_sentAccountActivateCode);
    on<AccountActivateCodeSubmit>(_accountActivateCode);
    on<LoginEventLogout>(_logOut);
    on<LoginEventCheckProfile>(_getUserInfo);

    /// set user data if usre already login

    final result = _authRepository.getCashedUserInfo();

    result.fold(
      (l) => _user = null,
      (r) {
        user = r;
      },
    );
  }

  Future<void> _getUserInfo(
    LoginEventCheckProfile event,
    Emitter<LoginModelState> emit,
  ) async {
    final result = _authRepository.getCashedUserInfo();

    result.fold(
      (l) => _user = null,
      (r) {
        user = r;
        emit(state.copyWith(state: LoginStateLoaded(r)));
      },
    );

    ///load user info if user loged in and update user on bloc state
    if (isLogedIn) {
      final updateProfile =
          await _profileRepository.userProfile(userInfo!.accessToken);

      updateProfile.fold(
        (failure) {
          if (failure.statusCode == 401) {
            final currentState = LoginStateLogOut(
                'Session expired, Sign-in again', failure.statusCode);
            emit(state.copyWith(state: currentState));
          } else {
            final currentState =
                LoginStateError(failure.message, failure.statusCode);
            emit(state.copyWith(state: currentState));
          }
        },
        (UserWithCountryResponse userCountry) {
          user = (_user?.copyWith(user: userCountry.user))!;
          countries.clear();
          countries.addAll(userCountry.countries);
          emit(state.copyWith(state: LoginStateUpdatedProfile(userInfo!)));
        },
      );
    } else {
      _user = null;
      const currentState = LoginStateInitial();
      emit(state.copyWith(state: currentState));
    }
  }

  Future<void> _submitLoginForm(
    LoginEventSubmit event,
    Emitter<LoginModelState> emit,
  ) async {
    final validationError = _validateLoginForm(state);
    if (validationError != null) {
      emit(state.copyWith(
        state: LoginStateError(validationError, 422),
      ));
      return;
    }

    if (formKey.currentState != null && !formKey.currentState!.validate()) {
      return;
    }

    emit(state.copyWith(state: const LoginStateLoading()));
    final bodyData = state.toMap();

    final result = await _authRepository.login(bodyData);

    result.fold(
      (failure) {
        if (failure is InvalidAuthData) {
          final errors = LoginStateFormInvalid(failure.errors);
          emit(state.copyWith(state: errors));
        } else {
          final error = LoginStateError(failure.message, failure.statusCode);
          emit(state.copyWith(state: error));
        }
      },
      (user) {
        final loadedData = LoginStateLoaded(user);
        _user = user;
        emit(state.copyWith(state: loadedData));
      },
    );
  }

  String? _validateLoginForm(LoginModelState current) {
    if (current.password.trim().isEmpty) {
      return 'Şifre gereklidir.';
    }
    if (current.loginInputType == LoginInputType.email) {
      final email = current.text.trim();
      if (email.isEmpty) {
        return 'E-posta adresi gereklidir.';
      }
      if (!RegExp(r'^[^@]+@[^@]+\.[^@]+').hasMatch(email)) {
        return 'Geçerli bir e-posta adresi girin.';
      }
      return null;
    }
    final digits = current.text.replaceAll(RegExp(r'\D'), '');
    if (digits.isEmpty) {
      return 'Telefon numarası gereklidir.';
    }
    if (digits.length != 10 || !digits.startsWith('5')) {
      return 'Geçerli bir telefon numarası girin (5XXXXXXXXX).';
    }
    return null;
  }

  final _googleSignIn = GoogleSignIn(
    scopes: const ['email', 'profile'],
    serverClientId:
        '967339992980-moljr9rvqo8ojelsfcgoh8rl8v225fk0.apps.googleusercontent.com',
  );

  // final _firebaseAuth = FirebaseAuth.instance;

  FutureOr<void> _googleSocialAuth(
      GoogleSignInEvent event, Emitter<LoginModelState> emit) async {
    try {
      final googleSignInAccount = await _googleSignIn.signIn();

      if (googleSignInAccount != null) {
        final token = await googleSignInAccount.authentication;
        emit(state.copyWith(state: const GoogleStateLoading()));
        final userInfo =
            "email=${googleSignInAccount.email}&name=${googleSignInAccount.displayName}&provider=google&provider_id={${googleSignInAccount.id}}";
        debugPrint('google-user-info $userInfo');
        debugPrint('google-access-token ${token.accessToken}');
        final request = await _authRepository.socialSignInRepo(userInfo);

        request.fold(
          (failure) {
            final error = LoginStateError(failure.message, failure.statusCode);
            debugPrint('google-user-error ${failure.message}');
            emit(state.copyWith(state: error));
          },
          (user) {
            final loadedData = LoginStateLoaded(user);
            _user = user;
            debugPrint('google-user-login $user');
            emit(state.copyWith(state: loadedData));
          },
        );
      }
    } catch (e) {
      debugPrint("Error $e");
    }
  }

  FutureOr<void> _appleSocialAuth(
      AppleSignInEvent event, Emitter<LoginModelState> emit) async {
    try {
      final credential = await SignInWithApple.getAppleIDCredential(
        scopes: [
          AppleIDAuthorizationScopes.email,
          AppleIDAuthorizationScopes.fullName,
        ],
      );

      final providerId = credential.userIdentifier ?? '';
      if (providerId.isEmpty) {
        emit(state.copyWith(
          state: const LoginStateError('Apple girişi başarısız.', 400),
        ));
        return;
      }

      emit(state.copyWith(state: const AppleStateLoading()));

      final given = credential.givenName?.trim() ?? '';
      final family = credential.familyName?.trim() ?? '';
      final fullName = '$given $family'.trim();
      final email = (credential.email ?? '').trim();
      final name = fullName.isNotEmpty ? fullName : 'Apple Kullanıcı';

      final userInfo =
          "email=${Uri.encodeQueryComponent(email)}&name=${Uri.encodeQueryComponent(name)}&provider=apple&provider_id=${Uri.encodeQueryComponent(providerId)}";
      debugPrint('apple-user-info $userInfo');

      final request = await _authRepository.socialSignInRepo(userInfo);
      request.fold(
        (failure) {
          final error = LoginStateError(failure.message, failure.statusCode);
          debugPrint('apple-user-error ${failure.message}');
          emit(state.copyWith(state: error));
        },
        (user) {
          final loadedData = LoginStateLoaded(user);
          _user = user;
          debugPrint('apple-user-login $user');
          emit(state.copyWith(state: loadedData));
        },
      );
    } on SignInWithAppleAuthorizationException catch (e) {
      if (e.code == AuthorizationErrorCode.canceled) {
        return;
      }
      debugPrint('apple-auth-error ${e.code} ${e.message}');
      emit(state.copyWith(
        state: LoginStateError(e.message, 400),
      ));
    } catch (e) {
      debugPrint('apple-unknown-error $e');
      emit(state.copyWith(
        state: LoginStateError(e.toString(), 500),
      ));
    }
  }

  Future<void> _sentAccountActivateCode(
    SentAccountActivateCodeSubmit event,
    Emitter<LoginModelState> emit,
  ) async {
    emit(state.copyWith(state: const LoginStateLoading()));

    final result = await _authRepository.sendActiveAccountCode(
      state.loginIdentifierForApi(),
    );

    result.fold(
      (Failure failure) {
        final error = LoginStateError(failure.message, failure.statusCode);
        emit(state.copyWith(state: error));
      },
      (String success) {
        final loadedData = SendAccountCodeSuccess(success);
        emit(state.copyWith(state: loadedData));
      },
    );
  }

  Future<void> _accountActivateCode(
    AccountActivateCodeSubmit event,
    Emitter<LoginModelState> emit,
  ) async {
    emit(state.copyWith(state: const LoginStateLoading()));

    final result = await _authRepository.activeAccountCodeSubmit(event.code);

    result.fold(
      (Failure failure) {
        final error = LoginStateError(failure.message, failure.statusCode);
        emit(state.copyWith(state: error));
      },
      (String success) {
        final loadedData = AccountActivateSuccess(success);
        emit(state.copyWith(state: loadedData));
      },
    );
  }

  Future<String?> refreshAccessToken() async {
    final current = userInfo?.accessToken ?? '';
    if (current.isEmpty) return null;

    final result = await _authRepository.refreshAccessToken(current);
    return result.fold(
      (_) => null,
      (newToken) {
        _user = _user!.copyWith(accessToken: newToken);
        return newToken;
      },
    );
  }

  Future<void> _logOut(
    LoginEventLogout event,
    Emitter<LoginModelState> emit,
  ) async {
    emit(state.copyWith(state: const LoginStateLogOutLoading()));

    final token = userInfo?.accessToken ?? '';
    if (token.isEmpty) {
      _user = null;
      emit(state.copyWith(
        state: const LoginStateLogOut('Çıkış yapıldı', 200),
      ));
      return;
    }

    final result = await _authRepository.logOut(token);
    _user = null;

    result.fold(
      (Failure failure) {
        // Yerel oturum her durumda temizlendi.
        emit(state.copyWith(
          state: const LoginStateLogOut('Çıkış yapıldı', 200),
        ));
      },
      (String success) {
        emit(state.copyWith(state: LoginStateLogOut(success, 200)));
      },
    );
  }
}
