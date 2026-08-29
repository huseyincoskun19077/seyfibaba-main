import 'dart:convert';

import 'package:equatable/equatable.dart';

import '../../../setting/model/currencies_model.dart';
import '../../../setting/model/language_model.dart';
import 'currency_cubit.dart';

class CurrencyStateModel extends Equatable {
  final List<CurrenciesModel> currencies;
  final List<LanguageModel> languages;
  final CurrencyState currencyState;

  const CurrencyStateModel({
    required this.currencies,
    required this.languages,
    this.currencyState = const CurrencyInitial(),
  });

  CurrencyStateModel copyWith({
    List<CurrenciesModel>? currencies,
    List<LanguageModel>? languages,
    CurrencyState? currencyState,
  }) {
    return CurrencyStateModel(
      currencies: currencies ?? this.currencies,
      languages: languages ?? this.languages,
      currencyState: currencyState ?? this.currencyState,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'currencies': currencies.map((x) => x.toMap()).toList(),
      'languages': languages.map((x) => x.toMap()).toList(),
    };
  }

  factory CurrencyStateModel.fromMap(Map<String, dynamic> map) {
    return CurrencyStateModel(
      currencies: List<CurrenciesModel>.from(
        (map['currencies'] as List<dynamic>).map<CurrenciesModel>(
              (x) => CurrenciesModel.fromMap(x as Map<String, dynamic>),
        ),
      ),
      languages: List<LanguageModel>.from(
        (map['languages'] as List<dynamic>).map<LanguageModel>(
              (x) => LanguageModel.fromMap(x as Map<String, dynamic>),
        ),
      ),
    );
  }

  String toJson() => json.encode(toMap());

  factory CurrencyStateModel.fromJson(String source) =>
      CurrencyStateModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  static CurrencyStateModel init() {
    return const CurrencyStateModel(
      currencies: <CurrenciesModel>[],
      languages: <LanguageModel>[],
      currencyState: CurrencyInitial(),
    );
  }

  @override
  List<Object> get props => [currencies, languages, currencyState];
}
