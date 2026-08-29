import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/widgets/fetch_error_text.dart';
import 'package:shop_o/widgets/loading_widget.dart';

import '/modules/profile/controllers/map/map_cubit.dart';
import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '../../core/router_name.dart';
import '../../dummy_data/all_dummy_data.dart';
import '../../utils/constants.dart';
import '../../utils/k_images.dart';
import '../../utils/utils.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/confirm_dialog.dart';
import '../../widgets/custom_text.dart';
import '../../widgets/rounded_app_bar.dart';
import '../cart/component/address_card_component.dart';
import 'controllers/address/address_cubit.dart';
import 'controllers/country_state_by_id/country_state_by_id_cubit.dart';
import 'model/address_model.dart';
import 'model/billing_shipping_model.dart';

class AddressScreen extends StatefulWidget {
  const AddressScreen({super.key});

  @override
  State<AddressScreen> createState() => _AddressScreenState();
}

class _AddressScreenState extends State<AddressScreen> {
  @override
  void initState() {
    Future.microtask(() {
      context.read<CountryStateByIdCubit>().countryListLoaded();
      context.read<AddressCubit>().getAddress();
    });
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    final addressCubit = context.read<AddressCubit>();
    return Scaffold(
      appBar: RoundedAppBar(
          titleText: Language.address.capitalizeByWord(), bgColor: scaBgColor),
      body: BlocConsumer<AddressCubit, AddressModel>(
        listener: (context, states) {
          final state = states.addState;
          if (state is AddressStateUpdated) {
            context.read<AddressCubit>().getAddress();
          }
          if (state is AddressStateError) {
            if (state.statusCode == 503 || addressCubit.address == null) {
              addressCubit.getAddress();
            }
          }
        },
        builder: (context, states) {
          final state = states.addState;
          if (state is AddressStateLoading) {
            return const LoadingWidget();
          } else if (state is AddressStateError) {
            if (state.statusCode == 503 || addressCubit.address != null) {
              return _LoadedWidget(address: addressCubit.address!);
            } else {
              return FetchErrorText(text: state.message);
            }
          } else if (state is AddressStateLoaded) {
            return _LoadedWidget(address: state.address);
          }
          if (addressCubit.address != null) {
            return _LoadedWidget(address: addressCubit.address!);
          } else {
            return FetchErrorText(text: Language.somethingWentWrong);
          }
        },
      ),
      floatingActionButton: BlocBuilder<AddressCubit, AddressModel>(
        builder: (context, states) {
          final state = states.addState;
          if (state is AddressStateLoading) {
            return const SizedBox.shrink();
          } else if (state is AddressStateLoaded) {
            return _buildBottomButton(context);
          }
          if (addressCubit.address != null) {
            return _buildBottomButton(context);
          } else {
            return const SizedBox.shrink();
          }
        },
      ),
    );
  }

  Widget _buildBottomButton(BuildContext context) {
    return FloatingActionButton(
      backgroundColor: blackColor,
      onPressed: () {
        context.read<AddressCubit>().clearAddress();
        context.read<MapCubit>().clear();
        Navigator.pushNamed(
          context,
          RouteNames.addAddressScreen,
          arguments: {"type": "new"},
        );
      },
      child: const Icon(Icons.add, color: Colors.white),
    );
  }
}

class _LoadedWidget extends StatefulWidget {
  const _LoadedWidget({required this.address});

  final AddressBook address;

  @override
  State<_LoadedWidget> createState() => _LoadedWidgetState();
}

class _LoadedWidgetState extends State<_LoadedWidget> {
  final _pageController =
      PageController(initialPage: 0, keepPage: true, viewportFraction: 1);
  String addressTypeSelect = Language.billingAddress.capitalizeByWord();
  int billingAddressId = 0;
  int shippingAddressId = 0;

  @override
  void initState() {
    _initSelectedAddress();
    super.initState();
  }

  _initSelectedAddress() {
    if (widget.address.addresses.isNotEmpty &&
        billingAddressId == 0 &&
        shippingAddressId == 0) {
      billingAddressId = widget.address.addresses.reversed.toList().first.id;
      shippingAddressId = widget.address.addresses.reversed.toList().first.id;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [

        if (widget.address.addresses.isNotEmpty)...[
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
                          _pageController.animateToPage(e.key,
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
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: CustomText(
              text: Language.swipeToDelete.capitalizeByWord(),
              color: textGreyColor,
              fontWeight: FontWeight.w500,
            ),
          ),
          Expanded(
            child: PageView.builder(
              itemCount: addressType.length,
              controller: _pageController,
              physics: const NeverScrollableScrollPhysics(),
              itemBuilder: (context, index) {
                return ListView.builder(
                  padding:
                  const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  itemCount: widget.address.addresses.reversed.toList().length,
                  itemBuilder: (context, index) {
                    final item = widget.address.addresses.reversed.toList()[index];
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: InkWell(
                        borderRadius: Utils.borderRadius(r: 12.0),
                        onTap: () {
                          if (addressTypeSelect ==
                              Language.billingAddress.capitalizeByWord()) {
                            billingAddressId = item.id;
                          } else {
                            shippingAddressId = item.id;
                          }
                          setState(() {});
                        },
                        child: Dismissible(
                          key: Key(item.toString()),
                          onDismissed: (direction) async {
                            final item =
                            widget.address.addresses.reversed.toList()[index];
                            final result = await context
                                .read<AddressCubit>()
                                .deleteSingleAddress(item.id.toString());

                            result.fold(
                                  (failure) {
                                Utils.errorSnackBar(context, failure.message);
                              },
                                  (success) {
                                widget.address.addresses.remove(item);
                                setState(() {}); // Update the UI
                                Utils.showSnackBar(
                                    context, Language.addressDeletedSuccessfully);
                              },
                            );
                          },
                          confirmDismiss: (v) async {
                            return await showDialog(
                              context: context,
                              barrierDismissible: false,
                              builder: (context) => ConfirmDialog(
                                icon: Kimages.deleteIcon2,
                                message: Language.deleteAddressConfirm,
                                confirmText: Language.yesDelete,
                                cancelText: Language.noThanks,
                                onTap: () {
                                  Navigator.of(context).pop(true);
                                },
                              ),
                            );
                          },
                          child: AddressCardComponent(
                            selectAddress: addressTypeSelect ==
                                Language.billingAddress.capitalizeByWord()
                                ? billingAddressId
                                : shippingAddressId,
                            addressModel:
                            widget.address.addresses.reversed.toList()[index],
                            type: widget.address.addresses.reversed
                                .toList()[index]
                                .type,
                            isEditButtonShow: false,
                          ),
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ]else...[
          Expanded(
            child: AppEmptyState(
              icon: Icons.location_on_outlined,
              title: Language.noAddress,
              subtitle: Language.emptyAddressHint,
              action: TextButton(
                onPressed: () =>
                    Navigator.pushNamed(context, RouteNames.addAddressScreen),
                child: Text(Language.addNewAddress),
              ),
            ),
          ),
        ],

      ],
    );
  }
}
