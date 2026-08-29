part of 'faq_cubit.dart';

abstract class FaqCubitState extends Equatable {
  const FaqCubitState();

  @override
  List<Object> get props => [];
}

class FaqCubitStateInitial extends FaqCubitState {
  const FaqCubitStateInitial();
}
class FaqCubitStateLoading extends FaqCubitState {
  const FaqCubitStateLoading();
}

class FaqCubitStateError extends FaqCubitState {
  final String errorMessage;
  final int status;
  const FaqCubitStateError({
    required this.errorMessage,
    required this.status,
  });

  @override
  List<Object> get props => [errorMessage,status];
}

class FaqCubitStateLoaded extends FaqCubitState {
  final List<FaqModel> faqList;
  const FaqCubitStateLoaded({required this.faqList});

  // FaqCubitStateLoaded copyWith({List<FaqModel>? faqList}) {
  //   return FaqCubitStateLoaded(faqList: faqList ?? this.faqList);
  // }

  @override
  List<Object> get props => [faqList];
}
