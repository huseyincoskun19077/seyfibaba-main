part of 'iyzico_cubit.dart';

abstract class IyzicoState extends Equatable {
  const IyzicoState();

  @override
  List<Object> get props => [];
}

class IyzicoInitialState extends IyzicoState {
  const IyzicoInitialState();
}

class IyzicoLoadingState extends IyzicoState {
  const IyzicoLoadingState();
}

class IyzicoErrorState extends IyzicoState {
  const IyzicoErrorState(this.message, this.statusCode);

  final String message;
  final int statusCode;

  @override
  List<Object> get props => [message, statusCode];
}

class IyzicoLoadedState extends IyzicoState {
  const IyzicoLoadedState({
    required this.checkoutUrl,
    required this.orderId,
  });

  final String checkoutUrl;
  final String orderId;

  @override
  List<Object> get props => [checkoutUrl, orderId];
}
