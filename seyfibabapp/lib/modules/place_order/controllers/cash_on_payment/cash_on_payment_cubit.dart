import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/remote_urls.dart';
import '../../../authentication/controller/login/login_bloc.dart';
import '../payment_repository.dart';

part 'cash_on_payment_state.dart';

class CashOnPaymentCubit extends Cubit<CashPaymentState> {
  final LoginBloc _loginBloc;
  final PaymentRepository _paymentRepository;

  CashOnPaymentCubit({
    required LoginBloc loginBloc,
    required PaymentRepository paymentRepository,
  })  : _loginBloc = loginBloc,
        _paymentRepository = paymentRepository,
        super(const CashPaymentStateInitial());

  Future<void> cashOnDelivery(Map<String,dynamic> body) async {
    Uri uri;
    if (_loginBloc.userInfo?.accessToken.isNotEmpty??false) {
      uri = Uri.parse(RemoteUrls.cashOnDelivery('aut')).replace(queryParameters: {'token':_loginBloc.userInfo?.accessToken??''});
    }else{
      uri = Uri.parse(RemoteUrls.cashOnDelivery('guest'));
    }
    debugPrint('cod-url $uri');
    emit(const CashPaymentStateLoading());

    final result = await _paymentRepository.cashOnDelivery(body, uri);
    result.fold(
      (failure) {
        emit(CashPaymentStateError(failure.message, failure.statusCode));
      },
      (success) {
        emit(CashPaymentStateLoaded(success));
      },
    );
  }
}
