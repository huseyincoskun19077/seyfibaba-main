import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../widgets/app_bar_leading.dart';
import '../../widgets/capitalized_word.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/please_signin_widget.dart';
import '../home/controller/cubit/product/product_state_model.dart';
import '../home/widgets/home_theme.dart';
import 'component/single_order_details_component.dart';
import 'controllers/order/order_cubit.dart';
import 'model/order_model.dart';
import 'widgets/order_status_timeline.dart';
import 'widgets/order_summary_panel.dart';

class SingleOrderDetails extends StatefulWidget {
  const SingleOrderDetails({super.key});

  @override
  State<SingleOrderDetails> createState() => _SingleOrderDetailsState();
}

class _SingleOrderDetailsState extends State<SingleOrderDetails> {
  late OrderCubit oCubit;

  @override
  void initState() {
    oCubit = context.read<OrderCubit>();
    super.initState();
    Future.microtask(() => oCubit.showOrderTracking());
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.dark.copyWith(
        statusBarColor: Colors.transparent,
      ),
      child: Scaffold(
        backgroundColor: HomeTheme.bg,
        appBar: AppBar(
          elevation: 0,
          scrolledUnderElevation: 0,
          backgroundColor: HomeTheme.header,
          surfaceTintColor: Colors.transparent,
          leading: const AppbarLeading(),
          title: Text(
            Language.singleOrder,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: HomeTheme.textDark,
            ),
          ),
        ),
        body: BlocConsumer<OrderCubit, ProductStateModel>(
          listener: (context, order) {
            final state = order.orderState;
            if (state is OrderStateError) {
              if (state.statusCode == 503 || oCubit.singleOrder == null) {
                oCubit.showOrderTracking();
              }
            }
          },
          builder: (context, order) {
            final state = order.orderState;
            if (state is OrderStateLoading) {
              return const LoadingWidget(color: HomeTheme.brandYellow);
            } else if (state is OrderStateError) {
              if (state.statusCode == 503 || oCubit.singleOrder != null) {
                return LoadedList(singleOrder: oCubit.singleOrder!);
              }
              if (state.statusCode == 401) {
                return const PleaseSigninWidget();
              }
              return FetchErrorText(text: state.message);
            } else if (state is OrderSingleStateLoaded) {
              return LoadedList(singleOrder: state.singleOrder);
            }

            if (oCubit.singleOrder != null) {
              return LoadedList(singleOrder: oCubit.singleOrder!);
            }
            return FetchErrorText(text: Language.somethingWentWrong);
          },
        ),
      ),
    );
  }
}

class LoadedList extends StatelessWidget {
  const LoadedList({super.key, required this.singleOrder});

  final OrderModel singleOrder;

  ({Color bg, Color fg, String label}) _statusStyle() {
    switch ('${singleOrder.orderStatus}') {
      case '0':
        return (
          bg: const Color(0xFFFFF6E5),
          fg: const Color(0xFFB45309),
          label: Language.pending.capitalizeByWord(),
        );
      case '1':
        return (
          bg: const Color(0xFFEFF6FF),
          fg: const Color(0xFF2563EB),
          label: Language.progress.capitalizeByWord(),
        );
      case '2':
        return (
          bg: const Color(0xFFECFDF3),
          fg: greenColor,
          label: Language.delivered.capitalizeByWord(),
        );
      case '3':
        return (
          bg: const Color(0xFFECFDF3),
          fg: deepGreenColor,
          label: Language.completed.capitalizeByWord(),
        );
      default:
        return (
          bg: redColor.withValues(alpha: 0.08),
          fg: redColor,
          label: Language.declined.capitalizeByWord(),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = _statusStyle();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
      children: [
        OrderIdHeader(
          orderId: singleOrder.orderId,
          createdAt: singleOrder.createdAt,
          statusLabel: status.label,
          statusBg: status.bg,
          statusFg: status.fg,
        ),
        const SizedBox(height: 12),
        OrderStatusTimeline(order: singleOrder),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 6),
          decoration: HomeTheme.cardDecoration(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                Language.orderProductsTitle,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: HomeTheme.textDark,
                ),
              ),
              const SizedBox(height: 8),
              ...List.generate(
                singleOrder.orderProducts.length,
                (index) {
                  final item = singleOrder.orderProducts[index];
                  return Column(
                    children: [
                      SingleOrderDetailsComponent(orderItem: item),
                      if (index < singleOrder.orderProducts.length - 1)
                        Divider(
                          height: 20,
                          color: HomeTheme.headerBorder.withValues(alpha: 0.8),
                        ),
                    ],
                  );
                },
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        OrderSummaryPanel(order: singleOrder),
      ],
    );
  }
}
