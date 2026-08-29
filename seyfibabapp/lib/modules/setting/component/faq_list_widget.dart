import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_html/flutter_html.dart';
import '/modules/setting/controllers/faq_cubit/faq_cubit.dart';
import '/widgets/custom_text.dart';

import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../model/faq_model.dart';

class FaqListWidget extends StatefulWidget {
  const FaqListWidget({super.key, required this.faqList});

  final List<FaqModel> faqList;

  @override
  State<FaqListWidget> createState() => _FaqListWidgetState();
}

class _FaqListWidgetState extends State<FaqListWidget> {
  @override
  Widget build(BuildContext context) {
    return SliverList(
      delegate: SliverChildBuilderDelegate((context, index) {
        final faq = widget.faqList[index];
        return BlocBuilder<FaqCubit, FaqModel>(
          builder: (context, state) {
            return Container(
              margin: Utils.symmetric(h: 14.0, v: 4.0),
              decoration: BoxDecoration(
                  color: whiteColor,
                  borderRadius: Utils.borderRadius(r: 6.0),
                  boxShadow: [
                    BoxShadow(
                        offset: const Offset(0.0, 0.0),
                        spreadRadius: 0.0,
                        blurRadius: 0.0,
                        // color: whiteColor
                        color: const Color(0xFF000000).withOpacity(0.4)),
                  ]),
              child: Theme(
                data: Theme.of(context)
                    .copyWith(dividerColor: Colors.transparent),
                child: ExpansionTile(
                  // enabled: state.status == index,
                  title: CustomText(
                      text: faq.question,
                      fontSize: 16.0,
                      fontWeight: FontWeight.w600,
                      color: blackColor),
                  onExpansionChanged: (bool expand) {
                    //debugPrint('isExpand $expand');
                    context.read<FaqCubit>().initExpand(index);
                  },
                  initiallyExpanded: state.status == index,
                  iconColor: blackColor,
                  childrenPadding:
                      Utils.symmetric(v: 0.0, h: 0.0).copyWith(top: 0.0),
                  children: [
                    Html(data: faq.answer),
                  ],
                ),
              ),
            );
          },
        );
      }, childCount: widget.faqList.length),
    );
  }

  ExpansionPanelList buildExpansionPanelList(BuildContext context) {
    return ExpansionPanelList(
      expansionCallback: ((panelIndex, isExpanded) {
        widget.faqList.asMap().forEach((key, value) {
          widget.faqList[key] = widget.faqList[key].copyWith(isExpanded: false);
        });
        widget.faqList[panelIndex] =
            widget.faqList[panelIndex].copyWith(isExpanded: !isExpanded);
        setState(() {});
      }),
      expandedHeaderPadding: EdgeInsets.zero,
      dividerColor: borderColor.withOpacity(.4),
      elevation: 0,
      children: widget.faqList
          .map(
            (e) => ExpansionPanel(
              isExpanded: e.isExpanded,
              backgroundColor: e.isExpanded
                  ? Utils.dynamicPrimaryColor(context).withOpacity(.1)
                  : null,
              canTapOnHeader: true,
              headerBuilder: (_, bool isExpended) => ListTile(
                title: Text(e.question, maxLines: 1),
                // contentPadding: EdgeInsets.zero,
              ),
              body: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Html(data: e.answer),
              ),
            ),
          )
          .toList(),
    );
  }
}
