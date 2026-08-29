import 'package:bloc/bloc.dart';
import 'package:equatable/equatable.dart';

import '../../model/faq_model.dart';
import '../repository/setting_repository.dart';

part 'faq_cubit_state.dart';

class FaqCubit extends Cubit<FaqModel> {
  FaqCubit(this.repository) : super(FaqModel.init());

  final SettingRepository repository;
  List<FaqModel> faqList = [];

  void initExpand(int index) {
    emit(state.copyWith(status: index));
  }

  Future<void> getFaqList() async {
    emit(state.copyWith(state: const FaqCubitStateLoading()));

    final result = await repository.getFaqList();
    result.fold(
      (failure) {
        emit(state.copyWith(
            state: FaqCubitStateError(
                errorMessage: failure.message, status: failure.statusCode)));
      },
      (data) {
        faqList = data;
        emit(state.copyWith(state: FaqCubitStateLoaded(faqList: data)));
      },
    );
  }
}
