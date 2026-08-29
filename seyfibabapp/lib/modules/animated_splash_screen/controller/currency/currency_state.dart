part of 'currency_cubit.dart';

abstract class CurrencyState extends Equatable {
  const CurrencyState();

  @override
  List<Object> get props => [];
}

class CurrencyInitial extends CurrencyState {
  const CurrencyInitial();
}

class CurrenciesLoad extends CurrencyState {
  final List<CurrenciesModel> currencies;

  const CurrenciesLoad(this.currencies);

  @override
  List<Object> get props => [currencies];
}

// class LanguageLoad extends CurrencyState {
//   final List<LanguageListModel> languages;
//
//   const LanguageLoad(this.languages);
//
//   @override
//   List<Object> get props => [languages];
// }
