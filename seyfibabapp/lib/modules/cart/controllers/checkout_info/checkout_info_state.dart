part of 'checkout_info_cubit.dart';

sealed class CheckoutInfoState extends Equatable {
  const CheckoutInfoState();
  @override
  List<Object> get props => [];
}

final class CheckoutInfoInitial extends CheckoutInfoState {
  const CheckoutInfoInitial();
}
