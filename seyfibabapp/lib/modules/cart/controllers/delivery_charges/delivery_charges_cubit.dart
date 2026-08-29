import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../model/shipping_response_model.dart';

part 'delivery_charges_state.dart';

class DeliveryChargesCubit extends Cubit<ShippingResponseModel> {
  DeliveryChargesCubit() : super(ShippingResponseModel.init());

  void addDeliveryCharges(double price) {
    emit(state.copyWith(initialPrice: price));
  }

  void addDistancePerKM(double distance, double price) {
    final result = distance * price;

    if (state.distancePrice > 0.0) {
      emit(state.copyWith(distancePrice: 0.0));
    }

    emit(state.copyWith(distancePrice: result));
  }

  void resetDistance() {
    if (state.distancePrice > 0.0) {
      emit(state.copyWith(distancePrice: 0.0));
    }
  }
}
