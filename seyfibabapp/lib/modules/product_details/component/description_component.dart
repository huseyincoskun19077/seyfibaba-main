import 'package:flutter/material.dart';
import 'package:shop_o/utils/constants.dart';
import '/widgets/custom_text.dart';

import '../../../utils/utils.dart';

class DescriptionComponent extends StatelessWidget {
  const DescriptionComponent(this.description, {super.key});

  final String description;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [

          // TranslatableHtmlWidget(description: description,langCode: context.read<LoginBloc>().state.langCode),

          // HtmlWidget(description),
          // Html(data: description),
          CustomText(text: Utils.htmlTextConverter(description),maxLine: 200,color: iconGreyColor),
          const SizedBox(height: 14),
        ],
      ),
    );
  }
}

