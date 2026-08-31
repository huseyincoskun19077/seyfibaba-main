import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../model/buyer_personalization_data.dart';
import '../repository/profile_repository.dart';
import '../../../authentication/controller/login/login_bloc.dart';

part 'buyer_personalization_state.dart';

class BuyerPersonalizationCubit extends Cubit<BuyerPersonalizationState> {
  BuyerPersonalizationCubit({
    required ProfileRepository profileRepository,
    required LoginBloc loginBloc,
    BuyerPersonalizationData? initial,
  })  : _profileRepository = profileRepository,
        _loginBloc = loginBloc,
        super(BuyerPersonalizationState(data: initial ?? const BuyerPersonalizationData()));

  final ProfileRepository _profileRepository;
  final LoginBloc _loginBloc;

  void setShopName(String value) {
    emit(state.copyWith(data: state.data.copyWith(shopName: value)));
  }

  void setBusinessType(String value) {
    emit(state.copyWith(
      data: state.data.copyWith(
        businessType: value,
        businessTypeOther: value == 'other' ? state.data.businessTypeOther : '',
      ),
    ));
  }

  void setBusinessTypeOther(String value) {
    emit(state.copyWith(data: state.data.copyWith(businessTypeOther: value)));
  }

  void setBusinessStatus(String value) {
    emit(state.copyWith(data: state.data.copyWith(businessStatus: value)));
  }

  Future<bool> submit() async {
    final token = _loginBloc.userInfo?.accessToken;
    if (token == null || token.isEmpty) return false;

    if (state.data.businessType.isEmpty || state.data.businessStatus.isEmpty) {
      emit(state.copyWith(errorMessage: 'Lütfen zorunlu alanları doldurun'));
      return false;
    }
    if (state.data.businessType == 'other' &&
        state.data.businessTypeOther.trim().isEmpty) {
      emit(state.copyWith(errorMessage: 'Lütfen alanınızı yazın'));
      return false;
    }

    emit(state.copyWith(isLoading: true, errorMessage: null));
    final result = await _profileRepository.updateBuyerPersonalization(
      token,
      state.data.toSubmitMap(),
    );

    return result.fold(
      (failure) {
        emit(state.copyWith(isLoading: false, errorMessage: failure.message));
        return false;
      },
      (_) {
        emit(state.copyWith(
          isLoading: false,
          data: state.data.copyWith(
            personalizationCompletedAt: DateTime.now(),
            personalizationSkippedAt: null,
            shouldShowPersonalization: false,
          ),
        ));
        return true;
      },
    );
  }

  Future<bool> skip() async {
    final token = _loginBloc.userInfo?.accessToken;
    if (token == null || token.isEmpty) return false;

    emit(state.copyWith(isLoading: true, errorMessage: null));
    final result = await _profileRepository.skipBuyerPersonalization(token);

    return result.fold(
      (failure) {
        emit(state.copyWith(isLoading: false, errorMessage: failure.message));
        return false;
      },
      (_) {
        emit(state.copyWith(
          isLoading: false,
          data: state.data.copyWith(
            personalizationSkippedAt: DateTime.now(),
            shouldShowPersonalization: false,
          ),
        ));
        return true;
      },
    );
  }
}

extension _BuyerPersonalizationDataCopy on BuyerPersonalizationData {
  BuyerPersonalizationData copyWith({
    String? shopName,
    String? businessType,
    String? businessTypeOther,
    String? businessStatus,
    DateTime? personalizationCompletedAt,
    DateTime? personalizationSkippedAt,
    bool? shouldShowPersonalization,
  }) {
    return BuyerPersonalizationData(
      shopName: shopName ?? this.shopName,
      businessType: businessType ?? this.businessType,
      businessTypeOther: businessTypeOther ?? this.businessTypeOther,
      businessStatus: businessStatus ?? this.businessStatus,
      personalizationCompletedAt:
          personalizationCompletedAt ?? this.personalizationCompletedAt,
      personalizationSkippedAt:
          personalizationSkippedAt ?? this.personalizationSkippedAt,
      shouldShowPersonalization:
          shouldShowPersonalization ?? this.shouldShowPersonalization,
    );
  }
}
