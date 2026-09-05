import 'package:another_stepper/another_stepper.dart';
import 'package:flutter/material.dart';

import '../../utils/language_string.dart';
import '../../utils/constants.dart';
import '../../utils/k_images.dart';
import '../../widgets/custom_image.dart';
import '../../widgets/rounded_app_bar.dart';
import 'model/order_model.dart';
import 'utils/order_display_status.dart';

class OrderTrackingScreen extends StatelessWidget {
  const OrderTrackingScreen({super.key, required this.orders});

  final OrderModel orders;

  @override
  Widget build(BuildContext context) {
    final display = OrderDisplayStatusHelper.resolve(orders);
    final activeIndex =
        OrderDisplayStatusHelper.stepIndex(display).clamp(0, 3);
    final fullyDone = OrderDisplayStatusHelper.isFullyCompleted(display);

    List<StepperData> steppers = [
      StepperData(
        title: StepperText('Sipariş alındı'),
        iconWidget: _buildDot(
          activeColor: fullyDone || activeIndex >= 0 ? greenColor : grayColor,
        ),
      ),
      StepperData(
        title: StepperText('Hazırlanıyor'),
        iconWidget: _buildDot(
          activeColor: fullyDone || activeIndex >= 1 ? greenColor : grayColor,
        ),
      ),
      StepperData(
        title: StepperText('Kargoda'),
        iconWidget: _buildDot(
          activeColor: fullyDone || activeIndex >= 2 ? greenColor : grayColor,
        ),
      ),
      StepperData(
        title: StepperText('Teslim'),
        iconWidget: _buildDot(
          activeColor: fullyDone || activeIndex >= 3 ? greenColor : grayColor,
        ),
      ),
    ];

    return Scaffold(
      appBar: RoundedAppBar(titleText: Language.singleOrder),
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 20.0),
        shrinkWrap: true,
        children: [
          display == OrderDisplayStatus.declined
              ? declinedOrderWidget(context)
              : Container(
                  padding: const EdgeInsets.symmetric(horizontal: 20.0),
                  decoration: BoxDecoration(
                      color: const Color(0xFFCBECFF),
                      borderRadius: BorderRadius.circular(10.0)),
                  child: AnotherStepper(
                    stepperList: steppers,
                    stepperDirection: Axis.vertical,
                    verticalGap: 40.0,
                    activeIndex: activeIndex,
                    activeBarColor: greenColor,
                    inActiveBarColor: primaryColor,
                    barThickness: 6.0,
                  ),
                ),
        ],
      ),
    );
  }

  Widget declinedOrderWidget(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    return Container(
      height: size.height * 0.7,
      width: size.width,
      alignment: Alignment.center,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            Language.orderIsDeclined,
            style: TextStyle(
                fontSize: 20.0, fontWeight: FontWeight.w600, color: redColor),
          ),
          const SizedBox(height: 20.0),
          CustomImage(path: Kimages.orderDeclined)
        ],
      ),
    );
  }
}

Widget _buildDot({Color activeColor = greenColor}) {
  return Container(
    height: 30.0,
    width: 30.0,
    padding: const EdgeInsets.all(3.0),
    decoration: BoxDecoration(
      border: Border.all(color: activeColor),
      shape: BoxShape.circle,
    ),
    child: DecoratedBox(
      decoration: BoxDecoration(
        color: activeColor,
        shape: BoxShape.circle,
      ),
    ),
  );
}
