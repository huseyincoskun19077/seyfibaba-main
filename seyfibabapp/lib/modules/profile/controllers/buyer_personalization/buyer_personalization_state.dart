part of 'buyer_personalization_cubit.dart';

class BuyerPersonalizationState extends Equatable {
  final BuyerPersonalizationData data;
  final bool isLoading;
  final String? errorMessage;

  const BuyerPersonalizationState({
    required this.data,
    this.isLoading = false,
    this.errorMessage,
  });

  BuyerPersonalizationState copyWith({
    BuyerPersonalizationData? data,
    bool? isLoading,
    String? errorMessage,
  }) {
    return BuyerPersonalizationState(
      data: data ?? this.data,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: errorMessage,
    );
  }

  @override
  List<Object?> get props => [data, isLoading, errorMessage];
}
