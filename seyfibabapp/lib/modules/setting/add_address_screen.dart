import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../widgets/custom_text.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/translate_form_text.dart';
import '../profile/controllers/map/map_cubit.dart';
import '../profile/controllers/map/map_state_model.dart';
import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '../../utils/constants.dart';
import '../../utils/utils.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/rounded_app_bar.dart';
import '../authentication/widgets/sign_up_form.dart';
import '../profile/controllers/address/address_cubit.dart';
import '../profile/controllers/country_state_by_id/country_state_by_id_cubit.dart';
import '../profile/model/address_model.dart';
import '../profile/model/city_model.dart';
import '../profile/model/country_model.dart';
import '../profile/model/country_state_model.dart';
import 'component/map_address.dart';

class AddAddressScreen extends StatefulWidget {
  const AddAddressScreen({super.key});

  @override
  State<AddAddressScreen> createState() => _AddAddressScreenState();
}

class _AddAddressScreenState extends State<AddAddressScreen> {
  late MapCubit aCubit;
  late AddressCubit addressCubit;

  CountryModel? _countryModel;
  CountryStateModel? _countryStateModel;
  CityModel? _cityModel;
  AddressModel? addressModel;
  List<CountryModel> countries = [];
  List<CountryStateModel> stateList = [];
  List<CityModel> cityList = [];

  final nameCtr = TextEditingController();
  final emailCtr = TextEditingController();
  final phoneCtr = TextEditingController();
  final addressCtr = TextEditingController();
  final zipCtr = TextEditingController();

  @override
  void initState() {
    addressCubit = context.read<AddressCubit>();
    aCubit = context.read<MapCubit>();
    context.read<CountryStateByIdCubit>().countryListLoaded();
    super.initState();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(
        titleText: Language.addNewAddress.capitalizeByWord(),
        onTap: () {
          context.read<AddressCubit>().getAddress();
        },
      ),
      body: BlocConsumer<AddressCubit, AddressModel>(
        // listenWhen: (previous, current) => previous != current,
        listener: (context, states) {
          final state = states.addState;
          if (state is AddressStateUpdateError) {
            Utils.closeDialog(context);
            Utils.errorSnackBar(context, state.message);
            context.read<AddressCubit>().getAddress();
          } else if (state is AddressStateUpdated) {
            Navigator.of(context).pop(true);
          }
        },
        builder: (context, states) {
          final addressState = states.addState;
          return BlocBuilder<CountryStateByIdCubit, CountryStateByIdState>(
            builder: (context, state) {
              if (state is CountryStateByIdStateLoadied) {
                _countryStateModel = context
                    .read<CountryStateByIdCubit>()
                    .filterState(addressModel?.stateId.toString() ?? "");
                if (_countryStateModel != null) {
                  _cityModel = context
                      .read<CountryStateByIdCubit>()
                      .filterCity(addressModel?.cityId.toString() ?? "");
                }
              }
              return SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CustomText(
                        text: Language.addNewAddress.capitalizeByWord(),
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                        height: 1.5),
                    const SizedBox(height: 9),
                    BlocBuilder<MapCubit, MapStateModel>(
                      builder: (context, state) {
                        if (Utils.isMapEnable(context)) {
                          return TextFormField(
                            keyboardType: TextInputType.streetAddress,
                            readOnly: true,
                            onTap: () async {
                              await showDialog(
                                context: context,
                                builder: (context) => const AddressMapDialog(),
                              );
                            },
                            decoration: InputDecoration(
                              hintStyle: const TextStyle(
                                  fontSize: 16.0,
                                  fontWeight: FontWeight.w600,
                                  color: blackColor),
                              hintText: state.location.isEmpty
                                  ? Utils.formText(
                                      context, 'Choose your Location')
                                  : state.location,
                              suffixIcon: Padding(
                                padding:
                                    Utils.all(value: 4.0).copyWith(right: 10.0),
                                child: GestureDetector(
                                  onTap: () async {
                                    await showDialog(
                                      context: context,
                                      builder: (context) =>
                                          const AddressMapDialog(),
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
                        } else {
                          return const SizedBox.shrink();
                        }
                      },
                    ),
                    const SizedBox(height: 16),
                    TranslateWidget(
                      future: Utils.hintText(context, Language.name),
                      hintText: Language.name,
                      builder: (context, snap) {
                        return TextFormField(
                          controller: nameCtr,
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
                      future: Utils.hintText(context, Language.emailAddress),
                      hintText: Language.emailAddress,
                      builder: (context, snap) {
                        return TextFormField(
                          controller: emailCtr,
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
                    TranslateWidget(
                      future: Utils.hintText(context, Language.phoneNumber),
                      hintText: Language.phoneNumber,
                      builder: (context, snap) {
                        return TextFormField(
                          controller: phoneCtr,
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
                      future: Utils.hintText(context, Language.address),
                      hintText: Language.address,
                      builder: (context, snap) {
                        return TextFormField(
                          controller: addressCtr,
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
                        text: Language.addNewAddress.capitalizeByWord(),
                        onPressed: () {
                          Utils.closeKeyBoard(context);
                          final dataMap = {
                            'name': nameCtr.text.trim(),
                            'email': emailCtr.text.trim(),
                            'phone': phoneCtr.text.trim(),
                            'type': 'home',
                            'address':
                            addressCtr.text.trim(),
                            'latitude': aCubit.state.latitude.toString(),
                            'longitude': aCubit.state.longitude.toString(),
                            'country': _countryModel != null
                                ? _countryModel!.id.toString()
                                : "",
                            'state': _countryStateModel != null
                                ? _countryStateModel!.id.toString()
                                : "",
                            'city': _cityModel != null
                                ? _cityModel!.id.toString()
                                : "",
                            'zip_code': zipCtr.text.trim(),
                          };

                          if (_countryModel == null) {
                            Utils.errorSnackBar(
                                context, 'Please select country');
                          } else if (_countryStateModel == null) {
                            Utils.errorSnackBar(context, 'Please select state');
                          } else if (_cityModel == null) {
                            Utils.errorSnackBar(context, 'Please select city');
                          } else {
                            if (Utils.isMapEnable(context)) {
                              if (aCubit.state.latitude != 0.0 &&
                                  aCubit.state.longitude != 0.0) {
                                context
                                    .read<AddressCubit>()
                                    .addAddress(dataMap);
                              } else {
                                Utils.showSnackBar(
                                    context, 'Location is required');
                              }
                            } else {
                              context.read<AddressCubit>().addAddress(dataMap);
                            }
                          }
                        },
                      )
                    ],
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }

  Widget _countryField(List<CountryModel> countries) {
    final addressBl = context.read<CountryStateByIdCubit>();
    return DropdownButtonFormField<CountryModel>(
      value: _countryModel,
      hint: CustomText(text: Language.country.capitalizeByWord()),
      decoration: const InputDecoration(
        isDense: true,
        border: OutlineInputBorder(
          // borderSide: BorderSide(width: 1, color: CustomColors.lineColor),
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
      items: addressBl.countryList.isNotEmpty
          ? addressBl.countryList
              .map<DropdownMenuItem<CountryModel>>((CountryModel value) {
              return DropdownMenuItem<CountryModel>(
                  value: value,
                  child: CustomText(text: value.name, isTranslate: false));
            }).toList()
          : null,
    );
  }

  Widget stateField() {
    final addressBl = context.read<CountryStateByIdCubit>();
    return DropdownButtonFormField<CountryStateModel>(
      value: _countryStateModel,
      hint: CustomText(text: Language.state.capitalizeByWord()),
      decoration: const InputDecoration(
        isDense: true,
        border: OutlineInputBorder(
          // borderSide: BorderSide(width: 1, color: CustomColors.lineColor),
          borderRadius: BorderRadius.all(Radius.circular(5)),
        ),
      ),
      onTap: () async {
        Utils.closeKeyBoard(context);
      },
      onChanged: (value) {
        if (value == null) return;
        _countryStateModel = value;
        _loadCity(value);
        addressBl.cityStateChangeCityFilter(value);
      },
      isDense: true,
      isExpanded: true,
      items: addressBl.stateList.isNotEmpty
          ? addressBl.stateList.map<DropdownMenuItem<CountryStateModel>>(
              (CountryStateModel value) {
              return DropdownMenuItem<CountryStateModel>(
                  value: value,
                  child: CustomText(text: value.name, isTranslate: false));
            }).toList()
          : null,
    );
  }

  Widget cityField() {
    final addressBl = context.read<CountryStateByIdCubit>();
    return DropdownButtonFormField<CityModel>(
      value: _cityModel,
      hint: CustomText(text: Language.city.capitalizeByWord()),
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
      items: addressBl.cities.isNotEmpty
          ? addressBl.cities
              .map<DropdownMenuItem<CityModel>>((CityModel value) {
              return DropdownMenuItem<CityModel>(
                  value: value,
                  child: CustomText(
                    text: value.name,
                    isTranslate: false,
                  ));
            }).toList()
          : [],
    );
  }
}
