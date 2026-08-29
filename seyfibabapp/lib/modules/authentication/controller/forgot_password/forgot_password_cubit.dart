import 'package:flutter/cupertino.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:equatable/equatable.dart';
import 'package:shop_o/core/error/failure.dart';
import 'package:shop_o/modules/authentication/models/auth_error_model.dart';

import '../../models/set_password_model.dart';
import '../../repository/auth_repository.dart';

part 'forgot_password_state.dart';

class ForgotPasswordCubit extends Cubit<ForgotPasswordState> {
  final AuthRepository repository;

  final setpassformKey = GlobalKey<FormState>();
  final emailformKey = GlobalKey<FormState>();
  final emailController = TextEditingController();
  final phoneController = TextEditingController();
  final paswordController = TextEditingController();
  final paswordConfirmController = TextEditingController();
  final codeController = TextEditingController();

  ForgotPasswordCubit(this.repository)
      : super(const ForgotPasswordStateInitial());

  String get fullPhone {
    final digits = phoneController.text.replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.isEmpty) return '';
    if (digits.startsWith('90') && digits.length == 12) return '+$digits';
    if (digits.length == 10 && digits.startsWith('5')) return '+90$digits';
    return '+90$digits';
  }

  Future<void> forgotPassWord() async {
    final phone = fullPhone;
    if (phone.length < 12) {
      emit(const ForgotPasswordStateError(
          'Lütfen geçerli bir telefon numarası girin.'));
      return;
    }

    emit(const ForgotPasswordStateLoading());

    final result = await repository.sendOtp({
      'phone': phone,
      'purpose': 'password_reset',
    });
    result.fold(
      (failure) {
        if (failure is InvalidAuthData) {
          emit(ForgotPasswordFormValidateError(failure.errors));
        } else {
          emit(ForgotPasswordStateError(
            failure is Failure ? failure.message : failure.toString(),
          ));
        }
      },
      (data) {
        emit(ForgotPasswordStateLoaded(data));
      },
    );
  }

  Future<void> resendOtp() async {
    await forgotPassWord();
  }

  Future<void> setNewPassword() async {
    final phone = fullPhone;
    final otp = codeController.text.trim();
    if (otp.length != 6) {
      emit(const ForgotPasswordStateError('Lütfen 6 haneli doğrulama kodunu girin.'));
      return;
    }

    emit(const ForgotPasswordStateLoading());

    final verifyResult = await repository.verifyOtp({
      'phone': phone,
      'otp_code': otp,
      'purpose': 'password_reset',
    });

    await verifyResult.fold(
      (failure) async {
        emit(ForgotPasswordStateError(
          failure is Failure ? failure.message : failure.toString(),
        ));
      },
      (token) async {
        final model = SetPasswordModel(
          code: token,
          phone: phone,
          password: paswordController.text,
          passwordConfirmation: paswordConfirmController.text,
        );
        final result = await repository.setPassword(model);
        result.fold(
          (failure) {
            if (failure is InvalidAuthData) {
              emit(ForgotPasswordFormValidateError(failure.errors));
            } else {
              emit(ForgotPasswordStateError(
                failure is Failure ? failure.message : failure.toString(),
              ));
            }
          },
          (data) {
            emit(PasswordSetStateLoaded(data));
          },
        );
      },
    );
  }
}
