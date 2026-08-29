import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../setting/model/currencies_model.dart';
import '../../../setting/model/language_model.dart';
import 'currency_state_model.dart';

part 'currency_state.dart';

class CurrencyCubit extends Cubit<CurrencyStateModel> {
  CurrencyCubit() : super(CurrencyStateModel.init());

  void addNewCurrency(CurrenciesModel newCurrency) {
    final updatedCurrencies = List.of(state.currencies)..add(newCurrency);
    emit(state.copyWith(currencies: updatedCurrencies));
  }

  void addNewLanguage(LanguageModel newLanguage) {
    final updatedCurrencies = List<LanguageModel>.from(state.languages)
      ..add(newLanguage);
    emit(state.copyWith(languages: updatedCurrencies));
  }

  void resetList(bool isLanguage){
    if(isLanguage == true){
      emit(state.copyWith(languages: <LanguageModel>[]));
    }else{
      //emit(state.copyWith(currencies: <CurrenciesModel>[],languages: <LanguageModel>[]));
    }
  }
}
