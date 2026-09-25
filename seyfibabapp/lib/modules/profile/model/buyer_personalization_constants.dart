class BuyerBusinessType {
  static const femaleHairdresser = 'female_hairdresser';
  static const maleHairdresser = 'male_hairdresser';
  static const barber = 'barber';
  static const beautySalon = 'beauty_salon';
  static const nailArt = 'nail_art';
  static const other = 'other';

  static const options = <String, String>{
    femaleHairdresser: 'Bayan kuaförü',
    maleHairdresser: 'Erkek kuaförü',
    barber: 'Berber',
    beautySalon: 'Güzellik salonu',
    nailArt: 'Nail art / Protez tırnak',
    other: 'Diğer',
  };
}

class BuyerBusinessStatus {
  static const ownShop = 'own_shop';
  static const openingSoon = 'opening_soon';
  static const employedInSalon = 'employed_in_salon';
  static const planning = 'planning';

  static const options = <String, String>{
    ownShop: 'Kendi dükkanım var',
    openingSoon: 'Yakında dükkan açacağım',
    employedInSalon: 'Bir salonda çalışıyorum',
    planning: 'Henüz dükkanım yok, planlıyorum',
  };
}

class BuyerPersonalizationCopy {
  static const introTitle = 'Sizi daha iyi tanıyalım';
  static const introBody =
      'Alışverişe özel ürünler gösterebilmemiz için birkaç kısa soru. Bir kez kaydedilir; isterseniz atlayabilirsiniz.';
  static const whyWeAsk =
      'Sektörünüze (erkek kuaförü, güzellik salonu, nail art…) ve dükkan durumunuza göre “Sana Özel” ürünler listelenir.';

  static const shopNameTitle = 'Salonunuzun veya işletmenizin adı nedir?';
  static const shopNameHint = 'Örn: Kuaför Ahmet, Güzellik Merkezi...';
  static const shopNameHelper =
      'İsterseniz boş bırakabilirsiniz. Yeni açacaksanız da sorun değil.';

  static const businessTypeTitle = 'Hangi alanda çalışıyorsunuz?';
  static const businessTypeHelper =
      'Bayan kuaförü, erkek kuaförü, güzellik salonu, nail art…';

  static const businessStatusTitle = 'Dükkan açıyor musunuz / durumunuz?';
  static const businessStatusHelper =
      'Yeni açılışta farklı ürün paketleri önerebiliriz.';

  static const otherHint = 'Alanınızı yazın';
  static const skip = 'Atla';
  static const continueText = 'Devam';
  static const save = 'Kaydet';
  static const saved = 'Bilgileriniz kaydedildi';
}
