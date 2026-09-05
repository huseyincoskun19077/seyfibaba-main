import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/error/failure.dart';
import '../../../../core/remote_urls.dart';
import '../../../authentication/controller/login/login_bloc.dart';
import '../../../authentication/models/auth_error_model.dart';
import '../payment_repository.dart';

part 'bank_state.dart';

class BankCubit extends Cubit<BankState> {
  final PaymentRepository _paymentRepository;

  final LoginBloc _loginBloc;

  BankCubit({
    required LoginBloc loginBloc,
    required PaymentRepository paymentRepository,
  })  : _paymentRepository = paymentRepository,
        _loginBloc = loginBloc,
        super(const BankInitialState());

  Future<void> makeBankPayment(Map<String, dynamic> body) async {

    Uri uri;

    if(_loginBloc.userInfo?.accessToken.isNotEmpty??false){
      uri = Uri.parse(RemoteUrls.payWithBankUrl('auth')).replace(queryParameters: {'token':_loginBloc.userInfo?.accessToken??''});
    }else{
      uri = Uri.parse(RemoteUrls.payWithBankUrl('guest'));
    }
    // debugPrint('bank-url $uri');
    emit(const BankStateLoading());

    final result = await _paymentRepository.bankPay(uri, body);

    result.fold((failure) {
      if (failure is InvalidAuthData) {
        emit(BankPaymentFormError(failure.errors));
      } else {
        emit(BankStateError(failure.message, failure.statusCode));
      }
    }, (data) {
      emit(BankLoadedState(
        data['message'] ?? 'Sipariş başarıyla gönderildi.',
        orderId: data['order_id'] ?? '',
      ));
    });
  }
}
