import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../widgets/custom_text.dart';
import '../../widgets/loading_widget.dart';
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
import '../profile/component/buyer_invoice_form.dart';
import '../second_hand/widgets/turkey_address_selects.dart';
import 'component/map_address.dart';

class AddAddressScreen extends StatefulWidget {
  const AddAddressScreen({super.key, this.showInvoice = true});

  /// Fatura adresi sekmesinde true; teslimat sekmesinde false.
  final bool showInvoice;

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
  final tcCtr = TextEditingController();
  final postalCtr = TextEditingController();
  final taxNumberCtr = TextEditingController();
  final taxOfficeCtr = TextEditingController();
  final companyCtr = TextEditingController();

  String _invoiceType = 'individual';
  bool _isEInvoice = false;
  bool _isHome = true;
  String _neighborhood = '';
  String _locality = '';
  final _invoiceFormKey = GlobalKey<BuyerInvoiceFormState>();

  @override
  void initState() {
    addressCubit = context.read<AddressCubit>();
    aCubit = context.read<MapCubit>();
    context.read<CountryStateByIdCubit>().countryListLoaded();
    super.initState();
  }

  @override
  void dispose() {
    nameCtr.dispose();
    emailCtr.dispose();
    phoneCtr.dispose();
    addressCtr.dispose();
    zipCtr.dispose();
    tcCtr.dispose();
    postalCtr.dispose();
    taxNumberCtr.dispose();
    taxOfficeCtr.dispose();
    companyCtr.dispose();
    super.dispose();
  }

  void _loadState(CountryModel countryModel) {
    _countryModel = countryModel;
    _countryStateModel = null;
    _cityModel = null;
    _neighborhood = '';
    _locality = '';

    final stateLoadIdCountryId =
        context.read<CountryStateByIdCubit>().stateLoadIdCountryId;

    stateLoadIdCountryId(countryModel.id.toString());
  }

  void _loadCity(CountryStateModel countryStateModel) {
    _countryStateModel = countryStateModel;
    _cityModel = null;
    _neighborhood = '';
    _locality = '';

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
          } else if (state is AddressStateInvalidDataError) {
            final msg = state.errorMsg.message.isNotEmpty
                ? state.errorMsg.message.first
                : 'Lütfen formu kontrol edin.';
            Utils.errorSnackBar(context, msg);
            _invoiceFormKey.currentState?.applyServerErrors({
              if (state.errorMsg.message.isNotEmpty)
                'tc_identity': state.errorMsg.message.first,
            });
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
                    const SizedBox(height: 16),
                    if (widget.showInvoice) ...[
                      BuyerInvoiceForm(
                        key: _invoiceFormKey,
                        invoiceType: _invoiceType,
                        tcController: tcCtr,
                        postalController: postalCtr,
                        taxNumberController: taxNumberCtr,
                        taxOfficeController: taxOfficeCtr,
                        companyController: companyCtr,
                        isEInvoice: _isEInvoice,
                        showIntro: false,
                        embedded: true,
                        onInvoiceTypeChanged: (type) =>
                            setState(() => _invoiceType = type),
                        onEInvoiceChanged: (value) =>
                            setState(() => _isEInvoice = value),
                      ),
                      const SizedBox(height: 16),
                    ],
                    AddressPlaceTypeSelector(
                      isHome: _isHome,
                      onChanged: (isHome) => setState(() => _isHome = isHome),
                    ),
                    const SizedBox(height: 16),
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
                    TextFormField(
                      controller: nameCtr,
                      keyboardType: TextInputType.name,
                      decoration: const InputDecoration(
                        labelText: 'Ad Soyad *',
                        hintText: 'Ad Soyad',
                      ),
                    ),
                    if (addressState is AddressStateInvalidDataError) ...[
                      if (addressState.errorMsg.name.isNotEmpty)
                        ErrorText(text: addressState.errorMsg.name.first),
                    ],
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: emailCtr,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(
                        labelText: 'E-posta *',
                        hintText: 'ornek@mail.com',
                      ),
                    ),
                    if (addressState is AddressStateInvalidDataError) ...[
                      if (addressState.errorMsg.email.isNotEmpty)
                        ErrorText(text: addressState.errorMsg.email.first),
                    ],
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: phoneCtr,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(
                        labelText: 'Telefon *',
                        hintText: '5xx xxx xx xx',
                      ),
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
                    TurkeyAddressSelects(
                      onlyNeighborhood: true,
                      value: TurkeyAddressValue(
                        province: _countryStateModel?.name ?? '',
                        district: _cityModel?.name ?? '',
                        locality: _locality,
                        neighborhood: _neighborhood,
                      ),
                      onChanged: (next) {
                        setState(() {
                          _neighborhood = next.neighborhood;
                          _locality = next.locality;
                        });
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: addressCtr,
                      keyboardType: TextInputType.streetAddress,
                      maxLines: 3,
                      decoration: const InputDecoration(
                        labelText: 'Açık Adres *',
                        hintText: 'Cadde, sokak, bina no, daire',
                        alignLabelWithHint: true,
                      ),
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
                          final emailValue = emailCtr.text.trim().toLowerCase();
                          if (emailValue.isEmpty) {
                            Utils.errorSnackBar(context, 'E-posta zorunludur.');
                            return;
                          }
                          if (!emailValue.contains('@') ||
                              emailValue.endsWith('.local') ||
                              emailValue.endsWith('@pending.seyfibaba.local')) {
                            Utils.errorSnackBar(
                              context,
                              'Geçerli bir e-posta adresi girin.',
                            );
                            return;
                          }

                          String invoiceType = _invoiceType;
                          String tcIdentity = '';
                          String taxNumber = '';
                          String taxOffice = '';
                          String companyName = '';
                          bool isEInvoice = _isEInvoice;
                          String postalCode = '';

                          if (widget.showInvoice) {
                            final formState = _invoiceFormKey.currentState;
                            if (formState != null &&
                                !formState.validateAndHighlight()) {
                              final err = validateBuyerInvoice(
                                invoiceType: formState.readValues().invoiceType,
                                tcIdentity: formState.readValues().tcIdentity,
                                taxNumber: formState.readValues().taxNumber,
                                taxOffice: formState.readValues().taxOffice,
                                companyName: formState.readValues().companyName,
                                postalCode: formState.readValues().postalCode,
                              );
                              if (err != null) {
                                Utils.errorSnackBar(context, err);
                              }
                              return;
                            }

                            final inv = formState?.readValues();
                            invoiceType = inv?.invoiceType ?? _invoiceType;
                            tcIdentity = inv?.tcIdentity ??
                                BuyerInvoiceFormState.digitsOnly(
                                    tcCtr.text, 11);
                            taxNumber = inv?.taxNumber ??
                                BuyerInvoiceFormState.digitsOnly(
                                    taxNumberCtr.text, 11);
                            taxOffice =
                                inv?.taxOffice ?? taxOfficeCtr.text.trim();
                            companyName =
                                inv?.companyName ?? companyCtr.text.trim();
                            isEInvoice = inv?.isEInvoice ?? _isEInvoice;
                            postalCode = inv?.postalCode ??
                                BuyerInvoiceFormState.digitsOnly(
                                    postalCtr.text.isNotEmpty
                                        ? postalCtr.text
                                        : zipCtr.text,
                                    5);

                            _invoiceType = invoiceType;
                            _isEInvoice = isEInvoice;
                          }

                          final dataMap = <String, String>{
                            'name': nameCtr.text.trim(),
                            'email': emailValue,
                            'phone': phoneCtr.text.trim(),
                            'type': addressTypeValue(_isHome),
                            'address': addressCtr.text.trim(),
                            'neighborhood': _neighborhood.trim(),
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
                          };

                          if (widget.showInvoice) {
                            dataMap['zip_code'] = postalCode;
                            dataMap['postal_code'] = postalCode;
                            dataMap['invoice_type'] = invoiceType;
                            if (invoiceType == 'corporate') {
                              dataMap['tax_number'] = taxNumber;
                              dataMap['tax_office'] = taxOffice;
                              dataMap['company_name'] = companyName;
                              dataMap['is_e_invoice'] =
                                  isEInvoice ? '1' : '0';
                            } else {
                              dataMap['tc_identity'] = tcIdentity;
                            }
                          }

                          if (_countryModel == null) {
                            Utils.errorSnackBar(
                                context, 'Lütfen ülke seçin');
                          } else if (_countryStateModel == null) {
                            Utils.errorSnackBar(context, 'Lütfen il seçin');
                          } else if (_cityModel == null) {
                            Utils.errorSnackBar(context, 'Lütfen ilçe seçin');
                          } else {
                            if (Utils.isMapEnable(context)) {
                              if (aCubit.state.latitude != 0.0 &&
                                  aCubit.state.longitude != 0.0) {
                                context
                                    .read<AddressCubit>()
                                    .addAddress(dataMap);
                              } else {
                                Utils.showSnackBar(
                                    context, 'Konum seçimi zorunludur');
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
        _neighborhood = '';
        _locality = '';
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
        setState(() {
          _cityModel = value;
          _neighborhood = '';
          _locality = '';
        });
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
