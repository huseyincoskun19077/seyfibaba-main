part of 'delivery_charges_cubit.dart';

abstract class DeliveryChargesState extends Equatable {
  const DeliveryChargesState();
  @override
  List<Object> get props => [];
}

class DeliveryChargesInitial extends DeliveryChargesState {
  const DeliveryChargesInitial();
}
class DeliveryChargesAdded extends DeliveryChargesState {
  @override
  List<Object> get props => [];
}

