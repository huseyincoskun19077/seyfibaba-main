import 'package:bloc/bloc.dart';
import 'package:dartz/dartz.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';

import '/modules/profile/model/address_model.dart';
import '../../../../core/error/failure.dart';
import '../../../authentication/controller/login/login_bloc.dart';
import '../../../authentication/models/auth_error_model.dart';
import '../../model/billing_shipping_model.dart';
import '../repository/profile_repository.dart';

part 'address_state.dart';

class AddressCubit extends Cubit<AddressModel> {
  final ProfileRepository _repository;
  final LoginBloc _loginBloc;

  AddressCubit({
    required ProfileRepository repository,
    required LoginBloc loginBloc,
  })  : _repository = repository,
        _loginBloc = loginBloc,
        super(AddressModel.init());

  AddressBook? address;

  void addName(String name)=>emit(state.copyWith(name: name));
  void addEmail(String name)=>emit(state.copyWith(email: name));
  void addPhone(String name)=>emit(state.copyWith(phone: name));
  void addCountry(int name)=>emit(state.copyWith(countryId: name));
  void addState(int name)=>emit(state.copyWith(stateId: name));
  void addCity(int name)=>emit(state.copyWith(cityId: name));
  void addShippingId(int name){
    emit(state.copyWith(userId: name));
    debugPrint('store-shipping-id ${state.userId}');
  }
  void addNewAddress(String name)=>emit(state.copyWith(address: name));
  void addLatLong(double lat,double long)=>emit(state.copyWith(latitude: lat,longitude: long));



  TextEditingController addressController = TextEditingController();

  Future<void> addAddress(Map<String, String> dataMap) async {
    emit(state.copyWith(addState: const AddressStateUpdating()));

    final result =
        await _repository.addAddress(dataMap, _loginBloc.userInfo?.accessToken??'');

    result.fold(
      (failure) {
        if (failure is InvalidAuthData) {
          emit(state.copyWith(addState: AddressStateInvalidDataError(failure.errors)));
        } else {
          emit(state.copyWith(addState: AddressStateUpdateError(failure.message, failure.statusCode)));
        }
      },
      (successData) {
        emit(state.copyWith(addState: AddressStateUpdated(successData)));
      },
    );
  }

  Future<void> getAddress() async {
    emit(state.copyWith(addState: const AddressStateLoading()));

    final result =
        await _repository.getAddress(_loginBloc.userInfo?.accessToken??'');

    result.fold(
      (failure) {
        final error = AddressStateError(failure.message, failure.statusCode);
        emit(state.copyWith(addState: error));
      },
      (successData) {
        address = successData;
        emit(state.copyWith(addState: AddressStateLoaded(successData)));
      },
    );
  }

  Future<void> updateAddress(String id, Map<String, String> dataMap) async {
    emit(state.copyWith(addState: const AddressStateUpdating()));

    final result = await _repository.updateAddress(
        id, dataMap, _loginBloc.userInfo?.accessToken??'');

    result.fold(
      (failure) {
        if (failure is InvalidAuthData) {
          emit(state.copyWith(addState: AddressStateInvalidDataError(failure.errors)));
        } else {
          emit(state.copyWith(addState: AddressStateUpdateError(failure.message, failure.statusCode)));
        }
      },
      (successData) {
        emit(state.copyWith(addState: AddressStateUpdated(successData)));
      },
    );
  }

  Future<void> billingUpdate(Map<String, String> dataMap) async {
    emit(state.copyWith(addState: const AddressStateUpdating()));

    final result = await _repository.billingUpdate(
        dataMap, _loginBloc.userInfo?.accessToken??'');

    result.fold(
      (failure) {
        emit(state.copyWith(addState: AddressStateUpdateError(failure.message, failure.statusCode)));
      },
      (successData) {
        emit(state.copyWith(addState: AddressStateUpdated(successData)));
      },
    );
  }

  Future<void> shippingUpdate(Map<String, String> dataMap) async {
    emit(state.copyWith(addState: const AddressStateUpdating()));

    final result = await _repository.shippingUpdate(
        dataMap, _loginBloc.userInfo?.accessToken??'');

    result.fold(
      (failure) {
        emit(state.copyWith(addState: AddressStateUpdateError(failure.message, failure.statusCode)));
      },
      (successData) {
        emit(state.copyWith(addState: AddressStateUpdated(successData)));
      },
    );
  }

  Future<void> singleAddress(String id) async {
    emit(state.copyWith(addState: const AddressStateUpdating()));

    final result =
        await _repository.getSingleAddress(id, _loginBloc.userInfo!.accessToken);

    result.fold(
      (failure) {
        emit(state.copyWith(addState: AddressStateUpdateError(failure.message, failure.statusCode)));
      },
      (successData) {
        emit(state.copyWith(addState: BillingAndShippingAddressStateLoaded(successData)));
      },
    );
  }

  Future<Either<Failure, String>> deleteSingleAddress(String id) async {
    // emit(const AddressStateUpdating());

    final result =
        await _repository.deleteAddress(id, _loginBloc.userInfo!.accessToken);

    result.fold(
      (failure) {
        // emit(AddressStateError(failure.message, failure.statusCode));
        return false;
      },
      (successData) {
        // emit(AddressDelete(successData));
        return true;
      },
    );
    return result;
  }

  void addGuestAddress(){
    debugPrint('new-address ${state.toGuestMap()}');
  }

  void clearAddress() {
    addressController.clear();
  }

  void clearAddressInfo(){
    emit(state.copyWith(
        name : '',
        email : '',
        phone : '',
        address : '',
        type: '',
        countryId : 0,
        stateId : 0,
        cityId : 0,
        userId : 0,
        latitude : 0.0,
        longitude : 0.0,
    ));
  }
}
