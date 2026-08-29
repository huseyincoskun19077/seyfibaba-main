import 'package:equatable/equatable.dart';

import '../../../../utils/language_string.dart';
import 'translate_cubit.dart';

class TranslateStateModel extends Equatable {
  final String text;
  final Map<String, String> translations;
  final Map<String, String> bottomText;
  final bool loading;
  final bool isRebuild;
  final String langCode;
  final List<String> translateText;
  final TranslateState tState;

  const TranslateStateModel({
    required this.text,
    required this.translations,
    required this.bottomText,
    required this.tState,
    required this.loading,
    required this.isRebuild,
    required this.translateText,
    required this.langCode,
  });

  TranslateStateModel copyWith({
    String? text,
    Map<String, String>? translations,
    Map<String, String>? bottomText,
    String? langCode,
    List<String>? translateText,
    TranslateState? tState,
    bool? loading,
    bool? isRebuild,
  }) {
    return TranslateStateModel(
      text: text ?? this.text,
      translations: translations ?? this.translations,
      bottomText: bottomText ?? this.bottomText,
      langCode: langCode ?? this.langCode,
      tState: tState ?? this.tState,
      isRebuild: isRebuild ?? this.isRebuild,
      translateText: translateText ?? this.translateText,
      loading: loading ?? this.loading,
    );
  }

  static TranslateStateModel init() {
    return const TranslateStateModel(
      text: '',
      translations: {},
      bottomText: {
        'home': 'Ana Sayfa',
        'inbox': 'İkinci El',
        'order': 'Siparişler',
        'profile': 'Profilim',
        'Promo Code': 'Promosyon Kodu',
        'Pending': 'Beklemede',
        'Progress': 'Hazırlanıyor',
        'Delivered': 'Teslim Edildi',
        'Choose your Location': 'Konumunuzu Seçin',
        'Search Location': 'Konum Ara',
        'Type Here': 'Buraya Yazın',
        'Search Products': 'Ürün Ara',
        'Days': 'Gün',
        'Hours': 'Saat',
        'Minutes': 'Dakika',
        'Seconds': 'Saniye',
        'Password': 'Şifre',
        'Shop Name': 'Mağaza Adı',
        'Phone Number': 'Telefon Numarası',
        'Address': 'Adres',
        'Open At': 'Açılış',
        'Close At': 'Kapanış',
        'Email': 'E-posta',
      },
      langCode: 'tr',
      loading: true,
      isRebuild: true,
      translateText: <String>[],
      tState: TranslateInitial(),
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{'text': text};
  }

  @override
  List<Object?> get props =>
      [text, langCode, loading, isRebuild, bottomText, translateText, tState];
}
