import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/remote_urls.dart';
import '../../../authentication/controller/login/login_bloc.dart';
import '../../../authentication/models/checkout_body_model.dart';
import '../../../../utils/language_string.dart';
import '../../model/checkout_response_model.dart';
import '../../model/coupon_response_model.dart';
import '../../model/shipping_response_model.dart';
import '../cart_repository.dart';
import 'dart:math';

part 'checkout_state.dart';

class CheckoutCubit extends Cubit<CouponResponseModel> {
  final CartRepository _cartRepository;

  final LoginBloc _loginBloc;
  CheckoutCubit({
    required LoginBloc loginBloc,
    required CartRepository cartRepository,
  })  : _cartRepository = cartRepository,
        _loginBloc = loginBloc,
        super( CouponResponseModel.init());

  CheckoutResponseModel? checkoutResponseModel;
  CheckoutResponseModel? guestInfo;

  void addCheckoutBody(CheckoutBodyModel body){
    emit(state.copyWith(checkoutBody: body));
  }

  void addSerial(String name)=>emit(state.copyWith(createdAt: name));
  void addIndex(int name)=>emit(state.copyWith(currentIndex: name));
  void addShippingId(int name)=>emit(state.copyWith(shippingId: name));

  Future<void> getCheckOutData(String couponCode) async {
    if (_loginBloc.userInfo == null) {
      final error = CheckoutStateError(Language.loginRequiredForCheckout, 401);
      emit(state.copyWith(checkState: error) );
      return;
    }

    emit(state.copyWith(checkState: const CheckoutStateLoading()));
    final result = await _cartRepository.getCheckoutData(
        _loginBloc.userInfo!.accessToken, couponCode);

    result.fold(
      (failure) {
        final error = CheckoutStateError(failure.message, failure.statusCode);
        emit(state.copyWith(checkState: error) );

      },
      (successData) {
        checkoutResponseModel = successData;
        guestInfo = successData;
        final loaded = CheckoutStateLoaded(successData);
        emit(state.copyWith(checkState: loaded));
      },
    );
  }

  Future<void> loadCheckoutContext({
    String? vendorId,
    String coupon = '',
  }) async {
    if (_loginBloc.userInfo != null) {
      await getCheckOutData(coupon);
      return;
    }

    await guestCheckoutData(vendorId ?? '');
  }

  Future<void> guestCheckoutData(String id) async {
    // emit(const CheckoutStateLoading());
    final result = await _cartRepository.guestCheckout(id);

    result.fold((failure) {
      final error = CheckoutStateError(failure.message, failure.statusCode);
      emit(state.copyWith(checkState: error) );
      },(success) {
      guestInfo = success;
      checkoutResponseModel = success;
      final loaded = CheckoutStateLoaded(success);
      emit(state.copyWith(checkState: loaded));
      },
    );
  }

  void shippingFee(double fee){
    emit(state.copyWith(shippingFee: fee));
   // debugPrint('shippingFee-added ${state.shippingFee}');
  }

  void addCheckoutShipping(double newShippingFee) {
    debugPrint('added-shipping $newShippingFee');
    final previousShippingFee = state.shippingFee;
    final newTotalPrice = (state.totalCheckoutPrice - previousShippingFee) + newShippingFee;

    // debugPrint('Previous Shipping Fee: $previousShippingFee');
    // debugPrint('New Shipping Fee: $newShippingFee');
    // debugPrint('Updated totalCheckoutPrice: $newTotalPrice');

    // emit(state.copyWith(totalCheckoutPrice: newTotalPrice));
    emit(state.copyWith(totalCheckoutPrice: newTotalPrice, shippingFee: newShippingFee));
  }




  void addCheckoutPrice(double fee){
    emit(state.copyWith(totalCheckoutPrice: fee));
   // debugPrint('price-addCheckoutPrice ${state.totalCheckoutPrice}');
  }

/*  void filterShippingAddress(double totalPrice, double totalWeight, int totalQty, int cityId) {
    // Clear previous shippings if any
    if (state.shippings.isNotEmpty) {
      emit(state.copyWith(shippings: <ShippingResponseModel>[]));
    }

    // Check if guest info contains shipping rules
    if (guestInfo?.shippings?.isNotEmpty ?? false) {
      final filteredShipping = guestInfo?.shippings?.where((rule) {
        // Ensure the rule matches the city ID
        if (rule.cityId != cityId) return false;

        // Filter based on rule type
        if (rule.type == "base_on_price") {
          return totalPrice >= rule.conditionFrom && (rule.conditionTo == -1 || totalPrice <= rule.conditionTo);
        } else if (rule.type == "base_on_weight") {
          return totalWeight >= rule.conditionFrom && (rule.conditionTo == -1 || totalWeight <= rule.conditionTo);
        } else if (rule.type == "base_on_qty") {
          return totalQty >= rule.conditionFrom && (rule.conditionTo == -1 || totalQty <= rule.conditionTo);
        }
        return false;
      }).toList();

      emit(state.copyWith(shippings: filteredShipping));
      final names = state.shippings.map((e)=>e.shippingRule).toList();
      debugPrint('converted-shipping $names');
    }
  }*/

  void filterShippingAddress(double totalPrice, double totalWeight, int totalQty, int cityId, [double? distanceInKm, double? perKmPriceRange]) {
    // Clear previous shipping rules if any
    // debugPrint('called ${checkoutResponseModel?.shippings}');
    if (state.shippings.isNotEmpty) {
      emit(state.copyWith(shippings: <ShippingResponseModel>[]));
    }

    // Check if checkout data contains shipping rules
    final allShippings =
        guestInfo?.shippings ?? checkoutResponseModel?.shippings;
    if (allShippings?.isNotEmpty ?? false) {
      final citySpecificRules =
          allShippings!.where((rule) => rule.cityId == cityId).toList();
      final defaultRules =
          allShippings.where((rule) => rule.cityId == 0).toList();

      List<ShippingResponseModel> filteredShipping = [];

      // Add city-specific rules if they exist
      if (citySpecificRules.isNotEmpty) {
        filteredShipping.addAll(citySpecificRules.where((rule) {
          // Apply filtering logic based on the rule type
          if (rule.type == "base_on_price") {
            return totalPrice >= rule.conditionFrom && (rule.conditionTo == -1 || totalPrice <= rule.conditionTo);
          } else if (rule.type == "base_on_weight") {
            return totalWeight >= rule.conditionFrom && (rule.conditionTo == -1 || totalWeight <= rule.conditionTo);
          } else if (rule.type == "base_on_qty") {
            return totalQty >= rule.conditionFrom && (rule.conditionTo == -1 || totalQty <= rule.conditionTo);
          }
          return false;
        }));
      }

      // Add default rules if no specific rules exist or as fallback
      if (filteredShipping.isEmpty) {
        filteredShipping.addAll(defaultRules);
      }

      // Handle distance-based shipping calculation
      if (distanceInKm != null && perKmPriceRange != null) {
        final calculatedPrice = (distanceInKm * perKmPriceRange).toStringAsFixed(2);
        // debugPrint('Distance-based price: $calculatedPrice');
        // You can add this calculated price as a separate rule or integrate it with existing logic
      }

      // Update the state with the filtered shipping rules
      emit(state.copyWith(shippings: filteredShipping));
      final names = state.shippings.map((e) => e.shippingRule).toList();
      // debugPrint('Filtered shipping rules: $names');
    }
  }

  void clearShipping(){
    if (state.shippings.isNotEmpty) {
      emit(state.copyWith(shippings: <ShippingResponseModel>[]));
    }
  }

  Future<void> draftCheckout(Map<String,dynamic>? body) async {

    Uri uri;

    if(_loginBloc.userInfo?.accessToken.isNotEmpty??false){
      uri = Uri.parse(RemoteUrls.draftCheckout('auth')).replace(queryParameters: {'token':_loginBloc.userInfo?.accessToken??''});
    }else{
      uri = Uri.parse(RemoteUrls.draftCheckout('guest'));
    }
    final mapBody = {...body??{},...state.checkoutBody?.toMap()??{}};
    // debugPrint('draft-order $uri');

    // log('draft-body $mapBody');

    emit(state.copyWith(checkState: const CheckoutStateLoading()));
    //??const CheckoutBodyModel(shippingMethod: '',billingAddress: '',shippingAddress: '')
    final result = await _cartRepository.draftCheckout(uri,mapBody);

    result.fold(
          (failure) {
        final error = CheckoutDraftError(failure.message, failure.statusCode);
        emit(state.copyWith(checkState: error) );

      },(success) {
        final loaded = CheckoutDraftLoaded(success);
        emit(state.copyWith(checkState: loaded));
      },
    );
  }

  void distancePrice(){
    emit(state.copyWith(distancePrice: 0.0));
  }



  void getDistance(double lat, double long) {
    if (guestInfo?.vendorLocation == null) {
      return;
    }

    final double lat1 = guestInfo?.vendorLocation?.latitude??0.0;
    final double lon1 = guestInfo?.vendorLocation?.longitude??0.0;
    final double lat2 = lat;
    final double lon2 = long;

    debugPrint('seller-lat-long $lat1 - $lon1');
    debugPrint('my-lat-long $lat2 - $lon2');

    const double earthRadius = 6371.0;

    double toRadians(double degree) {
      return degree * (pi / 180.0);
    }

    double dLat = toRadians(lat2 - lat1);
    double dLon = toRadians(lon2 - lon1);

    double a = sin(dLat / 2) * sin(dLat / 2) +
        cos(toRadians(lat1)) * cos(toRadians(lat2)) *
            sin(dLon / 2) * sin(dLon / 2);

    double c = 2 * atan2(sqrt(a), sqrt(1 - a));

    double distance = earthRadius * c;
    double totalPrice = distance * (guestInfo?.vendorLocation?.pricePerKM??0.0);
    debugPrint('total-distance $distance');
    debugPrint('total-price $totalPrice');

    // return distance;
    emit(state.copyWith(distancePrice: totalPrice));
  }

}
