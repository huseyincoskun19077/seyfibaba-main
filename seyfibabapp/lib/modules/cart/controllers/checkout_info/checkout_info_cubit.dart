import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'checkout_info_state_model.dart';

part 'checkout_info_state.dart';

class CheckoutInfoCubit extends Cubit<CheckoutInfoStateModel> {
  CheckoutInfoCubit() : super(const CheckoutInfoStateModel());

  void switchAddress(int index) {
    emit(state.copyWith(addressId: index));
  }

  void changeBillingId(String id) {
    emit(state.copyWith(billingId: id));
  }

  void changeShippingId(String id) {
    emit(state.copyWith(shippingId: id));
  }
}
