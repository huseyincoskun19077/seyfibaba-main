import 'package:flutter/material.dart';

import '../../../utils/constants.dart';
import '../../../widgets/custom_radio_button.dart';

class SinglePaymentCardComponent extends StatelessWidget {
  const SinglePaymentCardComponent({
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(border: Border.all(color: borderColor)),
      child: const ListTile(
        horizontalTitleGap: 0,
        leading: CustomRadioButton(isSelected: true),
        title: Row(
          children: [
            Icon(Icons.credit_card_outlined),
            SizedBox(width: 10),
            Flexible(
              child: Text("MasterCard", maxLines: 1),
            ),
          ],
        ),
        subtitle: Text('874 ****** 8372'),
        trailing: CircleAvatar(
          backgroundColor: Color(0xffC1D4DF),
          radius: 13,
          child: Icon(Icons.arrow_forward_ios_rounded,
              size: 16, color: blackColor),
        ),
      ),
    );
  }
}
