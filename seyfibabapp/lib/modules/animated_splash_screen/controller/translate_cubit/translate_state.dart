part of 'translate_cubit.dart';

sealed class TranslateState extends Equatable {
  const TranslateState();

  @override
  List<Object> get props => [];
}

final class TranslateInitial extends TranslateState {
  const TranslateInitial();

  @override
  List<Object> get props => [];
}

final class TranslateLoading extends TranslateState {}

final class TranslateLoaded extends TranslateState {}
