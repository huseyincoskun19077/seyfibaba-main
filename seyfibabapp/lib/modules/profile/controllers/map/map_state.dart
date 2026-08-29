part of 'map_cubit.dart';

sealed class MapState extends Equatable {
  const MapState();

  @override
  List<Object> get props => [];
}

final class MapInitial extends MapState {
  const MapInitial();
}

class RefreshStateEveryFive extends MapState {
  const RefreshStateEveryFive();
}

class DeliveryLoading extends MapState {
  const DeliveryLoading();
}

class DeliveryError extends MapState {
  const DeliveryError(this.message, this.statusCode);
  final String message;
  final int statusCode;

  @override
  List<Object> get props => [message, statusCode];
}

class DeliveryLoaded extends MapState {
  final DeliveryManModel delivery;
  const DeliveryLoaded(this.delivery);

  @override
  List<Object> get props => [delivery];
}
