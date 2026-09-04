import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '/widgets/custom_text.dart';
import '/widgets/fetch_error_text.dart';
import '/widgets/loading_widget.dart';

import '/modules/animated_splash_screen/controller/currency/currency_cubit.dart';
import '/modules/cart/controllers/delivery_charges/delivery_charges_cubit.dart';
import '/modules/profile/controllers/address/address_cubit.dart';
import '/widgets/capitalized_word.dart';
import '../../core/router_name.dart';
import '../../dummy_data/all_dummy_data.dart';
import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/page_refresh.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/rounded_app_bar.dart';
import '../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../profile/component/buyer_invoice_form.dart';
import '../profile/controllers/updated_info/updated_info_cubit.dart';
import '../profile/model/address_model.dart';
import 'component/address_card_component.dart';
import 'component/checkout_legal_consent.dart';
import 'component/checkout_single_item.dart';
import 'component/shiping_method_list.dart';
import 'controllers/cart/cart_cubit.dart';
import 'controllers/checkout/checkout_cubit.dart';
import 'model/cart_calculation_model.dart';
import 'model/checkout_response_model.dart';
import 'model/coupon_response_model.dart';
import 'model/shipping_response_model.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  CartCalculation? cartCalculation;
  late CheckoutCubit checkCubit;

  @override
  void initState() {
    super.initState();
    checkCubit = context.read<CheckoutCubit>();
    Future.microtask(() {
      if (context.read<CartCubit>().couponResponseModel != null) {
        checkCubit.getCheckOutData(context.read<CartCubit>().couponResponseModel!.code);
      } else {
        checkCubit.getCheckOutData("");
      }
      context.read<AddressCubit>().getAddress();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(titleText: Language.checkout.capitalizeByWord()),
      body: PageRefresh(
        onRefresh: () async {
          if (context.read<CartCubit>().couponResponseModel != null) {
            context.read<CheckoutCubit>().getCheckOutData(
                context.read<CartCubit>().couponResponseModel!.code);
          } else {
            context.read<CheckoutCubit>().getCheckOutData("");
          }
          context.read<AddressCubit>().getAddress();
        },
        child: BlocConsumer<CheckoutCubit, CouponResponseModel>(
          listener: (_, states) {
            final state = states.checkState;
            if (state is CheckoutStateError) {
              if (state.statusCode == 503 ||
                  checkCubit.checkoutResponseModel == null) {
                if (context.read<CartCubit>().couponResponseModel != null) {
                  checkCubit.getCheckOutData(
                      context.read<CartCubit>().couponResponseModel!.code);
                } else {
                  checkCubit.getCheckOutData("");
                }
              }
            }
          },
          builder: (context, states) {
            final state = states.checkState;
            if (state is CheckoutStateLoading) {
              return const LoadingWidget();
            } else if (state is CheckoutStateError) {
              if (state.statusCode == 503 ||
                  checkCubit.checkoutResponseModel != null) {
                return const _LoadedWidget();
              } else {
                return FetchErrorText(text: state.message);
              }
            } else if (state is CheckoutStateLoaded) {
              return const _LoadedWidget();
            }
            if (checkCubit.checkoutResponseModel != null) {
              return const _LoadedWidget();
            } else {
              return FetchErrorText(text: Language.somethingWentWrong);
            }
          },
        ),
      ),
    );
  }
}

class _LoadedWidget extends StatefulWidget {
  const _LoadedWidget();

  @override
  State<_LoadedWidget> createState() => _LoadedWidgetState();
}

class _LoadedWidgetState extends State<_LoadedWidget> {
  late CheckoutCubit checkCubit;
  late DeliveryChargesCubit deliveryCubit;
  late CurrencyCubit cCubit;

  late CheckoutResponseModel? response;
  CartCalculation? cartCalculation;
  PageController pageController =
      PageController(initialPage: 0, keepPage: true, viewportFraction: 1);
  final body = <String, dynamic>{};
  final shippingMethodList = <ShippingResponseModel>[];

  String addressTypeSelect = addressType[0];

  int shippingMethod = 0;
  final Map<String, bool> _legalConsents = {};
  int billingAddressId = 0;
  int shippingAddressId = 0;
  double previousPrice = 0.0;
  double selectedShippingFee = 0.0;

  String _invoiceType = 'individual';
  String _tcIdentity = '';
  String _taxNumber = '';
  String _taxOffice = '';
  String _companyName = '';
  bool _isEInvoice = false;
  String _postalCode = '';
  bool _invoiceInitialized = false;

  static const basedOnWeight = 'base_on_weight';
  static const basedOnPrice = 'base_on_price';
  static const basedOnQty = 'base_on_qty';

  bool get _isBillingTab => addressTypeSelect == addressType[0];
  bool _defaultAddressesScheduled = false;

  void _ensureDefaultAddresses(List<AddressModel> addresses) {
    if (addresses.isEmpty) return;
    if (billingAddressId > 0 && shippingAddressId > 0) return;
    if (_defaultAddressesScheduled) return;
    _defaultAddressesScheduled = true;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _defaultAddressesScheduled = false;
      if (!mounted || addresses.isEmpty) return;
      setState(() {
        final first = addresses.first;
        if (billingAddressId < 1) billingAddressId = first.id;
        if (shippingAddressId < 1) {
          shippingAddressId = first.id;
          if (!Utils.isMapEnable(context)) {
            _populateShippingMethods(first);
          } else {
            deliveryCubit.addDistancePerKM(first.distance, first.priceRange);
          }
        }
      });
    });
  }

  void _selectAddress(AddressModel address) {
    setState(() {
      if (_isBillingTab) {
        billingAddressId = address.id;
        if (shippingAddressId < 1) {
          shippingAddressId = address.id;
          if (!Utils.isMapEnable(context)) {
            _populateShippingMethods(address);
          }
        }
      } else {
        shippingAddressId = address.id;
        if (billingAddressId < 1) billingAddressId = address.id;
        if (Utils.isMapEnable(context)) {
          deliveryCubit.addDistancePerKM(address.distance, address.priceRange);
        } else {
          _populateShippingMethods(address);
        }
      }
    });
  }

  @override
  void initState() {
    super.initState();
    deliveryCubit = context.read<DeliveryChargesCubit>();
    cCubit = context.read<CurrencyCubit>();
    deliveryCubit.resetDistance();
    load();
    if (context.read<CartCubit>().couponResponseModel != null) {
      body['coupon'] = context.read<CartCubit>().couponResponseModel!.code;
    }
  }

  @override
  void dispose() {
    pageController.dispose();
    super.dispose();
  }

  void load() {
    checkCubit = context.read<CheckoutCubit>();
    response = checkCubit.checkoutResponseModel;
    cartCalculation = context.read<CartCubit>().getCartCalculation();

    previousPrice = cartCalculation!.total;
    deliveryCubit.addDeliveryCharges(previousPrice);

    final addresses = response?.addresses ?? [];
    if (addresses.isNotEmpty) {
      billingAddressId = addresses.first.id;
      shippingAddressId = addresses.first.id;

      if (Utils.isMapEnable(context)) {
        deliveryCubit.addDistancePerKM(
          addresses.first.distance,
          addresses.first.priceRange,
        );
      } else {
        _populateShippingMethods(addresses.first);
      }
    }
  }

  int _cartTotalQty() {
    return (response?.cartProducts ?? [])
        .fold<int>(0, (sum, item) => sum + item.qty);
  }

  double _cartTotalWeight() {
    return (response?.cartProducts ?? []).fold<double>(0, (sum, item) {
      return sum + (item.product.weight * item.qty);
    });
  }

  void _populateShippingMethods(AddressModel address) {
    shippingMethodList.clear();
    final shippings = response?.shippings ?? [];
    if (shippings.isEmpty) return;

    final totalPrice = previousPrice;
    final totalWeight = _cartTotalWeight();
    final totalQty = _cartTotalQty();
    final cityId = address.cityId;

    final cityRules =
        shippings.where((rule) => rule.cityId == cityId).toList();
    final defaultRules =
        shippings.where((rule) => rule.cityId == 0).toList();
    final sourceRules = cityRules.isNotEmpty ? cityRules : defaultRules;

    bool matchesRule(ShippingResponseModel rule) {
      if (rule.type == basedOnPrice) {
        return totalPrice >= rule.conditionFrom &&
            (rule.conditionTo == -1 || totalPrice <= rule.conditionTo);
      }
      if (rule.type == basedOnWeight) {
        return totalWeight >= rule.conditionFrom &&
            (rule.conditionTo == -1 || totalWeight <= rule.conditionTo);
      }
      if (rule.type == basedOnQty) {
        return totalQty >= rule.conditionFrom &&
            (rule.conditionTo == -1 || totalQty <= rule.conditionTo);
      }
      return false;
    }

    shippingMethodList.addAll(sourceRules.where(matchesRule));

    if (shippingMethodList.isEmpty && defaultRules.isNotEmpty) {
      shippingMethodList.addAll(defaultRules);
    }

    if (shippingMethodList.isNotEmpty) {
      _selectShippingMethod(shippingMethodList.first);
    } else {
      shippingMethod = 0;
      selectedShippingFee = 0;
      deliveryCubit.addDeliveryCharges(previousPrice);
    }
  }

  void _selectShippingMethod(ShippingResponseModel method) {
    shippingMethod = method.id;
    selectedShippingFee = method.shippingFee;
    deliveryCubit.addDeliveryCharges(previousPrice + method.shippingFee);
    checkCubit.shippingFee(method.shippingFee);
    context.read<AddressCubit>().addShippingId(method.id);
  }

  bool _validateCheckout() {
    if (!CheckoutLegalConsentCatalog.allAccepted(_legalConsents)) {
      Utils.errorSnackBar(context, Language.termAndCondition);
      return false;
    }
    final invoiceError = validateBuyerInvoice(
      invoiceType: _invoiceType,
      tcIdentity: _tcIdentity,
      taxNumber: _taxNumber,
      taxOffice: _taxOffice,
      companyName: _companyName,
      postalCode: _postalCode,
    );
    if (invoiceError != null) {
      Utils.errorSnackBar(context, invoiceError);
      return false;
    }
    if (billingAddressId < 1) {
      Utils.errorSnackBar(context, Language.selectBillingAddress);
      return false;
    }
    if (shippingAddressId < 1) {
      Utils.errorSnackBar(context, Language.selectLocation);
      return false;
    }
    if (!Utils.isMapEnable(context) && shippingMethod < 1) {
      Utils.errorSnackBar(context, Language.selectShippingMethod);
      return false;
    }
    return true;
  }

  void _goToPayment() {
    if (!_validateCheckout()) return;

    body['shipping_address_id'] = shippingAddressId.toString();
    body['billing_address_id'] = billingAddressId.toString();
    body['shipping_method_id'] = shippingMethod.toString();
    body['invoice_type'] = _invoiceType;
    if (_invoiceType == 'corporate') {
      body['tax_number'] = _taxNumber;
      body['tax_office'] = _taxOffice;
      body['company_name'] = _companyName;
      body['is_e_invoice'] = _isEInvoice ? 1 : 0;
    } else {
      body['tc_identity'] = _tcIdentity;
      body['postal_code'] = _postalCode;
    }

    Navigator.pushNamed(
      context,
      RouteNames.placeOrderScreen,
      arguments: {'body': body, 'payment_status': response},
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Expanded(
          child: CustomScrollView(
            slivers: [
              SliverToBoxAdapter(child: _buildProductNumber()),
              _buildProductList(),
              SliverToBoxAdapter(child: _buildLocation()),
              if (!Utils.isMapEnable(context) && shippingMethodList.isNotEmpty)
                SliverToBoxAdapter(
                  child: ShippingMethodList(
                    shippingMethods: shippingMethodList,
                    onChange: (int id) {
                      for (final method in shippingMethodList) {
                        if (method.id == id) {
                          setState(() => _selectShippingMethod(method));
                          break;
                        }
                      }
                    },
                  ),
                ),
              if (Utils.isMapEnable(context))
                SliverToBoxAdapter(child: _locationWiseCharge()),
              SliverToBoxAdapter(child: _buildOrderSummary()),
              SliverToBoxAdapter(child: _buildInvoiceSection()),
              SliverToBoxAdapter(
                child: CheckoutLegalConsentPanel(
                  values: _legalConsents,
                  onChanged: (key, value) {
                    setState(() => _legalConsents[key] = value);
                  },
                ),
              ),
              const SliverToBoxAdapter(child: SizedBox(height: 16)),
            ],
          ),
        ),
        _bottomBtn(),
      ],
    );
  }

  Widget _buildInvoiceSection() {
    if (!_invoiceInitialized) {
      final info = context.read<UserProfileInfoCubit>().updatedInfo?.updateUserInfo;
      if (info != null) {
        _invoiceType = info.invoiceType.isNotEmpty ? info.invoiceType : 'individual';
        _tcIdentity = info.tcIdentity;
        _taxNumber = info.taxNumber;
        _taxOffice = info.taxOffice;
        _companyName = info.companyName;
        _isEInvoice = info.isEInvoice;
        _postalCode = info.postalCode.isNotEmpty ? info.postalCode : info.zipCode;
      }
      _invoiceInitialized = true;
    }

    return BuyerInvoiceForm(
      invoiceType: _invoiceType,
      tcIdentity: _tcIdentity,
      taxNumber: _taxNumber,
      taxOffice: _taxOffice,
      companyName: _companyName,
      isEInvoice: _isEInvoice,
      postalCode: _postalCode,
      onChanged: ({
        required invoiceType,
        required tcIdentity,
        required taxNumber,
        required taxOffice,
        required companyName,
        required isEInvoice,
        required postalCode,
      }) {
        setState(() {
          _invoiceType = invoiceType;
          _tcIdentity = tcIdentity;
          _taxNumber = taxNumber;
          _taxOffice = taxOffice;
          _companyName = companyName;
          _isEInvoice = isEInvoice;
          _postalCode = postalCode;
        });
      },
    );
  }

  Widget _buildOrderSummary() {
    return BlocBuilder<DeliveryChargesCubit, ShippingResponseModel>(
      builder: (context, state) {
        final shippingFee = Utils.isMapEnable(context)
            ? state.distancePrice
            : selectedShippingFee;
        final total = state.initialPrice + state.distancePrice;

        return Container(
          margin: const EdgeInsets.fromLTRB(20, 12, 20, 8),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: whiteColor,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: borderColor),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CustomText(
                text: Language.orderSummary,
                fontWeight: FontWeight.w700,
                fontSize: 16,
              ),
              const SizedBox(height: 10),
              _summaryRow(
                Language.subTotal.capitalizeByWord(),
                Utils.formatPrice(previousPrice, context),
              ),
              const SizedBox(height: 6),
              _summaryRow(
                Language.shippingCost.capitalizeByWord(),
                Utils.formatPrice(shippingFee, context),
              ),
              const Divider(height: 20),
              _summaryRow(
                Language.total.capitalizeByWord(),
                Utils.formatPrice(total, context),
                isTotal: true,
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _summaryRow(String label, String value, {bool isTotal = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        CustomText(
          text: label,
          fontWeight: isTotal ? FontWeight.w700 : FontWeight.w500,
          fontSize: isTotal ? 16 : 14,
        ),
        CustomText(
          text: value,
          isTranslate: false,
          fontWeight: isTotal ? FontWeight.w700 : FontWeight.w500,
          fontSize: isTotal ? 18 : 14,
          color: isTotal ? redColor : blackColor,
        ),
      ],
    );
  }

  Widget _bottomBtn() {
    return SafeArea(
      top: false,
      child: Container(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 12),
        decoration: BoxDecoration(
          color: whiteColor,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.06),
              blurRadius: 12,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: BlocBuilder<DeliveryChargesCubit, ShippingResponseModel>(
          builder: (context, state) {
            final total = state.initialPrice + state.distancePrice;
            return Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CustomText(
                        text: Language.total.capitalizeByWord(),
                        fontSize: 13,
                        color: textGreyColor,
                      ),
                      CustomText(
                        text: Utils.formatPrice(total, context),
                        isTranslate: false,
                        color: redColor,
                        fontWeight: FontWeight.w700,
                        fontSize: 20,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 16),
                Flexible(
                  child: PrimaryButton(
                    text: Language.placeOrderNow.capitalizeByWord(),
                    onPressed: _goToPayment,
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildLocation() {
    final addressCubit = context.read<AddressCubit>();
    return Column(
      children: [
        const SizedBox(height: 8),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              CustomText(
                text: Language.deliveryLocation.capitalizeByWord(),
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
              InkWell(
                onTap: () {
                  Navigator.pushNamed(context, RouteNames.addressScreen);
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  height: 22,
                  decoration: BoxDecoration(
                    color: Utils.dynamicPrimaryColor(context),
                    borderRadius: BorderRadius.circular(3),
                  ),
                  child: Center(
                    child: CustomText(
                      text: Language.add.capitalizeByWord(),
                      fontSize: 12.0,
                      color: blackColor,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 9),
        if (Utils.isMapEnable(context)) ...[
          _scrollingAddress(context, response?.addresses ?? []),
        ] else ...[
          BlocConsumer<AddressCubit, AddressModel>(
            listener: (context, states) {
              final state = states.addState;
              if (state is AddressStateError && state.statusCode == 503) {
                addressCubit.getAddress();
              }
            },
            builder: (context, states) {
              final state = states.addState;
              if (state is AddressStateLoading) {
                return Padding(
                  padding: const EdgeInsets.all(20),
                  child: CustomText(text: Language.loading.capitalizeByWord()),
                );
              } else if (state is AddressStateError) {
                if (state.statusCode == 503 || addressCubit.address != null) {
                  return _scrollingAddress(
                      context, addressCubit.address!.addresses);
                }
                return FetchErrorText(text: state.message);
              } else if (state is AddressStateLoaded) {
                if (state.address.addresses.isEmpty) {
                  return Padding(
                    padding: const EdgeInsets.all(20),
                    child: CustomText(
                        text: Language.noAddress.capitalizeByWord()),
                  );
                }
                return _scrollingAddress(context, state.address.addresses);
              }
              if (addressCubit.address != null) {
                return _scrollingAddress(
                    context, addressCubit.address!.addresses);
              }
              return FetchErrorText(text: Language.somethingWentWrong);
            },
          ),
        ],
      ],
    );
  }

  Widget _scrollingAddress(BuildContext context, List<AddressModel> addresses) {
    _ensureDefaultAddresses(addresses);
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          padding: Utils.symmetric(h: 8.0, v: 6.0),
          margin: Utils.symmetric(h: 20.0, v: 6.0),
          decoration: BoxDecoration(
            color: whiteColor,
            borderRadius: Utils.borderRadius(),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              ...addressType.asMap().entries.map(
                (e) => InkWell(
                  onTap: () {
                    setState(() {
                      addressTypeSelect = e.value;
                      pageController.animateToPage(
                        e.key,
                        duration: const Duration(milliseconds: 300),
                        curve: Curves.ease,
                      );
                    });
                  },
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 300),
                    curve: Curves.ease,
                    decoration: BoxDecoration(
                      color: addressTypeSelect == e.value
                          ? tabBgColor
                          : transparent,
                      borderRadius: Utils.borderRadius(r: 6.0),
                    ),
                    padding: Utils.symmetric(h: 22.0, v: 12.0),
                    child: CustomText(
                      text: e.value,
                      color: blackColor,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
        SizedBox(
          height: Utils.mediaQuery(context).height * 0.22,
          child: PageView.builder(
            itemCount: addressType.length,
            controller: pageController,
            physics: const NeverScrollableScrollPhysics(),
            itemBuilder: (context, index) {
              return SingleChildScrollView(
                padding: const EdgeInsets.only(left: 20),
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: List.generate(
                    addresses.length,
                    (index) => _addressTile(addresses, index),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _addressTile(List<AddressModel> addresses, int index) {
    return Padding(
      padding: const EdgeInsets.only(right: 10, top: 10.0),
      child: InkWell(
        borderRadius: Utils.borderRadius(r: 12.0),
        onTap: () => _selectAddress(addresses[index]),
        child: AddressCardComponent(
          isEditButtonShow: false,
          selectAddress:
              _isBillingTab ? billingAddressId : shippingAddressId,
          addressModel: addresses[index],
          type: addresses[index].type,
        ),
      ),
    );
  }

  Widget _buildProductList() {
    final appSetting = context.read<AppSettingCubit>();
    return SliverPadding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      sliver: SliverList(
        delegate: SliverChildBuilderDelegate(
          (context, index) {
            return CheckoutSingleItem(
              appSetting: appSetting,
              product: response!.cartProducts![index],
            );
          },
          childCount: response?.cartProducts?.length,
          addAutomaticKeepAlives: true,
        ),
      ),
    );
  }

  Widget _buildProductNumber() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 14),
      child: Row(
        children: [
          const Icon(Icons.shopping_cart_rounded, color: redColor),
          const SizedBox(width: 10),
          CustomText(
            text:
                "${response?.cartProducts?.length ?? 0} ${Language.products.capitalizeByWord()}",
            fontSize: 16.0,
            fontWeight: FontWeight.w500,
          ),
        ],
      ),
    );
  }

  Widget _locationWiseCharge() {
    return BlocBuilder<DeliveryChargesCubit, ShippingResponseModel>(
      builder: (context, state) {
        if (state.distancePrice == 0.0) {
          return const SizedBox.shrink();
        }

        return Container(
          margin: Utils.symmetric(h: 20.0, v: 12.0),
          decoration: BoxDecoration(
            color: whiteColor,
            borderRadius: Utils.borderRadius(r: 12.0),
            border: Border.all(color: greenColor),
          ),
          child: ListTile(
            horizontalTitleGap: 0,
            title: CustomText(
              text:
                  '${Language.fee.capitalizeByWord()}: ${Utils.formatPrice(state.distancePrice, context)}',
              isTranslate: false,
            ),
            subtitle: CustomText(
              text: Language.basedOnDistance.capitalizeByWord(),
            ),
          ),
        );
      },
    );
  }
}
