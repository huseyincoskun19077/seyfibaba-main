import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../widgets/app_bar_leading.dart';
import '/utils/language_string.dart';
import '/widgets/page_refresh.dart';
import '../../core/router_name.dart';
import '../../utils/utils.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/please_signin_widget.dart';
import '../home/controller/cubit/product/product_state_model.dart';
import '../home/widgets/home_theme.dart';
import 'component/bottom_tab.dart';
import 'component/empty_order_component.dart';
import 'component/ordered_list_component.dart';
import 'controllers/order/order_cubit.dart';
import 'controllers/order_tracking/order_tracking_cubit.dart';
import 'model/order_model.dart';

class OrderScreen extends StatefulWidget {
  const OrderScreen({super.key, required this.isFromPayment});
  final bool isFromPayment;

  @override
  State<OrderScreen> createState() => _OrderScreenState();
}

class _OrderScreenState extends State<OrderScreen> {
  late OrderCubit orderCubit;
  late OrderTrackingCubit trackingCubit;

  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _initState();
  }

  @override
  void dispose() {
    if (orderCubit.state.initialPage > 1) {
      orderCubit.initPage();
    }
    _scrollController.dispose();
    super.dispose();
  }

  void _initState() {
    orderCubit = context.read<OrderCubit>();
    trackingCubit = context.read<OrderTrackingCubit>();
    orderCubit.isFromPayment(widget.isFromPayment);
    Future.microtask(() => orderCubit.getOrderList());
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollController.position.atEdge) {
      if (_scrollController.position.pixels != 0.0) {
        if (orderCubit.state.isListEmpty == false) {
          orderCubit.getOrderList();
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final orderList = context.read<OrderCubit>();

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.dark.copyWith(
        statusBarColor: Colors.transparent,
      ),
      child: Scaffold(
        backgroundColor: HomeTheme.bg,
        body: PageRefresh(
          onRefresh: () async {
            orderCubit.initPage();
            orderCubit.getOrderList();
          },
          child: MultiBlocListener(
            listeners: [
              BlocListener<OrderTrackingCubit, OrderTrackingState>(
                listener: (context, state) {
                  if (state is OrderStateTrackingLoading) {
                    Utils.loadingDialog(context);
                  } else {
                    Utils.closeDialog(context);
                    if (state is OrderTrackingStateError) {
                      Utils.errorSnackBar(context, state.message);
                    } else if (state is OrderStateTrackingLoaded) {
                      Navigator.pushNamed(
                        context,
                        RouteNames.orderTrackingScreen,
                        arguments: state.singleOrder,
                      );
                    }
                  }
                },
              ),
              BlocListener<OrderCubit, ProductStateModel>(
                listener: (context, productState) {
                  final state = productState.orderState;
                  if (state is OrderStateError) {
                    if (state.statusCode == 503) {
                      orderList.getOrderList();
                    }
                  }
                  if (state is OrderStateLoading &&
                      orderCubit.state.initialPage != 1) {
                    Utils.loadingDialog(context);
                  } else if (state is OrderStateMoreLoaded) {
                    Utils.closeDialog(context);
                  }
                },
              ),
            ],
            child: BlocBuilder<OrderCubit, ProductStateModel>(
              builder: (context, productState) {
                final state = productState.orderState;
                if (state is OrderStateLoading &&
                    orderCubit.state.initialPage == 1) {
                  return const LoadingWidget(color: HomeTheme.brandYellow);
                } else if (state is OrderStateError) {
                  if (state.statusCode == 503) {
                    return OrderLoadedWidget(
                      orderedList: orderList.orderList,
                      controller: _scrollController,
                    );
                  } else if (state.statusCode == 401) {
                    return const PleaseSigninWidget();
                  } else {
                    return FetchErrorText(text: state.message);
                  }
                } else if (state is OrderStateLoaded) {
                  return OrderLoadedWidget(
                    orderedList: state.orderList,
                    controller: _scrollController,
                  );
                } else if (state is OrderStateMoreLoaded) {
                  return OrderLoadedWidget(
                    orderedList: state.orderList,
                    controller: _scrollController,
                  );
                }

                if (orderList.orderList.isNotEmpty) {
                  return OrderLoadedWidget(
                    orderedList: orderList.orderList,
                    controller: _scrollController,
                  );
                } else {
                  if (orderList.orderList.isEmpty) {
                    return const LoadingWidget(color: HomeTheme.brandYellow);
                  } else {
                    return FetchErrorText(text: Language.somethingWentWrong);
                  }
                }
              },
            ),
          ),
        ),
      ),
    );
  }
}

class OrderLoadedWidget extends StatefulWidget {
  const OrderLoadedWidget({
    super.key,
    required this.orderedList,
    required this.controller,
  });

  final List<OrderModel> orderedList;
  final ScrollController controller;

  @override
  State<OrderLoadedWidget> createState() => _OrderLoadedWidgetState();
}

class _OrderLoadedWidgetState extends State<OrderLoadedWidget> {
  late OrderCubit bookingCubit;
  late List<List<OrderModel>> filteredList;

  @override
  void initState() {
    super.initState();
    _init();
  }

  void _init() {
    bookingCubit = context.read<OrderCubit>();
    filteredList = [
      bookingCubit.orderList,
      bookingCubit.pending,
      bookingCubit.progress,
      bookingCubit.delivered,
      bookingCubit.completed,
      bookingCubit.declined,
    ];
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<OrderCubit, ProductStateModel>(
      builder: (context, state) {
        filteredList = [
          bookingCubit.orderList,
          bookingCubit.pending,
          bookingCubit.progress,
          bookingCubit.delivered,
          bookingCubit.completed,
          bookingCubit.declined,
        ];

        final currentItems = filteredList[state.currentIndex];

        return CustomScrollView(
          controller: widget.controller,
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverAppBar(
              pinned: true,
              elevation: 0,
              scrolledUnderElevation: 0,
              backgroundColor: HomeTheme.header,
              surfaceTintColor: Colors.transparent,
              automaticallyImplyLeading: false,
              leading: bookingCubit.state.isFromPayment
                  ? const AppbarLeading()
                  : null,
              toolbarHeight: 56,
              title: Text(
                Language.order,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: HomeTheme.textDark,
                  letterSpacing: -0.3,
                ),
              ),
              bottom: const BottomTab(),
            ),
            if (currentItems.isNotEmpty)
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      return OrderedListComponent(
                        orderedItem: currentItems[index],
                      );
                    },
                    childCount: currentItems.length,
                  ),
                ),
              )
            else
              SliverFillRemaining(
                hasScrollBody: false,
                child: EmptyOrderComponent(tabIndex: state.currentIndex),
              ),
            SliverToBoxAdapter(
              child: SizedBox(
                height: bookingCubit.state.isFromPayment ? 100 : 88,
              ),
            ),
          ],
        );
      },
    );
  }
}
