import 'package:equatable/equatable.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/remote_urls.dart';
import '../../../authentication/controller/login/login_bloc.dart';
import '../payment_repository.dart';

part 'iyzico_state.dart';

class IyzicoCubit extends Cubit<IyzicoState> {
  final PaymentRepository _paymentRepository;
  final LoginBloc _loginBloc;

  IyzicoCubit({
    required PaymentRepository paymentRepository,
    required LoginBloc loginBloc,
  })  : _paymentRepository = paymentRepository,
        _loginBloc = loginBloc,
        super(const IyzicoInitialState());

  Future<void> startCheckout(Map<String, dynamic> body, {bool isGuest = false}) async {
    debugPrint('[Iyzico] startCheckout guest=$isGuest');
    emit(const IyzicoLoadingState());

    final token = _loginBloc.userInfo?.accessToken ?? '';
    if (!isGuest && token.isEmpty) {
      debugPrint('[Iyzico] startCheckout aborted: missing auth token');
      emit(const IyzicoErrorState(
        'Ödeme için giriş yapmanız gerekiyor.',
        401,
      ));
      return;
    }

    final uri = Uri.parse(RemoteUrls.payWithIyzicoUrl(isGuest ? 'guest' : 'auth'))
        .replace(queryParameters: isGuest ? {} : {'token': token});

    final result = await _paymentRepository.payWithIyzico(uri, body);

    result.fold(
      (failure) {
        debugPrint(
          '[Iyzico] startCheckout fail status=${failure.statusCode} msg=${failure.message}',
        );
        emit(IyzicoErrorState(failure.message, failure.statusCode));
      },
      (data) {
        final checkoutUrl = data['checkout_url']?.toString() ?? '';
        final orderId = data['order_id']?.toString() ?? '';
        if (checkoutUrl.isEmpty) {
          debugPrint('[Iyzico] startCheckout fail: empty checkout_url');
          emit(const IyzicoErrorState('Ödeme sayfası oluşturulamadı.', 422));
          return;
        }
        debugPrint('[Iyzico] startCheckout ok orderId=$orderId');
        emit(IyzicoLoadedState(
          checkoutUrl: checkoutUrl,
          orderId: orderId,
        ));
      },
    );
  }
}
