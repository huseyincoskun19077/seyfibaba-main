import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../widgets/custom_text.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/translate_form_text.dart';
import '../profile/controllers/map/map_cubit.dart';
import '../profile/controllers/map/map_state_model.dart';
import '../profile/model/address_model.dart';
import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '../../utils/constants.dart';
import '../../utils/utils.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/rounded_app_bar.dart';
import '../authentication/widgets/sign_up_form.dart';
import '../profile/controllers/address/address_cubit.dart';
import '../profile/controllers/address/cubit/edit_address_cubit.dart';
import '../profile/controllers/country_state_by_id/country_state_by_id_cubit.dart';
import '../profile/model/city_model.dart';
import '../profile/model/country_model.dart';
import '../profile/model/country_state_model.dart';
import '../profile/model/edit_address_model.dart';
import 'component/map_address.dart';

class EditAddressScreen extends StatefulWidget {
  const EditAddressScreen({super.key, required this.map});

  final Map<String, dynamic> map;

  @override
  State<EditAddressScreen> createState() => _EditAddressScreenState();
}

class _EditAddressScreenState extends State<EditAddressScreen> {
  late EditAddressCubit addressCubit;

  @override
  void initState() {
    addressCubit = context.read<EditAddressCubit>();

    Future.microtask(() =>
        addressCubit.fetchEditAddress(widget.map['address_id'].toString()));

    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
        appBar: RoundedAppBar(titleText: Language.editAddress),
        body: BlocConsumer<AddressCubit, AddressModel>(
          listener: (context, states) {
            final state = states.addState;
            if (state is AddressStateUpdateError) {
              Utils.errorSnackBar(context, state.message);
            } else if (state is AddressStateUpdated) {
              Navigator.of(context).pop(true);
            }
          },
          builder: (context, editState) {
            return BlocConsumer<EditAddressCubit, EditAddressState>(
                listener: (context, editState) {
              if (editState is EditAddressStateUpdateError) {
                if (editState.statusCode == 503 ||
                    addressCubit.editAddress != null) {
                  addressCubit
                      .fetchEditAddress(widget.map['address_id'].toString());
                }
              }
            }, builder: (context, editState) {
              if (editState is EditAddressLoading) {
                return const Center(child: CircularProgressIndicator());
              } else if (editState is EditAddressStateUpdateError) {
                if (editState.statusCode == 503 ||
                    addressCubit.editAddress != null) {
                  return LoadedAddressView(
                      id: widget.map['address_id'].toString());
                } else {
                  return FetchErrorText(text: editState.message);
                }
              } else if (editState is EditAddressStateLoaded) {
                return LoadedAddressView(
                    id: widget.map['address_id'].toString());
              }
              if (addressCubit.editAddress != null) {
                return LoadedAddressView(
                    id: widget.map['address_id'].toString());
              } else {
                return FetchErrorText(text: Language.somethingWentWrong);
              }
            });
          },
        ));
  }

// BlocConsumer<AddressCubit, AddressState> buildBlocConsumer() {
//   return BlocConsumer<AddressCubit, AddressState>(
//     listener: (context, state) {
//       if (state is AddressStateUpdating) {
//         Utils.loadingDialog(context);
//       } else {
//         Utils.closeDialog(context);
//         if (state is AddressStateUpdateError) {
//           Utils.closeDialog(context);
//           Utils.errorSnackBar(context, state.message);
//         } else if (state is AddressStateUpdated) {
//           Utils.closeDialog(context);
//           Navigator.of(context).pop(true);
//           print('called..');
//         }
//         // else if (state is AddressStateInvalidDataError) {
//         //   context.read<AddressCubit>().getAddress();
//         // }
//       }
//     },
//     builder: (context, addressState) {
//       return BlocBuilder<EditAddressCubit, EditAddressState>(
//           builder: (context, editState) {
//         if (editState is EditAddressLoading) {
//           return const Center(child: CircularProgressIndicator());
//         } else if (editState is EditAddressStateLoaded) {
//           if (_countryModel == null) {
//             context.read<CountryStateByIdCubit>().countryList =
//                 editState.editAddressModel.countries;
//             context.read<CountryStateByIdCubit>().stateList =
//                 editState.editAddressModel.states;
//             context.read<CountryStateByIdCubit>().cities =
//                 editState.editAddressModel.cities;
//           }
//
//           return BlocBuilder<CountryStateByIdCubit, CountryStateByIdState>(
//             builder: (context, countryState) {
//               if (countryState is CountryStateByIdStateLoadied) {
//                 _countryStateModel = context
//                     .read<CountryStateByIdCubit>()
//                     .filterState(editState.editAddressModel.address.stateId
//                         .toString());
//                 if (_countryStateModel != null) {
//                   _cityModel = context
//                       .read<CountryStateByIdCubit>()
//                       .filterCity(editState.editAddressModel.address.cityId
//                           .toString());
//                 }
//               }
//
//               return SingleChildScrollView(
//                 padding: const EdgeInsets.all(20),
//                 child: Column(
//                   crossAxisAlignment: CrossAxisAlignment.start,
//                   children: [
//                     const Text(
//                       'Edit Address',
//                       style: TextStyle(
//                           fontSize: 18,
//                           fontWeight: FontWeight.w600,
//                           height: 1.5),
//                     ),
//                     const SizedBox(height: 9),
//                     TextFormField(
//                       // initialValue:
//                       //     editState.editAddressModel.address.name,
//                       controller: nameCtr,
//                       // validator: (s) {
//                       //   if (s == null || s.isEmpty)
//                       //     return '*Name Required';
//                       //   return null;
//                       // },
//                       keyboardType: TextInputType.name,
//                       decoration: InputDecoration(
//                         hintText: Language.name.capitalizeByWord(),
//                         fillColor: borderColor.withOpacity(.10),
//                       ),
//                     ),
//                     if (addressState is AddressStateInvalidDataError) ...[
//                       if (addressState.errorMsg.name.isNotEmpty)
//                         ErrorText(text: addressState.errorMsg.name.first),
//                     ],
//
//                     const SizedBox(height: 16),
//                     TextFormField(
//                       controller: emailCtr,
//                       keyboardType: TextInputType.emailAddress,
//                       decoration: InputDecoration(
//                         hintText: Language.emailAddress.capitalizeByWord(),
//                         fillColor: borderColor.withOpacity(.10),
//                       ),
//                     ),
//                     if (addressState is AddressStateInvalidDataError) ...[
//                       if (addressState.errorMsg.email.isNotEmpty)
//                         ErrorText(text: addressState.errorMsg.email.first),
//                     ],
//                     const SizedBox(height: 16),
//                     TextFormField(
//                       // initialValue:
//                       //     editState.editAddressModel.address.phone,
//                       controller: phoneCtr,
//                       // validator: (s) {
//                       //   if (s == null || s.isEmpty) {
//                       //     return '*Phone Number Required';
//                       //   }
//                       //   return null;
//                       // },
//                       keyboardType: TextInputType.phone,
//                       decoration: InputDecoration(
//                         hintText: Language.phoneNumber.capitalizeByWord(),
//                         fillColor: borderColor.withOpacity(.10),
//                       ),
//                     ),
//                     if (addressState is AddressStateInvalidDataError) ...[
//                       if (addressState.errorMsg.phone.isNotEmpty)
//                         ErrorText(text: addressState.errorMsg.phone.first),
//                     ],
//                     const SizedBox(height: 16),
//                     _countryField(countries),
//                     if (addressState is AddressStateInvalidDataError) ...[
//                       if (addressState.errorMsg.country.isNotEmpty)
//                         ErrorText(text: addressState.errorMsg.country.first),
//                     ],
//                     const SizedBox(height: 16),
//                     stateField(),
//                     if (addressState is AddressStateInvalidDataError) ...[
//                       if (addressState.errorMsg.state.isNotEmpty)
//                         ErrorText(text: addressState.errorMsg.state.first),
//                     ],
//                     const SizedBox(height: 16),
//                     cityField(),
//                     if (addressState is AddressStateInvalidDataError) ...[
//                       if (addressState.errorMsg.city.isNotEmpty)
//                         ErrorText(text: addressState.errorMsg.city.first),
//                     ],
//                     const SizedBox(height: 16),
//                     TextFormField(
//                       controller: addressCtr,
//                       // initialValue:
//                       //     editState.editAddressModel.address.address,
//                       // validator: (s) {
//                       //   if (s == null || s.isEmpty)
//                       //     return '*Address Required';
//                       //   return null;
//                       // },
//                       keyboardType: TextInputType.streetAddress,
//                       decoration: InputDecoration(
//                         fillColor: borderColor.withOpacity(.10),
//                         hintText: Language.address.capitalizeByWord(),
//                       ),
//                     ),
//                     if (addressState is AddressStateInvalidDataError) ...[
//                       if (addressState.errorMsg.address.isNotEmpty)
//                         ErrorText(text: addressState.errorMsg.address.first),
//                     ],
//
//                     const SizedBox(height: 16),
//                     // TextFormField(
//                     //   controller: zipCtr,
//                     //   validator: (s) {
//                     //     if (s == null || s.isEmpty) {
//                     //       return '* Zip Code Required';
//                     //     }
//                     //     return null;
//                     //   },
//                     //   keyboardType: TextInputType.number,
//                     //   decoration: InputDecoration(
//                     //     fillColor: borderColor.withOpacity(.10),
//                     //     hintText: 'ZipCode',
//                     //   ),
//                     // ),
//                     const SizedBox(height: 30),
//                     PrimaryButton(
//                       text: Language.updateAddress.capitalizeByWord(),
//                       onPressed: () {
//                         //if (!_formkey.currentState!.validate()) return;
//
//                         final dataMap = {
//                           'name': nameCtr.text.trim(),
//                           'email': emailCtr.text.trim(),
//                           'phone': phoneCtr.text.trim(),
//                           'country': _countryModel!.id.toString(),
//                           'state': _countryStateModel!.id.toString(),
//                           'type': 'home',
//                           'city': _cityModel!.id.toString(),
//                           // 'zip_code': zipCtr.text.trim(),
//                           'address': addressCtr.text.trim(),
//                         };
//                         // print("DataMap");
//                         // print(dataMap.toString());
//                         context.read<AddressCubit>().updateAddress(
//                             editState.editAddressModel.address.id.toString(),
//                             dataMap);
//                       },
//                     )
//                   ],
//                 ),
//               );
//             },
//           );
//         }
//         return const SizedBox();
//       });
//     },
//   );
// }
}

class LoadedAddressView extends StatefulWidget {
  const LoadedAddressView({super.key, required this.id});

  final String id;

  @override
  State<LoadedAddressView> createState() => _LoadedAddressViewState();
}

class _LoadedAddressViewState extends State<LoadedAddressView> {
  late EditAddressCubit addressCubit;
  late MapCubit aCubit;
  late CountryStateByIdCubit cSCCubit;

  CountryModel? _countryModel;
  CountryStateModel? _countryStateModel;
  CityModel? _cityModel;

  List<CountryModel> countries = [];
  List<CountryStateModel> stateList = [];
  List<CityModel> cityList = [];

  @override
  void initState() {
    addressCubit = context.read<EditAddressCubit>();
    aCubit = context.read<MapCubit>();
    cSCCubit = context.read<CountryStateByIdCubit>();

    countries = context.read<CountryStateByIdCubit>().countryList;
    context.read<CountryStateByIdCubit>().stateList = addressCubit.stateList;
    context.read<CountryStateByIdCubit>().cities = addressCubit.cities;

    _defaultValue();
    //ifUpdateAddress(addressCubit.editAddress!);
    super.initState();
  }

  _existLocation() async {
    if (addressCubit.editAddress != null) {
      await aCubit.getLocationFromLatLng();
      debugPrint('location-iss ${aCubit.state.location}');
    } else {
      debugPrint('not-location');
    }
  }

  ifUpdateAddress(EditAddressModel editAddressModel) {
    for (var element in editAddressModel.countries) {
      if (element.id == editAddressModel.address.countryId) {
        _countryModel = element;
        break;
      }
    }

    _countryStateModel = context
        .read<EditAddressCubit>()
        .defaultState(editAddressModel.address.stateId);

    if (_countryStateModel != null) {
      log(_countryStateModel.toString(), name: "_stateModel");

      _cityModel = context
          .read<EditAddressCubit>()
          .defaultCity(editAddressModel.address.cityId);
      log(_cityModel.toString(), name: "_cityModel");
    }
  }

  void _loadState(CountryModel countryModel) {
    _countryModel = countryModel;
    _countryStateModel = null;
    _cityModel = null;

    final stateLoadIdCountryId =
        context.read<CountryStateByIdCubit>().stateLoadIdCountryId;

    stateLoadIdCountryId(countryModel.id.toString());
  }

  void _loadCity(CountryStateModel countryStateModel) {
    _countryStateModel = countryStateModel;
    _cityModel = null;

    final cityLoadIdStateId =
        context.read<CountryStateByIdCubit>().cityLoadIdStateId;

    cityLoadIdStateId(countryStateModel.id.toString());
  }

  _defaultValue() {
    if (addressCubit.editAddress != null) {
      final result = addressCubit.editAddress;
      // debugPrint('edit-country-list ${addressCubit.countryList}');
      // debugPrint('edit-state-list ${addressCubit.stateList}');
      // debugPrint('edit-city-list ${addressCubit.cities}');
      // debugPrint('country-list ${cSCCubit.countryList}');
      // debugPrint('state-list ${cSCCubit.stateList}');
      // debugPrint('city-list ${cSCCubit.cities}');

      _countryModel = cSCCubit.countryList
          .where((c) => c.id == result!.address.countryId)
          .first;
      if (_countryModel != null) {
        // debugPrint('country-not-null');
        _countryStateModel = addressCubit
            .defaultState(addressCubit.editAddress!.address.stateId);
        // debugPrint('country-not-null-${_countryStateModel!.id}|${_countryStateModel!.name}');
      } else {
        _countryStateModel = null;
      }
      if (_countryStateModel != null) {
        // debugPrint('state-not-null');
        _cityModel =
            addressCubit.defaultCity(addressCubit.editAddress!.address.cityId);
        //debugPrint('state-not-null-${_cityModel!.id}|${_cityModel!.name}');
      } else {
        _cityModel = null;
      }
      // _cityModel =
      //     cSCCubit.cities.where((c) => c.id == result!.address.cityId).first;

      // debugPrint('country-list-name ${_countryModel!.name}');
      // debugPrint('state-list-name ${_countryStateModel!.name}');
      // debugPrint('city-list-name ${_cityModel!.name}');
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AddressCubit, AddressModel>(
      builder: (context, state) {
        final addressState = state.addState;
        return BlocBuilder<CountryStateByIdCubit, CountryStateByIdState>(
          builder: (context, state) {
            return ListView(
              // crossAxisAlignment: CrossAxisAlignment.start,
              padding: Utils.symmetric(),
              children: [
                CustomText(
                    text: Language.editAddress,
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    height: 1.5),
                const SizedBox(height: 9),
                BlocBuilder<MapCubit, MapStateModel>(
                  builder: (context, state) {
                    if (Utils.isMapEnable(context)){
                      _existLocation();
                      return TextFormField(
                        keyboardType: TextInputType.streetAddress,
                        readOnly: true,
                        onTap: () async {
                          await showDialog(
                            context: context,
                            builder: (context) => const AddressMapDialog(),
                          );
                        },
                        maxLines: 1,
                        decoration: InputDecoration(
                          hintStyle: const TextStyle(
                              fontSize: 16.0,
                              fontWeight: FontWeight.w600,
                              color: blackColor),
                          hintText: state.updateLocation.isEmpty
                              ? state.location
                              : state.updateLocation,
                          suffixIcon: Padding(
                            padding: Utils.all(value: 4.0).copyWith(right: 10.0),
                            child: GestureDetector(
                              onTap: () async {
                                await showDialog(
                                  context: context,
                                  builder: (context) => const AddressMapDialog(),
                                );
                              },
                              child: const CircleAvatar(
                                backgroundColor: Color(0XFFEBEBEB),
                                child: Icon(
                                  Icons.location_on,
                                  color: blackColor,
                                ),
                              ),
                            ),
                          ),
                        ),
                      );
                    }else{
                      return const SizedBox.shrink();
                    }
                  },
                ),
                const SizedBox(height: 16.0),


                TranslateWidget(
                  future: Utils.hintText(context, Language.name),
                  hintText: Language.name,
                  builder: (context, snap) {
                    return TextFormField(
                      controller: addressCubit.nameCtr,
                      keyboardType: TextInputType.name,
                      decoration: InputDecoration(hintText: snap),
                    );
                  },
                ),
                if (addressState is AddressStateInvalidDataError) ...[
                  if (addressState.errorMsg.name.isNotEmpty)
                    ErrorText(text: addressState.errorMsg.name.first),
                ],
                const SizedBox(height: 16),
                TranslateWidget(
                  future: Utils.hintText(context, Language.email),
                  hintText: Language.email,
                  builder: (context, snap) {
                    return TextFormField(
                      controller: addressCubit.emailCtr,
                      keyboardType: TextInputType.emailAddress,
                      decoration: InputDecoration(
                        hintText: snap,
                      ),
                    );
                  },
                ),

                if (addressState is AddressStateInvalidDataError) ...[
                  if (addressState.errorMsg.email.isNotEmpty)
                    ErrorText(text: addressState.errorMsg.email.first),
                ],
                const SizedBox(height: 16),
                _countryField(countries),
                if (addressState is AddressStateInvalidDataError) ...[
                  if (addressState.errorMsg.country.isNotEmpty)
                    ErrorText(text: addressState.errorMsg.country.first),
                ],
                const SizedBox(height: 16),
                stateField(),
                if (addressState is AddressStateInvalidDataError) ...[
                  if (addressState.errorMsg.state.isNotEmpty)
                    ErrorText(text: addressState.errorMsg.state.first),
                ],
                const SizedBox(height: 16),
                cityField(),
                if (addressState is AddressStateInvalidDataError) ...[
                  if (addressState.errorMsg.city.isNotEmpty)
                    ErrorText(text: addressState.errorMsg.city.first),
                ],
                const SizedBox(height: 16),

                TranslateWidget(
                  future: Utils.hintText(context, Language.phoneNumber),
                  hintText: Language.phoneNumber,
                  builder: (context, snap) {
                    return TextFormField(
                      controller: addressCubit.phoneCtr,
                      keyboardType: TextInputType.phone,
                      decoration: InputDecoration(
                        hintText: snap,
                      ),
                    );
                  },
                ),

                if (addressState is AddressStateInvalidDataError) ...[
                  if (addressState.errorMsg.phone.isNotEmpty)
                    ErrorText(text: addressState.errorMsg.phone.first),
                ],
                const SizedBox(height: 16),
                TranslateWidget(
                  future: Utils.hintText(context, Language.address),
                  hintText: Language.address,
                  builder: (context, snap) {
                    return TextFormField(
                      controller: addressCubit.addressCtr,
                      keyboardType: TextInputType.streetAddress,
                      decoration: InputDecoration(
                        hintText: snap,
                      ),
                    );
                  },
                ),

                if (addressState is AddressStateInvalidDataError) ...[
                  if (addressState.errorMsg.address.isNotEmpty)
                    ErrorText(text: addressState.errorMsg.address.first),
                ],
                const SizedBox(height: 30),
                if (addressState is AddressStateUpdating) ...[
                  const LoadingWidget()
                ] else ...[
                  PrimaryButton(
                    text: Language.updateAddress.capitalizeByWord(),
                    onPressed: () {
                      final emailValue =
                          addressCubit.emailCtr.text.trim().toLowerCase();
                      if (emailValue.isEmpty) {
                        Utils.errorSnackBar(context, 'E-posta zorunludur.');
                        return;
                      }
                      if (!emailValue.contains('@') ||
                          emailValue.endsWith('.local') ||
                          emailValue
                              .endsWith('@pending.seyfibaba.local')) {
                        Utils.errorSnackBar(
                          context,
                          'Geçerli bir e-posta adresi girin.',
                        );
                        return;
                      }
                      final dataMap = {
                        'name': addressCubit.nameCtr.text.trim(),
                        'email': emailValue,
                        'phone': addressCubit.phoneCtr.text.trim(),
                        'type': 'home',
                        'address': addressCubit.addressCtr.text.trim(),
                        'latitude': aCubit.state.latitude.toString(),
                        'longitude': aCubit.state.longitude.toString(),
                        'country': _countryModel != null
                            ? _countryModel!.id.toString()
                            : "",
                        'state': _countryStateModel != null
                            ? _countryStateModel!.id.toString()
                            : "",
                        'city':
                            _cityModel != null ? _cityModel!.id.toString() : "",
                      };
                      debugPrint(dataMap.toString());
                      context
                          .read<AddressCubit>()
                          .updateAddress(widget.id, dataMap);
                    },
                  )
                ],
              ],
            );
          },
        );
      },
    );
  }

  Widget _countryField(List<CountryModel> countries) {
    return DropdownButtonFormField<CountryModel>(
      value: _countryModel,
      hint: Text(Language.country.capitalizeByWord()),
      icon: const Icon(Icons.keyboard_arrow_down_outlined),
      decoration: const InputDecoration(
        isDense: true,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.all(Radius.circular(5)),
        ),
      ),
      onTap: () async {
        Utils.closeKeyBoard(context);
      },
      onChanged: (value) {
        if (value == null) return;
        _loadState(value);
      },
      isDense: true,
      isExpanded: true,
      items: cSCCubit.countryList.isNotEmpty
          ? cSCCubit.countryList
              .map<DropdownMenuItem<CountryModel>>((CountryModel value) {
              return DropdownMenuItem<CountryModel>(
                  value: value, child: Text(value.name));
            }).toList()
          : null,
    );
  }

  Widget stateField() {
    // final addressBl = context.read<CountryStateByIdCubit>();
    return DropdownButtonFormField<CountryStateModel>(
      value: _countryStateModel,
      hint: Text(Language.state.capitalizeByWord()),
      icon: const Icon(Icons.keyboard_arrow_down_outlined),
      decoration: const InputDecoration(
        isDense: true,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.all(Radius.circular(5)),
        ),
      ),
      onTap: () async {
        Utils.closeKeyBoard(context);
      },
      onChanged: (value) {
        if (value == null) return;
        // _cityModel = null;
        _countryStateModel = value;
        _loadCity(value);
        cSCCubit.cityStateChangeCityFilter(value);
      },
      isDense: true,
      isExpanded: true,
      items: cSCCubit.stateList.isNotEmpty
          ? cSCCubit.stateList.map<DropdownMenuItem<CountryStateModel>>(
              (CountryStateModel value) {
              return DropdownMenuItem<CountryStateModel>(
                  value: value, child: Text(value.name));
            }).toList()
          : [],
    );
  }

  Widget cityField() {
    // final addressBl = context.read<CountryStateByIdCubit>();
    return DropdownButtonFormField<CityModel>(
      value: _cityModel,
      hint: Text(Language.city.capitalizeByWord()),
      icon: const Icon(Icons.keyboard_arrow_down_outlined),
      decoration: const InputDecoration(
        isDense: true,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.all(Radius.circular(5)),
        ),
      ),
      onTap: () {
        Utils.closeKeyBoard(context);
      },
      onChanged: (value) {
        _cityModel = value;
        if (value == null) return;
      },
      isDense: true,
      isExpanded: true,
      items: cSCCubit.cities.isNotEmpty
          ? cSCCubit.cities.map<DropdownMenuItem<CityModel>>((CityModel value) {
              return DropdownMenuItem<CityModel>(
                  value: value, child: Text(value.name));
            }).toList()
          : [],
    );
  }
}
