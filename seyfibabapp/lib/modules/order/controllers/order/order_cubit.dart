import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../authentication/controller/login/login_bloc.dart';
import '../../../home/controller/cubit/product/product_state_model.dart';
import '../../../../utils/language_string.dart';
import '../../model/order_model.dart';
import '../../utils/order_display_status.dart';
import '../repository/order_repository.dart';

part 'order_state.dart';

class OrderCubit extends Cubit<ProductStateModel> {
  final LoginBloc _loginBloc;
  final OrderRepository _orderRepository;

  OrderCubit({
    required LoginBloc loginBloc,
    required OrderRepository orderRepository,
  })  : _loginBloc = loginBloc,
        _orderRepository = orderRepository,
        super(ProductStateModel());

  List<OrderModel> orderList = [];
  OrderModel? singleOrder;

  void changeCurrentIndex(int index) {
    emit(state.copyWith(currentIndex: index));
  }

  void tempTrackOrderId(String index) {
    emit(state.copyWith(tempTrackOrderId: index));
  }
  void isFromPayment(bool index) {
    emit(state.copyWith(isFromPayment: index));
  }

  Future<void> getOrderList() async {
    if (_loginBloc.userInfo == null) {
      emit(state.copyWith(
          orderState: OrderStateError(Language.loginRequiredForCheckout, 401)));
      return;
    } else {
      emit(state.copyWith(orderState: const OrderStateLoading()));
      final result = await _orderRepository.orderList(
          state.initialPage.toString(), _loginBloc.userInfo!.accessToken);
      result.fold(
        (failure) {
          final errors = OrderStateError(failure.message, failure.statusCode);
          emit(state.copyWith(orderState: errors));
        },
        (data) {
          if (state.initialPage == 1) {
            orderList = data;
            final loaded = OrderStateLoaded(orderList);
            emit(state.copyWith(orderState: loaded));
          } else {
            // Aynı order_id tekrar gelirse ekleme (çift görünüm engeli)
            final existingIds = orderList.map((o) => o.orderId).toSet();
            for (final order in data) {
              if (!existingIds.contains(order.orderId)) {
                orderList.add(order);
                existingIds.add(order.orderId);
              }
            }
            final loaded = OrderStateMoreLoaded(orderList);
            emit(state.copyWith(orderState: loaded));
          }
          state.initialPage++;
          if (data.isEmpty && state.initialPage != 1) {
            emit(state.copyWith(isListEmpty: true));
          }
          countOrder();
        },
      );
    }
  }

  Future<void> showOrderTracking() async {
    if (_loginBloc.userInfo == null) {
      emit(state.copyWith(
          orderState: OrderStateError(Language.loginRequiredForCheckout, 401)));
      return;
    }
    emit(state.copyWith(orderState: const OrderStateLoading()));
    final result = await _orderRepository.showOrderTracking(
        state.tempTrackOrderId, _loginBloc.userInfo?.accessToken??'');
    result.fold(
      (failure) {
        final error = OrderStateError(failure.message, failure.statusCode);
        emit(state.copyWith(orderState: error));
      },
      (data) {
        singleOrder = data;
        final loadedState = OrderSingleStateLoaded(data);
        emit(state.copyWith(orderState: loadedState));
      },
    );
  }

  /// Returns null on success, error message on failure.
  Future<String?> confirmOrderProductDelivery(int orderProductId) async {
    if (_loginBloc.userInfo == null) {
      return 'Ödeme için giriş yapmanız gerekiyor.';
    }

    final result = await _orderRepository.confirmOrderProductDelivery(
      orderProductId.toString(),
      _loginBloc.userInfo!.accessToken,
    );

    return await result.fold(
      (failure) async => failure.message,
      (message) async {
        await showOrderTracking();
        if (state.initialPage > 1) {
          initPage(isPaginate: false);
        }
        await getOrderList();
        return null;
      },
    );
  }

  List<OrderModel> pending = [];
  List<OrderModel> progress = [];
  List<OrderModel> delivered = [];
  List<OrderModel> completed = [];
  List<OrderModel> declined = [];

  void countOrder() {
    if (orderList.isNotEmpty) {
      pending.clear();
      progress.clear();
      delivered.clear();
      completed.clear();
      declined.clear();
      for (int i = 0; i < orderList.length; i++) {
        final booking = orderList[i];
        switch (OrderDisplayStatusHelper.resolve(booking)) {
          case OrderDisplayStatus.pending:
            pending.add(booking);
          case OrderDisplayStatus.preparing:
            progress.add(booking);
          case OrderDisplayStatus.inCargo:
            delivered.add(booking);
          case OrderDisplayStatus.delivered:
          case OrderDisplayStatus.completed:
            completed.add(booking);
          case OrderDisplayStatus.declined:
            declined.add(booking);
        }
      }
    }
  }

  void initPage({bool isPaginate = true}) {
    if (isPaginate) {
      emit(state.copyWith(initialPage: 1, isListEmpty: false));
    } else {
      emit(state.copyWith(initialPage: 1, orderState: const OrderStateInitial()));
    }
  }
}
