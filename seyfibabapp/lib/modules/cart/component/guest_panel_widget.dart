import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';


import '../../product_details/controller/cubit/details_state_model.dart';
import '../../product_details/controller/cubit/product_details_cubit.dart';
import '../../profile/controllers/map/map_cubit.dart';
import '/widgets/capitalized_word.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_text.dart';
import '../../../widgets/primary_button.dart';
import '../../../widgets/translate_form_text.dart';
import '../../profile/controllers/address/address_cubit.dart';

class GuestPanelCollapseComponent extends StatelessWidget {
  const GuestPanelCollapseComponent({super.key, required this.height});

  final double height;

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
            height: 4,
            width: 60,
          ),
          // const SizedBox(height: 9),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              CustomText(
                  text: Language.orderAmount.capitalizeByWord(),
                  fontSize: 16.0,
                  fontWeight: FontWeight.w600),
              BlocBuilder<ProductDetailsCubit, DetailsStateModel>(
                builder: (context, state) {
                  return Text(
                    Utils.formatPrice(state.cartPrice, context),
                    style: GoogleFonts.inter(
                        fontSize: 16.0, fontWeight: FontWeight.w600),
                  );
                },
              ),
            ],
          ),
          const SizedBox(height: 5),
          GestureDetector(
            onTap: () {
              context.read<AddressCubit>().clearAddressInfo();
              context.read<MapCubit>().clear();

              if (Utils.isLoggedIn(context)) {
                Navigator.pushNamed(context, RouteNames.cartScreen);
              } else {
                Utils.errorSnackBar(
                  context,
                  Language.guestCheckoutDisabled,
                );
                Navigator.pushNamed(context, RouteNames.authenticationScreen);
              }
            },
            child: SizedBox(
              height: 50.0,
              child: PrimaryButton(
                text: Language.checkout.capitalizeByWord(),
                onPressed: () {
                  context.read<AddressCubit>().clearAddressInfo();
                  context.read<MapCubit>().clear();

                  if (Utils.isLoggedIn(context)) {
                    Navigator.pushNamed(context, RouteNames.guestCheckoutScreen);
                  } else {
                    Utils.errorSnackBar(
                      context,
                      Language.guestCheckoutDisabled,
                    );
                    Navigator.pushNamed(context, RouteNames.authenticationScreen);
                  }
                },
              ),
            ),
          )
        ],
      ),
    );
  }
}

class GuestPanelComponent extends StatefulWidget {
  const GuestPanelComponent({super.key, this.controller});

  final ScrollController? controller;

  @override
  State<GuestPanelComponent> createState() => _GuestPanelComponentState();
}

class _GuestPanelComponentState extends State<GuestPanelComponent> {
  final textController = TextEditingController();

  late ProductDetailsCubit detailCubit;

  @override
  void initState() {
    detailCubit = context.read<ProductDetailsCubit>();
    super.initState();
  }

  @override
  void dispose() {
    super.dispose();
    textController.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<ProductDetailsCubit,DetailsStateModel>(
      listener: (context,detail){
        final state = detail.detailsState;
       if(state is CouponApplying){
         Utils.loadingDialog(context);
       }else{
         Utils.closeDialog(context);
         if (state is CouponApplyError) {
           Utils.errorSnackBar(context, state.message);

         } else if (state is CouponApplied) {
           textController.clear();
           detailCubit.couponCalculate();
         }
       }
      },
      builder: (context,state){
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
                Text(
                  Utils.formatPrice(state.cartPrice, context),
                  style: GoogleFonts.inter(
                      fontSize: 16.0,
                      fontWeight: FontWeight.w600
                  ),
                )
              ],
            ),
           if(Utils.isLoggedIn(context))...[
             const SizedBox(height: 8.0),
             _buildTextField(),
             const SizedBox(height: 8.0),
             Row(
               mainAxisAlignment: MainAxisAlignment.spaceBetween,
               children: [
                 CustomText(
                     text: Language.discountCoupon.capitalizeByWord(),
                     fontSize: 16.0,
                     color: redColor),

                 Text(
                   Utils.formatPrice(state.couponPrice, context),
                   style: GoogleFonts.inter(
                       fontSize: 16.0,
                       fontWeight: FontWeight.w600
                   ),
                 )
                 // BlocConsumer<CartCubit, CartState>(
                 //   listener: (context, state) {
                 //     if (state is CartStateDecIncrementLoading) {
                 //       Utils.loadingDialog(context);
                 //     } else {
                 //       Utils.closeDialog(context);
                 //     }
                 //   },
                 //   builder: (context, state) {
                 //     if (state is CartStateError) {
                 //       return FetchErrorText(text: state.message);
                 //     }
                 //     if (state is CartCouponStateLoaded) {
                 //       widget.cartCalculation!.copyWith(
                 //           total: (widget.cartCalculation!.total -
                 //               double.parse(
                 //                   state.couponResponseModel.discount.toString())));
                 //
                 //       return CustomText(
                 //           isTranslate: false,
                 //           text: Utils.formatPrice(
                 //               state.couponResponseModel.discount, context),
                 //           fontSize: 16,
                 //           color: redColor);
                 //     }
                 //     return const SizedBox();
                 //   },
                 // ),
               ],
             ),
           ]else...[],
            Container(margin: Utils.symmetric(h: 0.0,v: 10.0).copyWith(top: 8.0), height: 1, color: borderColor),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                CustomText(
                    text: Language.total.capitalizeByWord(),
                    fontSize: 16.0,
                    fontWeight: FontWeight.w600),
                Text(
                  Utils.formatPrice(state.priceAfterCoupon != 0.0?state.priceAfterCoupon: state.cartPrice, context),
                  style: GoogleFonts.inter(
                      fontSize: 16.0,
                      fontWeight: FontWeight.w600
                  ),
                )
              ],
            ),
            const SizedBox(height: 14),
            SizedBox(
              height: 50,
              child: PrimaryButton(
                text: Language.checkout.capitalizeByWord(),
                onPressed: () {
                  // final body = detailCubit.savedProduct.map((e)=>e.toMap()).toList();
                  // log('cart-body $body');

                  context.read<AddressCubit>().clearAddressInfo();
                  context.read<MapCubit>().clear();

                  if(Utils.isLoggedIn(context)){

                    Navigator.pushNamed(context, RouteNames.guestCheckoutScreen);
                  }else{
                    Navigator.pushNamed(context, RouteNames.guestAddressScreen);
                  }
                },
              ),
            )
          ],
        );
      },
    );
  }

  Widget _buildTextField() {
    return TranslateWidget(
      future: Utils.hintText(context, Language.promoCode.capitalizeByWord()),
      hintText: detailCubit.couponResponse?.code.isNotEmpty??false?detailCubit.couponResponse?.code?? Language.promoCode.capitalizeByWord(): Language.promoCode.capitalizeByWord(),
      builder: (context, snap) {
        return TextFormField(
          enabled: detailCubit.couponResponse == null,
          controller: textController,
          decoration: InputDecoration(
            hintText: snap,
            // contentPadding: const EdgeInsets.symmetric(horizontal: 8),
            isDense: true,
            suffixIconConstraints: const BoxConstraints(maxHeight: 55, maxWidth: 85),
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

          if (textController.text.trim().isEmpty) return;

          detailCubit.applyCoupon(textController.text.trim());

          // context.read<CartCubit>().applyCoupon(textController.text.trim());
          // textController.clear();
          // setState(() {});
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
