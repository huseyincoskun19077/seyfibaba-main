import 'dart:async';

import 'package:equatable/equatable.dart';

import '../../../../state_packages_names.dart';
import '../../../order/model/delivery_man_model.dart';
import 'map_state_model.dart';
import 'package:geocoding/geocoding.dart';

part 'map_state.dart';

class MapCubit extends Cubit<MapStateModel> {
  final LoginBloc _loginBloc;
  final OrderRepository _orderRepository;

  MapCubit({
    required LoginBloc loginBloc,
    required OrderRepository orderRepository,
  })  : _loginBloc = loginBloc,
        _orderRepository = orderRepository,
        super(MapStateModel.init());

  DeliveryManModel? model;

  bool isSeen = false;

  Timer? _timer;

  void isOpen(bool val) {
    debugPrint('called-isOpened');
    emit(state.copyWith(isOpenSupport: val));
    if (val) {
      _startPeriodicRefresh();
    } else {
      _stopPeriodicRefresh();
    }
  }

  void _startPeriodicRefresh() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 10), (timer) {
      if (state.isOpenSupport) {
        emit(state.copyWith(status: const RefreshStateEveryFive()));
      } else {
        _timer?.cancel();
      }
    });
  }

  void _stopPeriodicRefresh() {
    _timer?.cancel();
  }

  void deliveryIdStore(String id) => emit(state.copyWith(deliveryId: id));

  void addLatLong(double lat,double long){
    emit(state.copyWith(latitude: lat,longitude: long));
  }

  // void addLatitude(double la) {
  //   debugPrint('lat-stored $la');
  //   emit(state.copyWith(latitude: la));
  //   debugPrint('lat-stored-in-state ${state.latitude}');
  // }
  //
  // void addLongitude(double la) {
  //   debugPrint('lon-stored $la');
  //   emit(state.copyWith(longitude: la));
  //   debugPrint('lon-stored-in-state ${state.latitude}');
  // }

  void addDLatitude(double la) {
    emit(state.copyWith(dLatitude: la));
    debugPrint('de-lat-store-state ${state.dLatitude}');
  }

  void addDLongitude(double la) {
    emit(state.copyWith(dLongitude: la));
    debugPrint('de-long-store-state ${state.dLongitude}');
  }

  void addLocation(String la) => emit(state.copyWith(location: la));

  void updateLocation(String la) => emit(state.copyWith(updateLocation: la));

  Future<void> getLocationFromLatLng() async {
    try {
      List<Placemark> placeMarks = await placemarkFromCoordinates(state.latitude, state.longitude);

      if (placeMarks.isNotEmpty) {
        Placemark place = placeMarks.first;
        String address = '${place.name}, ${place.locality}, ${place.country}';
        // String address ='${place.locality}, ${place.subAdministrativeArea}, ${place.administrativeArea}, ${place.country}';
        // debugPrint('name: ${place.name}');
        // debugPrint('locality: ${place.locality}');
        // debugPrint('subLocality: ${place.subLocality}');
        // debugPrint('administrativeArea: ${place.administrativeArea}');
        // debugPrint('subAdministrativeArea: ${place.subAdministrativeArea}');
        // debugPrint('country: ${place.country}');
        // debugPrint('subThoroughfare: ${place.subThoroughfare}');
        // debugPrint('postalCode: ${place.postalCode}');
        emit(state.copyWith(location: address));
        // return address;
      } else {
        // return 'No address found';
      }
    } catch (e) {
      debugPrint('Error: $e');
      // throw Exception(e.toString());
    }
  }

  void clear(){
    debugPrint('called');
    emit(state.copyWith(
      location: '',
      latitude: 0.0,
      longitude: 0.0,
      updateLocation: '',
      dLatitude: 0.0,
      dLongitude: 0.0,
    ));
  }

  void clearLocation() => emit(state.copyWith(
        updateLocation: '',
        latitude: 0.0,
        longitude: 0.0,
        dLatitude: 0.0,
        dLongitude: 0.0,
      ));

  Future<void> deliveryLocation() async {
    debugPrint('called-after-10-sec');
    if (_loginBloc.userInfo != null &&
        _loginBloc.userInfo!.accessToken.isNotEmpty) {
      emit(state.copyWith(status: const DeliveryLoading()));
      final result = await _orderRepository.deliveryLocation(state.deliveryId, _loginBloc.userInfo!.accessToken);
      result.fold((failure) {
        final errors = DeliveryError(failure.message, failure.statusCode);
        emit(state.copyWith(status: errors));
      }, (data) {
        model = data;
        final loaded = DeliveryLoaded(data);
        emit(state.copyWith(
          latitude: data.uLatitude,
          longitude: data.uLatitude,
          dLatitude: data.dLatitude,
          dLongitude: data.dLongitude,
        ));
        debugPrint('new-lat-log-assign ${state.dLatitude} | ${state.dLongitude}');
        emit(state.copyWith(status: loaded));
      });
    }
  }
}
