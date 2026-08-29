import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/modules/animated_splash_screen/controller/currency/currency_cubit.dart';
import 'package:shop_o/modules/animated_splash_screen/controller/currency/currency_state_model.dart';
import 'package:shop_o/widgets/fetch_error_text.dart';

import '../../../widgets/custom_text.dart';
import '../../../widgets/translate_form_text.dart';
import '/modules/cart/model/cart_calculation_model.dart';
import '/widgets/capitalized_word.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/primary_button.dart';
import '../controllers/cart/cart_cubit.dart';
import '../model/cart_response_model.dart';

class PanelCollapseComponent extends StatelessWidget {
  const PanelCollapseComponent(
      {super.key,
      required this.height,
      required this.cartResponseModel,
      required this.totalPrice});

  final CartResponseModel cartResponseModel;
  final double height;
  final double totalPrice;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      width: MediaQuery.of(context).size.width,
      decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      child: Column(
        children: [
          Container(
            decoration: BoxDecoration(
              color: Colors.grey,
              borderRadius: BorderRadius.circular(2),
            ),
            height: 4.0,
            width: 60.0,
          ),
          // const SizedBox(height: 9),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              CustomText(
                  text: Language.orderAmount.capitalizeByWord(),
                  fontSize: 16.0,
                  fontWeight: FontWeight.w600),
              BlocBuilder<CurrencyCubit, CurrencyStateModel>(
                builder: (context, state) {
                  return CustomText(
                      text: Utils.formatPrice(totalPrice, context),
                      fontSize: 16,
                      fontWeight: FontWeight.w600);
                },
              ),
            ],
          ),
          const SizedBox(height: 5),
          GestureDetector(
            onTap: () =>
                Navigator.pushNamed(context, RouteNames.checkoutScreen),
            child: SizedBox(
              height: 50.0,
              child: PrimaryButton(
                text: Language.checkout.capitalizeByWord(),
                onPressed: () {
                  Navigator.pushNamed(context, RouteNames.checkoutScreen);
                },
              ),
            ),
          )
        ],
      ),
    );
  }
}

class PanelComponent extends StatefulWidget {
  const PanelComponent(
      {super.key,
      this.controller,
      required this.cartResponseModel,
      required this.cartCalculation});

  final CartResponseModel cartResponseModel;

  final ScrollController? controller;
  final CartCalculation? cartCalculation;

  @override
  State<PanelComponent> createState() => _PanelComponentState();
}

class _PanelComponentState extends State<PanelComponent> {
  final textController = TextEditingController();

  late CartResponseModel cResponse;

  @override
  void dispose() {
    super.dispose();
    textController.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ListView(
      controller: widget.controller,
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 20),
      children: [
        CustomText(text: Language.applyCoupon.capitalizeByWord()),
        const SizedBox(height: 8.0),
        CustomText(
            text: Language.billDetails.capitalizeByWord(),
            fontSize: 20.0,
            fontWeight: FontWeight.w600),
        const SizedBox(height: 8.0),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            CustomText(

                text: Language.subTotal.capitalizeByWord(), fontSize: 16.0),
            CustomText(
                isTranslate: false,
                text: Utils.formatPrice(
                    widget.cartCalculation!.subTotal, context),
                fontSize: 16.0)
          ],
        ),
        const SizedBox(height: 8),
        _buildTextField(),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            CustomText(
                text: Language.discountCoupon.capitalizeByWord(),
                fontSize: 16.0,
                color: redColor),
            BlocConsumer<CartCubit, CartState>(listener: (context, state) {
              if (state is CartStateDecIncrementLoading) {
                Utils.loadingDialog(context);
              } else {
                Utils.closeDialog(context);
              }
            }, builder: (context, state) {
              if (state is CartStateError) {
                return FetchErrorText(text: state.message);
              }
              if (state is CartCouponStateLoaded) {
                widget.cartCalculation!.copyWith(
                    total: (widget.cartCalculation!.total -
                        double.parse(
                            state.couponResponseModel.discount.toString())));

                return CustomText(
                    isTranslate: false,
                    text: Utils.formatPrice(
                        state.couponResponseModel.discount, context),
                    fontSize: 16,
                    color: redColor);
              }
              return const SizedBox();
            }),
          ],
        ),
        const SizedBox(height: 8),
        Container(
          height: 1,
          color: borderColor,
        ),
        const SizedBox(height: 10),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            CustomText(
                text: Language.total.capitalizeByWord(),
                fontSize: 16.0,
                fontWeight: FontWeight.w600),
            CustomText(
                isTranslate: false,
                text: Utils.formatPrice(widget.cartCalculation!.total, context),
                fontSize: 16.0,
                fontWeight: FontWeight.w600)
          ],
        ),
        const SizedBox(height: 14),
        SizedBox(
          height: 50,
          child: PrimaryButton(
            text: Language.checkout.capitalizeByWord(),
            onPressed: () {
              Navigator.pushNamed(context, RouteNames.checkoutScreen);
            },
          ),
        )
      ],
    );
  }

  Widget _buildTextField() {
    return TranslateWidget(
      future: Utils.hintText(context, Language.promoCode.capitalizeByWord()),
      hintText: Language.promoCode.capitalizeByWord(),
      builder: (context, snap) {
        return TextFormField(
          controller: textController,
          decoration: InputDecoration(
            hintText: snap,
            // contentPadding: const EdgeInsets.symmetric(horizontal: 8),
            isDense: true,
            suffixIconConstraints:
                const BoxConstraints(maxHeight: 55, maxWidth: 85),
            suffixIcon: _buildSubmit(),
          ),
        );
      },
    );
  }

  Widget _buildSubmit() {
    return Container(
      width: 85,
      height: 54,
      decoration: BoxDecoration(
        color: Utils.dynamicPrimaryColor(context),
        borderRadius: const BorderRadius.horizontal(
          right: Radius.circular(4),
        ),
      ),
      child: InkWell(
        onTap: () {
          // textController.clear();

          if (textController.text.isEmpty) return;
          // Utils.closeKeyBoard(context);
          context.read<CartCubit>().applyCoupon(textController.text.trim());
          textController.clear();
          setState(() {});
        },
        child: Center(
          child: CustomText(
              text: Language.apply.capitalizeByWord(),
              fontSize: 14.0,
              fontWeight: FontWeight.w600,
              color: blackColor),
        ),
      ),
    );
  }
}
