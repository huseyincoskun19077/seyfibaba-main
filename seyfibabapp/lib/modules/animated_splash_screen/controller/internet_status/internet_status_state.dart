part of 'internet_status_bloc.dart';

abstract class InternetStatusState extends Equatable {
  const InternetStatusState();

  @override
  List<Object> get props => [];
}

class InternetStatusInitial extends InternetStatusState {}

class InternetStatusBackState extends InternetStatusState {
  final String message;

  const InternetStatusBackState(this.message);

  @override
  List<Object> get props => [message];
}

class InternetStatusLostState extends InternetStatusState {
  final String message;

  const InternetStatusLostState(this.message);

  @override
  List<Object> get props => [message];
}
