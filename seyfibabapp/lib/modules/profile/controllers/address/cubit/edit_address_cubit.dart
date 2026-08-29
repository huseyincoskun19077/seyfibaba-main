import 'package:bloc/bloc.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';

import '../../../../authentication/controller/login/login_bloc.dart';
import '../../../model/city_model.dart';
import '../../../model/country_model.dart';
import '../../../model/country_state_model.dart';
import '../../../model/edit_address_model.dart';
import '../../repository/profile_repository.dart';

part 'edit_address_state.dart';

class EditAddressCubit extends Cubit<EditAddressState> {
  final ProfileRepository _repository;
  final LoginBloc _loginBloc;

  EditAddressCubit({
    required ProfileRepository profileRepository,
    required LoginBloc loginBloc,
  })  : _repository = profileRepository,
        _loginBloc = loginBloc,
        super(EditAddressInitial());

  List<CountryModel> countryList = [];
  List<CountryStateModel> stateList = [];
  List<CityModel> cities = [];
  EditAddressModel? editAddress;

  final nameCtr = TextEditingController();
  final emailCtr = TextEditingController();
  final phoneCtr = TextEditingController();
  final addressCtr = TextEditingController();

  Future<void> fetchEditAddress(String id) async {
    emit(EditAddressLoading());

    final result = await _repository.editAddress(id, _loginBloc.userInfo!.accessToken);
    result.fold(
      (failure) {
        emit(EditAddressStateUpdateError(failure.message, failure.statusCode));
      },
      (successData) {
        editAddress = successData;
        if(editAddress != null){
          nameCtr.text = editAddress!.address.name;
          emailCtr.text = editAddress!.address.email;
          phoneCtr.text = editAddress!.address.phone;
          addressCtr.text = editAddress!.address.address;
        }
        countryList = successData.countries.toSet().toList();
        stateList = successData.states.toSet().toList();
        cities = successData.cities.toSet().toList();
        debugPrint('countries $countryList');
        debugPrint('state $stateList');
        debugPrint('cities $cities');
        emit(EditAddressStateLoaded(successData));
      },
    );
  }

  CountryStateModel? defaultState(int? id) {
    for (var item in stateList) {
      if (item.id == id) {
        return item;
      }
    }
    return null;
  }

  CityModel? defaultCity(int? id) {
    for (var item in cities) {
      if (item.id == id) {
        return item;
      }
    }
    return null;
  }
}
