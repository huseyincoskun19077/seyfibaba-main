part of 'notification_cubit.dart';

abstract class NotificationState extends Equatable {
  const NotificationState();

  @override
  List<Object> get props => [];
}

class NotificationInitial extends NotificationState {
  const NotificationInitial();
}

class NotificationLoading extends NotificationState {}

// ignore: must_be_immutable
class NotificationLoaded extends NotificationState {
  dynamic success;

  NotificationLoaded(this.success);

  @override
  List<Object> get props => [success];
}

class NotificationError extends NotificationState {
  final String message;
  final int statusCode;

  const NotificationError(this.message, this.statusCode);

  @override
  List<Object> get props => [message, statusCode];
}

class NotificationUnread extends NotificationState {
  const NotificationUnread(this.unreadCount, this.totalCount);

  final int unreadCount;
  final int totalCount;

  @override
  List<Object> get props => [unreadCount, totalCount];
}
