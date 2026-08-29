import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../core/router_name.dart';
import '../../widgets/custom_text.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/translate_form_text.dart';
import '../cart/component/shiping_method_list.dart';
import '../cart/controllers/checkout/checkout_cubit.dart';
import '../cart/model/coupon_response_model.dart';
import '../product_details/controller/cubit/product_details_cubit.dart';
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

class GuestAddressScreen extends StatefulWidget {
  const GuestAddressScreen({super.key});

  @override
  State<GuestAddressScreen> createState() => _GuestAddressScreenState();
}

class _GuestAddressScreenState extends State<GuestAddressScreen> {
  late MapCubit aCubit;
  late AddressCubit addressCubit;
  late ProductDetailsCubit detailsCubit;
  late CheckoutCubit checkoutCubit;

  CountryModel? _countryModel;
  CountryStateModel? _countryStateModel;
  CityModel? _cityModel;
  AddressModel? addressModel;
  List<CountryModel> countries = [];
  List<CountryStateModel> stateList = [];
  List<CityModel> cityList = [];


  @override
  void initState() {
    addressCubit = context.read<AddressCubit>();
    detailsCubit = context.read<ProductDetailsCubit>();
    checkoutCubit = context.read<CheckoutCubit>();
    checkoutCubit..clearShipping()..shippingFee(0.0);
    // debugPrint('price-weight-qty ${detailsCubit.state.cartPrice} | ${detailsCubit.state.totalWight} | ${detailsCubit.state.totalQty}');
    aCubit = context.read<MapCubit>();
    context.read<CountryStateByIdCubit>().countryListLoaded();
    super.initState();
  }

  void _loadState(CountryModel countryModel) {
    _countryModel = countryModel;
    stateList.clear();
    cityList.clear();
    _countryStateModel = null;
    _cityModel = null;

    final stateLoadIdCountryId = context.read<CountryStateByIdCubit>().stateLoadIdCountryId;

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
        listener: (context, address) {
          final state = address.addState;
          if (state is AddressStateUpdateError) {
            Utils.closeDialog(context);
            Utils.errorSnackBar(context, state.message);
            context.read<AddressCubit>().getAddress();
          } else if (state is AddressStateUpdated) {
            Navigator.of(context).pop(true);
          }
        },
        builder: (context, address) {
          final addressState = address.addState;
          return BlocBuilder<CountryStateByIdCubit, CountryStateByIdState>(
            builder: (context, state) {
              if (state is CountryStateByIdStateLoadied) {
                _countryStateModel = context.read<CountryStateByIdCubit>().filterState(addressModel?.stateId.toString() ?? "");
                if (_countryStateModel != null) {
                  _cityModel = context.read<CountryStateByIdCubit>().filterCity(addressModel?.cityId.toString() ?? "");
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
                          addressCubit.addLatLong(state.latitude, state.longitude);
                          checkoutCubit.getDistance(state.latitude, state.longitude);
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
                        initialValue: address.name,
                          onChanged:  addressCubit.addName,
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
                          initialValue: address.email,
                          onChanged:  addressCubit.addEmail,
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
                          initialValue: address.phone,
                          onChanged:  addressCubit.addPhone,
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
                          initialValue: address.address,
                          onChanged:  addressCubit.addNewAddress,
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

                    BlocBuilder<CheckoutCubit,CouponResponseModel>(
                      builder: (context,state){
                        if(!Utils.isMapEnable(context) && state.shippings.isNotEmpty){
                          return ShippingMethodList(shippingMethods: state.shippings,onChange: (int index){
                            addressCubit.addShippingId(index);
                            },padding: Utils.all());
                        }else if(checkoutCubit.state.distancePrice != 0.0 && address.latitude != 0.0 && address.longitude != 0.0){
                          return const ShippingPerKM();
                        } else{
                          return const SizedBox.shrink();
                        }

                      },
                    ),
                    const SizedBox(height: 30.0),
                    PrimaryButton(
                      text: Language.checkout.capitalizeByWord(),
                      onPressed: () {
                        Utils.closeKeyBoard(context);

                        if(address.name.trim().isEmpty){
                          Utils.errorSnackBar(context, 'Name is required');
                        }else if(address.email.trim().isEmpty){
                          Utils.errorSnackBar(context, 'Email is required');
                        }else if(address.phone.trim().isEmpty){
                          Utils.errorSnackBar(context, 'Phone is required');
                        }else if (_countryModel == null) {
                          Utils.errorSnackBar(context, 'Please select country');
                        } else if (_countryStateModel == null) {
                          Utils.errorSnackBar(context, 'Please select state');
                        } else if (_cityModel == null) {
                          Utils.errorSnackBar(context, 'Please select city');
                        } else if(address.address.trim().isEmpty){
                          Utils.errorSnackBar(context, 'Address is required');
                        }else if(address.userId == 0 && !Utils.isMapEnable(context)){
                          Utils.errorSnackBar(context, 'Please select Shipping method');
                        }else {
                          if (Utils.isMapEnable(context)) {
                            if (aCubit.state.latitude == 0.0 && aCubit.state.longitude == 0.0) {
                              Utils.showSnackBar(context, 'Location is required');
                            }else{
                              Navigator.pushNamed(context,RouteNames.guestCheckoutScreen);
                            }
                          } else {
                            Navigator.pushNamed(context,RouteNames.guestCheckoutScreen);
                           // addressCubit.addGuestAddress();
                          }
                        }
                      },
                    ),
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

        checkoutCubit.clearShipping();
      },
      onChanged: (value) {
        if (value == null) return;
        addressCubit.addCountry(value.id);
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
        checkoutCubit.clearShipping();
      },
      onChanged: (value) {
        if (value == null) return;
        _countryStateModel = value;
        addressCubit.addState(value.id);
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
        addressCubit.addCity(value.id);
        checkoutCubit.filterShippingAddress(detailsCubit.state.cartPrice, detailsCubit.state.totalWight, detailsCubit.state.totalQty, value.id);
        debugPrint('city-id ${value.id} | ${value.name}');
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
