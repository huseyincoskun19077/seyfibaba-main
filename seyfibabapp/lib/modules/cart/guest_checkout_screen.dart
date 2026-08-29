import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/router_name.dart';
import '../../dummy_data/all_dummy_data.dart';
import '../../widgets/primary_button.dart';
import '../animated_splash_screen/controller/currency/currency_cubit.dart';
import '../authentication/models/checkout_body_model.dart';
import '../profile/controllers/address/address_cubit.dart';
import '../profile/model/address_model.dart';
import '/widgets/capitalized_word.dart';

import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/custom_text.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/page_refresh.dart';
import '../../widgets/rounded_app_bar.dart';
import '../product_details/controller/cubit/details_state_model.dart';
import '../product_details/controller/cubit/product_details_cubit.dart';
import 'component/address_card_component.dart';
import 'component/checkout_legal_consent.dart';
import 'component/guest_cart_component.dart';
import 'component/shiping_method_list.dart';
import 'controllers/delivery_charges/delivery_charges_cubit.dart';
import 'controllers/checkout/checkout_cubit.dart';
import 'model/coupon_response_model.dart';
import 'model/guest_cart_product.dart';
import 'model/shipping_response_model.dart';

class GuestCheckoutScreen extends StatefulWidget {
  const GuestCheckoutScreen({super.key});

  @override
  State<GuestCheckoutScreen> createState() => _GuestCheckoutScreenState();
}

class _GuestCheckoutScreenState extends State<GuestCheckoutScreen> {
  // CartCalculation? cartCalculation;
  late CheckoutCubit checkCubit;
  late ProductDetailsCubit detailCubit;
  late AddressCubit addressCubit;
  late String vendorId;

  @override
  void initState() {
    super.initState();
    checkCubit = context.read<CheckoutCubit>();
    detailCubit = context.read<ProductDetailsCubit>();
    addressCubit = context.read<AddressCubit>();
    checkCubit..addIndex(-1)..clearShipping();
    if(detailCubit.state.vendorIds.isNotEmpty){
      vendorId = detailCubit.state.vendorIds.first.toString();
    }else{
      vendorId = '';
    }

    if(Utils.isLoggedIn(context)){
      addressCubit.addShippingId(0);
      Future.microtask(() {
        final coupon = detailCubit.couponResponse?.code ?? '';
        checkCubit.loadCheckoutContext(vendorId: vendorId, coupon: coupon);
        addressCubit.getAddress();
      });
    } else {
      Future.microtask(() => checkCubit.loadCheckoutContext(vendorId: vendorId));
    }
  }
  @override
  Widget build(BuildContext context) {
    return  Scaffold(
      appBar: RoundedAppBar(titleText: Language.checkout.capitalizeByWord()),
      body: PageRefresh(
        onRefresh: () async {
          detailCubit.getGuestSavedProduct();
          if(Utils.isLoggedIn(context)){
            Future.microtask(() {
              final coupon = detailCubit.couponResponse?.code ?? '';
              checkCubit.loadCheckoutContext(vendorId: vendorId, coupon: coupon);
              addressCubit.getAddress();
            });
          } else {
            checkCubit.loadCheckoutContext(vendorId: vendorId);
          }
        },
        child: MultiBlocListener(
          listeners: [
            BlocListener<CheckoutCubit, CouponResponseModel>(
              listener: (context, state) {
                final checkState = state.checkState;
                if (checkState is CheckoutStateError &&
                    !Utils.isLoggedIn(context)) {
                  Utils.errorSnackBar(context, checkState.message);
                }
              },
            ),
          ],
          child: BlocConsumer<ProductDetailsCubit, DetailsStateModel>(
          listener: (context, states) {
            final state = states.detailsState;
            if (state is ProductDetailsStateError) {
            }
          },
          builder: (context, states) {
            final state = states.detailsState;
            if (state is GuestProductError) {
              return FetchErrorText(text: state.message);
            }
            if (state is GuestAllSavedProduct) {
              return GuestCheckoutBody(product: state.products);
            }
            if (detailCubit.savedProduct.isNotEmpty) {
              return GuestCheckoutBody(product: detailCubit.savedProduct);
            } else {
              return FetchErrorText(text: Language.somethingWentWrong);
            }
          },
        ),
        ),
      ),
      // bottomNavigationBar: _bottomBtn(),
    );
  }
}


class GuestCheckoutBody extends StatefulWidget {
  const GuestCheckoutBody({super.key, required this.product});
  final List<GustCartProduct> product;

  @override
  State<GuestCheckoutBody> createState() => _GuestCheckoutBodyState();
}

class _GuestCheckoutBodyState extends State<GuestCheckoutBody> {

  late CheckoutCubit checkCubit;
  late ProductDetailsCubit detailCubit;
  late AddressCubit addressCubit;
  late DeliveryChargesCubit deliveryCubit;
  late CurrencyCubit cCubit;



  int shippingMethod = 0;
  final Map<String, bool> _legalConsents = {};
  int billingAddressId = 0;
  int shippingAddressId = 0;
  double previousPrice = 0.0;
  double totalPrice = 0.0;
  String basedOnWeight = 'base_on_weight';
  String basedOnPrice = 'base_on_price';
  String basedOnQty = 'base_on_qty';
  double totalWeight = 0.0;
  double perUnit = 300.0;

  late double shippingPrice;




  final double height = 140;
  String addressTypeSelect = addressType[0];
  bool _defaultAddressesScheduled = false;

  bool get _isBillingTab => addressTypeSelect == addressType[0];

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
            _applyShippingForAddress(first);
          }
        }
      });
    });
  }

  void _autoSelectShippingMethod() {
    final shippings = checkCubit.state.shippings;
    if (shippings.isEmpty || addressCubit.state.userId > 0) return;
    final item = shippings.first;
    addressCubit.addShippingId(item.id);
    checkCubit..addIndex(0)..addCheckoutShipping(item.shippingFee);
    _shippingFee();
  }

  void _applyShippingForAddress(AddressModel address) {
    addressCubit.addShippingId(0);
    checkCubit
      ..addIndex(-1)
      ..addCheckoutShipping(0.0)
      ..filterShippingAddress(
        detailCubit.state.cartPrice,
        detailCubit.state.totalWight,
        detailCubit.state.totalQty,
        address.cityId,
      );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _autoSelectShippingMethod();
      setState(_shippingFee);
    });
  }

  void _selectAddress(AddressModel address) {
    setState(() {
      if (_isBillingTab) {
        billingAddressId = address.id;
        if (shippingAddressId < 1) {
          shippingAddressId = address.id;
          if (!Utils.isMapEnable(context)) {
            _applyShippingForAddress(address);
          }
        }
      } else {
        shippingAddressId = address.id;
        if (billingAddressId < 1) billingAddressId = address.id;
        if (Utils.isMapEnable(context)) {
          checkCubit.getDistance(address.latitude, address.longitude);
          _shippingFee();
        } else {
          _applyShippingForAddress(address);
        }
      }
    });
  }

  PageController pageController =
  PageController(initialPage: 0, keepPage: true, viewportFraction: 1);
  final body = <String, dynamic>{};
  final shippingMethodList = <ShippingResponseModel>[];



  @override
  void initState() {
    super.initState();
    _init();
  }



  _init(){
    checkCubit = context.read<CheckoutCubit>();
    detailCubit = context.read<ProductDetailsCubit>();
    addressCubit = context.read<AddressCubit>();
    deliveryCubit = context.read<DeliveryChargesCubit>();
    cCubit = context.read<CurrencyCubit>();
    deliveryCubit.resetDistance();

    if(Utils.isLoggedIn(context) && checkCubit.state.distancePrice != 0.0){
      checkCubit.distancePrice();

    }
    if(Utils.isLoggedIn(context) && checkCubit.state.shippingFee != 0.0){
      checkCubit.shippingFee(0.0);
    }
    // _initFirstItem();

    _shippingFee();
    checkCubit.addCheckoutPrice(shippingPrice);

  }

  _shippingFee(){
    // checkCubit.shippingFee(0.0);

    final cartPrice = detailCubit.state.priceAfterCoupon != 0.0?detailCubit.state.priceAfterCoupon:detailCubit.state.cartPrice;
    if(Utils.isMapEnable(context)){
      shippingPrice = cartPrice + checkCubit.state.distancePrice;
      checkCubit.addCheckoutPrice(shippingPrice);
    }else{
      shippingPrice = cartPrice + checkCubit.state.shippingFee;
    }

    // debugPrint('checkCubit.state.shippingFee ${checkCubit.state.shippingFee}');
    // debugPrint('shippingPrice $shippingPrice');
  }

  void _goToPayment() {
    if (!Utils.isLoggedIn(context)) {
      Utils.errorSnackBar(context, Language.guestCheckoutDisabled);
      Navigator.pushNamed(context, RouteNames.authenticationScreen);
      return;
    }

    if (shippingAddressId < 1 || billingAddressId < 1) {
      Utils.errorSnackBar(context, Language.selectLocation);
      return;
    }

    if (!Utils.isMapEnable(context) &&
        addressCubit.state.userId < 1 &&
        checkCubit.state.shippings.isNotEmpty) {
      Utils.errorSnackBar(context, Language.selectShippingMethod);
      return;
    }

    final checkoutModel = checkCubit.checkoutResponseModel;
    if (checkoutModel == null) {
      Utils.errorSnackBar(context, Language.somethingWentWrong);
      return;
    }

    final coupon = detailCubit.couponResponse?.code;
    final body = <String, dynamic>{
      'shipping_address_id': shippingAddressId.toString(),
      'billing_address_id': billingAddressId.toString(),
      'shipping_method_id': addressCubit.state.userId.toString(),
      if (coupon != null && coupon.isNotEmpty) 'coupon': coupon,
    };

    final savedCart = detailCubit.savedProduct.map((item) {
      final map = <String, dynamic>{
        'product_id': item.productId,
        'qty': item.qty,
      };
      if (item.variants?.isNotEmpty ?? false) {
        map['variants'] = item.variants!
            .map(
              (v) => {
                'variant_id': v.variantId,
                'variant_item_id': v.variantItemId,
              },
            )
            .toList();
      }
      return map;
    }).toList();

    if (savedCart.isNotEmpty) {
      body['cart_products'] = savedCart;
    }

    checkCubit.addCheckoutBody(
      CheckoutBodyModel(
        shippingAddress: shippingAddressId.toString(),
        billingAddress: billingAddressId.toString(),
        shippingMethod: addressCubit.state.userId.toString(),
        coupon: coupon ?? '',
      ),
    );

    Navigator.pushNamed(
      context,
      RouteNames.placeOrderScreen,
      arguments: {
        'body': body,
        'payment_status': checkoutModel,
      },
    );
  }

  _initFirstItem(){
    if(checkCubit.checkoutResponseModel?.shippings?.isNotEmpty??false){
      // debugPrint('not-empty ${checkCubit.checkoutResponseModel!.shippings!.first}');
      shippingMethodList.add(checkCubit.checkoutResponseModel!.shippings!.first);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Expanded(
          child: CustomScrollView(
            slivers: [
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 14),
                  child: Row(
                    children: [
                      const Icon(Icons.shopping_cart_rounded, color: redColor),
                      const SizedBox(width: 10),
                      CustomText(text:
                      _getText(), fontSize: 16, fontWeight: FontWeight.w600
                      ),
                    ],
                  ),
                ),
              ),
              if(widget.product.isNotEmpty)...[ SliverPadding(
                padding: Utils.symmetric(h: 12.0),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                        (context, index) {
                      final item = widget.product[index];
                      return  GuestCartComponent(product: item,isVisible: false,visibleQty:true);
                    },
                    childCount: widget.product.length,
                    addAutomaticKeepAlives: true,
                  ),
                ),
              ),]else...[
                SliverPadding(
                  padding: Utils.symmetric(),
                  sliver: AppEmptyState(
                    icon: Icons.shopping_cart_outlined,
                    title: Language.emptyCartTitle,
                    subtitle: Language.emptyCartHint,
                    isSliver: true,
                  ),
                ),
              ],


              // SliverToBoxAdapter(child: _buildLocation()),
              /*SliverToBoxAdapter(
                child: BlocBuilder<CheckoutCubit,CouponResponseModel>(
                  builder: (context,state){
                    debugPrint('stored-shippings ${state.shippings}');
                    // if(!Utils.isMapEnable(context) && state.shippings.isNotEmpty){
                    if(state.shippings.isNotEmpty){
                      return ShippingMethodList(shippingMethods: state.shippings,onChange: (int index){
                        addressCubit.addShippingId(index);
                      },padding: Utils.all());
                    }else{
                      return const SizedBox.shrink();
                    }

                  },
                ),
              ),*/

              if(Utils.isLoggedIn(context))...[
                SliverToBoxAdapter(child: _buildLocation()),
                const SliverToBoxAdapter(child: NewShippingMethod()),
                if (Utils.isMapEnable(context) && checkCubit.state.distancePrice != 0.0) ...[
                   SliverToBoxAdapter(child: ShippingPerKM(margin: Utils.symmetric().copyWith(top: 10.0))),
                  // SliverToBoxAdapter(child: _locationWiseCharge()),
                ],
               /* SliverToBoxAdapter(
                child: BlocBuilder<CheckoutCubit,CouponResponseModel>(
                  builder: (context,state){
                    debugPrint('stored-shippings ${state.shippings}');
                    // if(!Utils.isMapEnable(context) && state.shippings.isNotEmpty){
                    if(state.shippings.isNotEmpty){
                      return ShippingMethodList(shippingMethods: state.shippings,onChange: (int index){
                        addressCubit.addShippingId(index);
                        checkCubit.addCheckoutShipping(state.shippings[index].shippingFee);
                      },padding: Utils.symmetric());
                    }else{
                      return const SizedBox.shrink();
                    }

                  },
                ),
              ),*/

                /*if (shippingMethodList.isNotEmpty)...[
                  SliverToBoxAdapter(
                    child: ShippingMethodList(
                      shippingMethods: shippingMethodList,
                      onChange: (int id) {
                        //_shippingFee();
                        shippingMethod = id;
                        for (var i in shippingMethodList) {
                          if (i.id == id) {
                            // totalPrice = previousPrice + i.shippingFee;
                           // totalPrice = previousPrice + checkCubit.state.shippingFee;
                            checkCubit.addCheckoutShipping(i.shippingFee);
                           // context.read<DeliveryChargesCubit>().addDeliveryCharges(totalPrice);
                          }
                        }
                      },
                    ),
                  ),
                ],*/
                // if (Utils.isMapEnable(context) && checkCubit.state.distancePrice != 0.0) ...[
                //    SliverToBoxAdapter(child: ShippingPerKM(margin: Utils.symmetric().copyWith(top: 10.0))),
                //   // SliverToBoxAdapter(child: _locationWiseCharge()),
                // ],
              ],

            ],
          ),
        ),
        _bottomBtn(),
      ],
    );
  }



  Widget _bottomBtn() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      child: Column(
        children: [
          CheckoutLegalConsentPanel(
            values: _legalConsents,
            onChanged: (key, value) {
              setState(() => _legalConsents[key] = value);
            },
            padding: const EdgeInsets.fromLTRB(10, 0, 10, 8),
          ),
          Row(
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                 /* BlocBuilder<DeliveryChargesCubit, ShippingResponseModel>(
                    builder: (context, state) {
                      _shippingFee();
                      // final cartPrice = detailCubit.state.priceAfterCoupon != 0.0?detailCubit.state.priceAfterCoupon:detailCubit.state.cartPrice;
                      // final price = cartPrice + checkCubit.state.shippingFee;
                      // debugPrint('cart-price $cartPrice');
                      // debugPrint('cart-price2 $price');
                      return Row(
                        children: [
                          CustomText(
                            text: '${Language.total.capitalizeByWord()}: ',
                            color: redColor,
                            fontWeight: FontWeight.w700,
                            fontSize: 16.0,
                          ),
                          CustomText(
                            text: Utils.formatPrice(shippingPrice, context),
                            isTranslate: false,
                            color: redColor,
                            fontSize: 18.0,
                            fontWeight: FontWeight.w700,
                          ),
                        ],
                      );
                    },
                  ),*/
                  BlocBuilder<CheckoutCubit, CouponResponseModel>(
                    builder: (context, state) {
                      // _shippingFee();
                      // final cartPrice = detailCubit.state.priceAfterCoupon != 0.0?detailCubit.state.priceAfterCoupon:detailCubit.state.cartPrice;
                      // final price = cartPrice + checkCubit.state.shippingFee;
                      // debugPrint('cart-price $cartPrice');
                      // debugPrint('cart-price2 $price');
                      return Row(
                        children: [
                          CustomText(
                            text: '${Language.total.capitalizeByWord()}: ',
                            color: redColor,
                            fontWeight: FontWeight.w700,
                            fontSize: 16.0,
                          ),
                          CustomText(
                            text: Utils.formatPrice(state.totalCheckoutPrice, context),
                            isTranslate: false,
                            color: redColor,
                            fontSize: 18.0,
                            fontWeight: FontWeight.w700,
                          ),
                        ],
                      );
                    },
                  ),
                  CustomText(text: "+${Language.shippingCost.capitalizeByWord()}",fontSize: 12.0),
                ],
              ),
              const SizedBox(width: 20),
              Flexible(
                child: PrimaryButton(
                  text: Language.placeOrderNow.capitalizeByWord(),
                  onPressed: () {
                    if (!CheckoutLegalConsentCatalog.allAccepted(_legalConsents)) {
                      Utils.errorSnackBar(context, Language.termAndCondition);
                      return;
                    }
                    _goToPayment();
                  },
                ),
              ),
            ],
          ),
        ],
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
                  fontSize: 16.0,
                  fontWeight: FontWeight.w600),
              InkWell(
                onTap: () {
                  Navigator.pushNamed(context, RouteNames.addAddressScreen);
                  // Navigator.pushNamed(context, RouteNames.addressScreen);
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12.0),
                  height: 22,
                  decoration: BoxDecoration(
                    color: Utils.dynamicPrimaryColor(context),
                    borderRadius: BorderRadius.circular(3),
                  ),
                  child: Center(
                    child: CustomText(
                        text: Language.add.capitalizeByWord(),
                        fontSize: 12.0,
                        color: blackColor),
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 9),
        BlocConsumer<AddressCubit, AddressModel>(
          listener: (context, states) {
            final state = states.addState;
            if (state is AddressStateError) {
              if (state.statusCode == 503) {
                addressCubit.getAddress();
              }
            }
            if(state is AddressStateLoaded || (addressCubit.address?.addresses.isNotEmpty??false)){
              // debugPrint('called-from-listener');
              // _initFirstItem();
            }
          },
          builder: (context, states) {
            final state = states.addState;
            if (state is AddressStateLoading) {
              return CustomText(text: Language.loading.capitalizeByWord());
            } else if (state is AddressStateError) {
              if (state.statusCode == 503 || addressCubit.address != null) {
                return _scrollingAddress(context, addressCubit.address?.addresses??[]);
              }
              return FetchErrorText(text: state.message);
            } else if (state is AddressStateLoaded) {
              if (state.address.addresses.isEmpty) {
                return CustomText(
                    text: Language.noAddress.capitalizeByWord());
              } else {
                return _scrollingAddress(context, state.address.addresses);
              }
            }
            if (addressCubit.address != null) {
              return _scrollingAddress(context, addressCubit.address?.addresses??[]);
            } else {
              return FetchErrorText(text: Language.somethingWentWrong);
            }
          },
        ),
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
                    setState(
                          () {
                        addressTypeSelect = e.value;
                        pageController.animateToPage(e.key,
                            duration: const Duration(microseconds: 500),
                            curve: Curves.ease);
                      },
                    );
                  },
                  child: AnimatedContainer(
                    // width: Utils.mediaQuery(context).width / 2.6,

                    duration: const Duration(microseconds: 300),
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
        // height: MediaQuery.of(context).size.height * 0.25,

        SizedBox(
          height: Utils.mediaQuery(context).height * 0.22,
          child: PageView.builder(
              itemCount: addressType.length,
              controller: pageController,
              physics: const NeverScrollableScrollPhysics(),
              itemBuilder: (context, index) {
                //print('address-length ${addresses.length}');
                return SingleChildScrollView(
                  padding: const EdgeInsets.only(left: 20),
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      ...List.generate(addresses.length, (index) => shippingCharges(addresses, index)),
                    ],
                  ),
                );
              }),
        )
      ],
    );
  }

  Widget shippingCharges(List<AddressModel> addresses, int index) {
    return Padding(
      padding: const EdgeInsets.only(right: 10, top: 10.0),
      child: InkWell(
        borderRadius: Utils.borderRadius(r: 12.0),
        onTap: () => _selectAddress(addresses[index]),
        child: AddressCardComponent(
            isEditButtonShow: false,
            selectAddress: addressTypeSelect == addressType[0]
                ? billingAddressId
                : shippingAddressId,
            addressModel: addresses[index],
            type: addresses[index].type),
      ),
    );
  }

  Widget _locationWiseCharge() {
    String convertPrice(String price) {
      if (cCubit.state.currencies.isNotEmpty) {
        return Utils.convertMulCurrency(
            price, context, cCubit.state.currencies.first);
      } else {
        return Utils.formatPrice(price, context);
      }
    }

    return BlocBuilder<DeliveryChargesCubit, ShippingResponseModel>(
      builder: (context, state) {
        if (state.distancePrice != 0.0) {
          return Container(
            margin: Utils.symmetric(v: 12.0),
            decoration: BoxDecoration(
                color: whiteColor,
                borderRadius: Utils.borderRadius(r: 12.0),
                border: Border.all(color: greenColor),
                boxShadow: [
                  BoxShadow(
                    offset: const Offset(0.0, 0.0),
                    spreadRadius: 0.0,
                    blurRadius: 0.0,
                    // color: whiteColor
                    color: const Color(0xFF000000).withOpacity(0.4),
                  ),
                ]),
            child: ListTile(
              horizontalTitleGap: 0,
              title: CustomText(
                  text:
                  'Fee: ${convertPrice(state.distancePrice.toStringAsFixed(2))}'),
              //subtitle: Text(e.shippingRule),
              subtitle: CustomText(
                text: Language.deliveryCharge,
              ),
            ),
          );
        } else {
          return const SizedBox.shrink();
        }
      },
    );
  }

  String _getText() {
    final length = widget.product.length;
    if (length > 1) {
      return '$length ${Language.products.capitalizeByWord()}';
    } else {
      return '$length ${Language.product.capitalizeByWord()}';
    }
  }
}