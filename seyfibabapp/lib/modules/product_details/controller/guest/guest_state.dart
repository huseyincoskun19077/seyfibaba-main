part of 'guest_cubit.dart';

sealed class GuestState extends Equatable {
  const GuestState();
  @override
  List<Object> get props => [];
}

final class GuestInitial extends GuestState {
const GuestInitial();
}
