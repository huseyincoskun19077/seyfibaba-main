import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/error/failure.dart';
import '../../models/auth_error_model.dart';
import '../../models/user_login_response_model.dart';
import '../../repository/auth_repository.dart';

part 'sign_up_event.dart';
part 'sign_up_state.dart';

class SignUpBloc extends Bloc<SignUpEvent, SignUpModelState> {
  final AuthRepository repository;

  //final formKey = GlobalKey<FormState>();
  SignUpBloc(this.repository) : super(const SignUpModelState()) {
    on<SignUpEventName>((event, emit) {
      emit(state.copyWith(name: event.name, state: const SignUpStateInitial()));
    });
    on<SignUpEventEmail>((event, emit) {
      emit(state.copyWith(
          email: event.email, state: const SignUpStateInitial()));
    });
    on<SignUpEventPhone>((event, emit) {
      emit(state.copyWith(
          phone: event.phone, state: const SignUpStateInitial()));
    });
    on<SignUpEventCountryCode>((event, emit) {
      emit(state.copyWith(
          countryCode: event.code, state: const SignUpStateInitial()));
    });
    on<SignUpEventPassword>((event, emit) {
      emit(state.copyWith(
          password: event.password, state: const SignUpStateInitial()));
    });
    on<SignUpEventPasswordConfirm>((event, emit) {
      emit(state.copyWith(
          passwordConfirmation: event.passwordConfirm,
          state: const SignUpStateInitial()));
    });
    on<SignUpEventAgree>((event, emit) {
      emit(state.copyWith(
          agree: event.agree, state: const SignUpStateInitial()));
    });

    on<SignUpEventShowPassword>((event, emit) {
      emit(state.copyWith(
          showPassword: !(event.value), state: const SignUpStateInitial()));
    });

    on<SignUpEventShowConPassword>((event, emit) {
      emit(state.copyWith(
          showConPassword: !(event.value), state: const SignUpStateInitial()));
    });

    on<SignUpEventActive>((event, emit) {
      emit(state.copyWith(
          active: !(event.value), state: const SignUpStateInitial()));
    });
    on<SignUpEventSubmit>(_submitForm);
    on<SignUpEventVerifyOtp>(_verifyOtpAndRegister);
    on<SignUpEventResendOtp>(_resendOtp);
  }

  String _errorMessage(dynamic failure) {
    if (failure is InvalidAuthData) {
      final errors = failure.errors;
      for (final list in [
        errors.phone,
        errors.email,
        errors.password,
        errors.message,
        errors.name,
        errors.agree,
      ]) {
        if (list.isNotEmpty) return list.first;
      }
      return 'Kayıt bilgileri geçersiz.';
    }
    if (failure is Failure) {
      return failure.message.isNotEmpty ? failure.message : 'İşlem başarısız.';
    }
    return 'İşlem başarısız.';
  }

  void _emitFailure(dynamic failure, Emitter<SignUpModelState> emit) {
    if (failure is InvalidAuthData) {
      emit(state.copyWith(state: SignUpStateFormValidationError(failure.errors)));
      return;
    }
    emit(state.copyWith(
      state: SignUpStateFormError(_errorMessage(failure), failure is Failure ? failure.statusCode : 400),
    ));
  }

  void _submitForm(
      SignUpEventSubmit event, Emitter<SignUpModelState> emit) async {
    debugPrint('signup-body ${state.toMap()}');
    if (state.agree == 0) {
      const stateError =
          SignUpStateFormError('Please agree terms & condition', 404);
      emit(state.copyWith(state: const SignUpStateInitial()));
      emit(state.copyWith(state: stateError));
      return;
    }

    emit(state.copyWith(state: const SignUpStateLoading()));

    if (state.fullPhone.isNotEmpty) {
      final result = await repository.sendOtp({
        'phone': state.fullPhone,
        'email': state.email.trim(),
        'purpose': 'register',
        'password': state.password,
        'password_confirmation': state.passwordConfirmation,
      });
      result.fold(
        (failure) {
          emit(state.copyWith(
            awaitingOtp: false,
            state: SignUpStateFormError(
              _errorMessage(failure),
              failure is Failure ? failure.statusCode : 400,
            ),
          ));
        },
        (message) {
          emit(state.copyWith(
            awaitingOtp: true,
            state: SignUpStateLoaded(message),
          ));
        },
      );
      return;
    }

    final result = await repository.signUp(state.toMap());
    result.fold(
      (failure) {
        _emitFailure(failure, emit);
      },
      (user) {
        emit(state.copyWith(
          awaitingOtp: false,
          state: SignUpStateLoaded(user),
        ));
      },
    );
  }

  Future<void> _verifyOtpAndRegister(
    SignUpEventVerifyOtp event,
    Emitter<SignUpModelState> emit,
  ) async {
    if (state.state is SignUpStateLoading) return;

    emit(state.copyWith(state: const SignUpStateLoading()));

    final verifyResult = await repository.verifyOtp({
      'phone': state.fullPhone,
      'otp_code': event.code,
      'purpose': 'register',
    });

    final token = verifyResult.fold<String?>((failure) {
      _emitFailure(failure, emit);
      return null;
    }, (value) => value);

    if (token == null) return;

    final registerResult = await repository.signUp(state.toMap(otpVerifiedToken: token));
    final registered = registerResult.fold<bool>((failure) {
      _emitFailure(failure, emit);
      return false;
    }, (_) => true);

    if (!registered) return;

    final loginId = state.fullPhone.isNotEmpty ? state.fullPhone : state.email.trim();
    final loginResult = await repository.login({
      'email': loginId,
      'password': state.password,
    });

    loginResult.fold(
      (failure) {
        emit(state.copyWith(
          awaitingOtp: false,
          state: SignUpStateFormError(
            'Kayıt başarılı. Lütfen giriş yapın.',
            failure is Failure ? failure.statusCode : 200,
          ),
        ));
      },
      (user) {
        emit(state.copyWith(
          awaitingOtp: false,
          state: SignUpStateLoggedIn(user),
        ));
      },
    );
  }

  Future<void> _resendOtp(
    SignUpEventResendOtp event,
    Emitter<SignUpModelState> emit,
  ) async {
    if (state.fullPhone.isEmpty) return;
    emit(state.copyWith(state: const SignUpStateLoading()));

    final result = await repository.sendOtp({
      'phone': state.fullPhone,
      'email': state.email.trim(),
      'purpose': 'register',
      'password': state.password,
      'password_confirmation': state.passwordConfirmation,
    });

    result.fold(
      (failure) => _emitFailure(failure, emit),
      (message) {
        emit(state.copyWith(
          awaitingOtp: true,
          state: SignUpStateLoaded(message),
        ));
      },
    );
  }
}
