import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../cart/model/guest_cart_product.dart';
import '../../model/product_details_model.dart';
import '../repository/product_details_repository.dart';
import 'guest_state_model.dart';

part 'guest_state.dart';

class GuestCubit extends Cubit<GuestStateModel> {

  GuestCubit(this.repository) : super(const GuestStateModel());

  final ProductDetailsRepository repository;

  ProductDetailsModel? details;
  List<GustCartProduct> savedProduct = [];



  void addQty(String type) {
    if (type == 'add') {
      emit(state.copyWith(quantity: state.quantity + 1));
      debugPrint('added-one ${state.quantity}');
    }else {
      if (state.quantity > 1) {
        emit(state.copyWith(quantity: state.quantity - 1));
        debugPrint('minus-one ${state.quantity}');
      }
    }
  }


  void increase(){
    emit(state.copyWith(quantity: state.quantity + 1));
    debugPrint('quantity ${state.quantity}');
  }
}
