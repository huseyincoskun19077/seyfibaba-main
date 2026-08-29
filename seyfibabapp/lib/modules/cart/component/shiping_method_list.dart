import 'package:shop_o/modules/animated_splash_screen/controller/currency/currency_cubit.dart';

import '../../../widgets/custom_text.dart';
import '../../../utils/language_string.dart';
import '../../../widgets/capitalized_word.dart';
import '../model/coupon_response_model.dart';
import '/modules/cart/model/shipping_response_model.dart';
import '../../../state_packages_names.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';

class ShippingMethodList extends StatefulWidget {
  const ShippingMethodList(
      {super.key, required this.shippingMethods, required this.onChange, this.padding});

  final List<ShippingResponseModel> shippingMethods;

  final ValueChanged<int> onChange;
  final EdgeInsets? padding;

  @override
  State<ShippingMethodList> createState() => _ShippingMethodListState();
}

class _ShippingMethodListState extends State<ShippingMethodList> {
  ShippingResponseModel? shippingMethodModel;
  late CheckoutCubit checkout;

  @override
  void initState() {
    super.initState();
    _selectFirstIfNeeded();
  }

  @override
  void didUpdateWidget(ShippingMethodList oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.shippingMethods != widget.shippingMethods) {
      shippingMethodModel = null;
      _selectFirstIfNeeded();
    }
  }

  void _selectFirstIfNeeded() {
    if (widget.shippingMethods.isEmpty || shippingMethodModel != null) return;
    final first = widget.shippingMethods.first;
    shippingMethodModel = first;
    checkout = context.read<CheckoutCubit>();
    checkout.shippingFee(first.shippingFee);
    context.read<AddressCubit>().addShippingId(first.id);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      widget.onChange(first.id);
    });
  }

  int currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: widget.padding ?? Utils.symmetric(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 20),
          CustomText(
              text: Language.shippingCharge.capitalizeByWord(),
              fontSize: 16,
              fontWeight: FontWeight.w600),
          const SizedBox(height: 10),
          ...widget.shippingMethods.map(
                (e) {
              final isSelected = e == shippingMethodModel;
              return Container(
                margin: Utils.only(bottom: 8.0),
                decoration: BoxDecoration(
                    color: whiteColor,
                    borderRadius: Utils.borderRadius(r: 12.0),
                    border: Border.all(
                        color: isSelected ? greenColor : transparent),
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
                  shape: RoundedRectangleBorder(
                      borderRadius: Utils.borderRadius(r: 12.0)),
                  onTap: () {
                    // _initMethods();
                    setState(() {
                      shippingMethodModel = e;
                      widget.onChange(e.id);
                    });
                    debugPrint('iddddd ${e.id}');
                    context.read<AddressCubit>().addShippingId(e.id) ;
                    context.read<CheckoutCubit>().shippingFee(e.shippingFee);
                  },
                  horizontalTitleGap: 0,
                  title: CustomText(
                      text:
                          "${Language.fee.capitalizeByWord()}: ${Utils.formatPrice(e.shippingFee, context)}",
                      isTranslate: false),
                  //subtitle: Text(e.shippingRule),
                  subtitle: CustomText(text: e.shippingRule),
                  // subtitle: CustomText(text: Utils.shippingType(e.type)),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

class ShippingPerKM extends StatelessWidget {
  const ShippingPerKM({super.key,this.margin});
  final EdgeInsets ? margin;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CheckoutCubit, CouponResponseModel>(
      builder: (context, state) {
        // debugPrint('state.distancePrice ${state.distancePrice}');
        return Container(
          margin: margin ?? Utils.only(top: 10.0),
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
            shape: RoundedRectangleBorder(
                borderRadius: Utils.borderRadius(r: 12.0)),
            onTap: () {},
            horizontalTitleGap: 0,
            title: CustomText(
                text:
                    "${Language.fee.capitalizeByWord()}: ${Utils.formatPrice(state.distancePrice, context)}",
                isTranslate: false),
            subtitle: CustomText(
              text: Language.basedOnDistance.capitalizeByWord(),
            ),
          ),
        );
      },
    );
  }
}

class NewShippingMethod extends StatefulWidget {
  const NewShippingMethod({super.key});

  @override
  State<NewShippingMethod> createState() => _NewShippingMethodState();
}

class _NewShippingMethodState extends State<NewShippingMethod> {
  late CheckoutCubit checkout;
  late AddressCubit addressCubit;

  @override
  void initState() {
    super.initState();
    _initMethods();
  }

  _initMethods(){
    checkout = context.read<CheckoutCubit>();
    addressCubit = context.read<AddressCubit>();
  }
  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CheckoutCubit,CouponResponseModel>(
        builder: (context,state){
          // debugPrint('state.shippings ${state.shippings}');
      return Padding(
            padding: Utils.symmetric(h: 16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Utils.verticalSpace(10.0),
                const CustomText(text: 'Kargo Ücretleri',fontWeight: FontWeight.w600,fontSize: 16.0),
                if(state.shippings.isNotEmpty)...[
                  ...List.generate(state.shippings.length, (index){
                    final item = state.shippings[index];
                    final active = state.currentIndex == index;
                    return Container(
                      margin: Utils.symmetric(v: 8.0,h: 0.0),
                      decoration: BoxDecoration(
                          color: whiteColor,
                          borderRadius: Utils.borderRadius(r: 12.0),
                          // border: Border.all(color:  greenColor ),
                          border: Border.all(color: active ? greenColor : transparent),
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
                        shape: RoundedRectangleBorder(
                            borderRadius: Utils.borderRadius(r: 12.0)),
                        onTap: () {
                          // _initMethods();
                          // setState(() {
                          //   shippingMethodModel = e;
                          //   widget.onChange(e.id);
                          // });
                          addressCubit.addShippingId(item.id) ;
                          //shippingFee(item.shippingFee)
                          checkout..addIndex(index)..addCheckoutShipping(item.shippingFee);
                          // debugPrint('price $item');
                    },
                        horizontalTitleGap: 0,
                        title: CustomText(
                            text:
                                "${Language.fee.capitalizeByWord()}: ${Utils.formatPrice(item.shippingFee, context)}",
                            isTranslate: false),
                        //subtitle: Text(e.shippingRule),
                        subtitle: CustomText(text: item.shippingRule),
                        // subtitle: CustomText(text: Utils.shippingType(e.type)),
                      ),
                    );
                  })
                ]
              ],
            ),
          );
        }
    );
  }
}
