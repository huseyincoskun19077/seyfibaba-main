import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/widgets/page_refresh.dart';
import '../../widgets/custom_text.dart';
import '/widgets/fetch_error_text.dart';
import '/widgets/loading_widget.dart';

import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../widgets/app_bar_leading.dart';
import 'component/faq_app_bar.dart';
import 'component/faq_list_widget.dart';
import 'controllers/faq_cubit/faq_cubit.dart';
import 'model/faq_model.dart';

class FaqScreen extends StatefulWidget {
  const FaqScreen({super.key});

  @override
  State<FaqScreen> createState() => _FaqScreenState();
}

class _FaqScreenState extends State<FaqScreen> {
  late FaqCubit faqCubit;

  @override
  void initState() {
    faqCubit = context.read<FaqCubit>();
    Future.microtask(() => faqCubit.getFaqList());
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: scaBgColor,
      body: PageRefresh(
        onRefresh: () async => faqCubit.getFaqList(),
        child: BlocConsumer<FaqCubit, FaqModel>(
          listener: (context, states) {
            final state = states.state;
            if (state is FaqCubitStateError) {
              if (state.status == 503) {
                faqCubit.getFaqList();
              }
            }
          },
          builder: (context, states) {
            final state = states.state;
            if (state is FaqCubitStateLoading) {
              return const LoadingWidget();
            } else if (state is FaqCubitStateLoaded) {
              return LoadedFaqContent(faqList: state.faqList);
            } else if (state is FaqCubitStateError) {
              if (state.status == 503) {
                return LoadedFaqContent(faqList: faqCubit.faqList);
              } else {
                return FetchErrorText(text: state.errorMessage);
              }
            }
            if (faqCubit.faqList.isNotEmpty) {
              return LoadedFaqContent(faqList: faqCubit.faqList);
            } else {
              return FetchErrorText(text: Language.somethingWentWrong);
            }
          },
        ),
      ),
    );
  }
}

class LoadedFaqContent extends StatelessWidget {
  const LoadedFaqContent({super.key, required this.faqList});

  final List<FaqModel> faqList;

  @override
  Widget build(BuildContext context) {
    const double appBarHeight = 120;
    return CustomScrollView(slivers: [
      SliverAppBar(
        expandedHeight: appBarHeight,
        systemOverlayStyle: const SystemUiOverlayStyle(statusBarColor: blackColor),
        title: CustomText(text:
          Language.faq,
         color: whiteColor,
          fontSize: 18.0,
          fontWeight: FontWeight.w700,
        ),
        titleSpacing: 0,
        leading: const AppbarLeading(),
        // leading: const AppbarLeading(color: primaryColor,),
        flexibleSpace: const FaqAppBar(height: appBarHeight),
      ),
      const SliverToBoxAdapter(child: SizedBox(height: 30)),
      FaqListWidget(faqList: faqList),
    ]);
  }
}
