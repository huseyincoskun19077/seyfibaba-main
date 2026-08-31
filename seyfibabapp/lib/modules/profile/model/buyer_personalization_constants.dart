class BuyerBusinessType {
  static const femaleHairdresser = 'female_hairdresser';
  static const maleHairdresser = 'male_hairdresser';
  static const barber = 'barber';
  static const beautySalon = 'beauty_salon';
  static const other = 'other';

  static const options = <String, String>{
    femaleHairdresser: 'Bayan kuaförü',
    maleHairdresser: 'Erkek kuaförü',
    barber: 'Berber',
    beautySalon: 'Güzellik salonu',
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
      'Size özel ürün, paket ve kampanyalar sunabilmemiz için birkaç kısa soru soruyoruz. İsterseniz atlayabilirsiniz.';
  static const whyWeAsk =
      'Bu bilgiler yalnızca size uygun ürün ve fırsatları göstermek için kullanılır.';

  static const shopNameTitle = 'Salonunuzun veya işletmenizin adı nedir?';
  static const shopNameHint = 'Örn: Kuaför Ahmet, Güzellik Merkezi...';
  static const shopNameHelper =
      'İsterseniz boş bırakabilirsiniz. Yeni açacaksanız da sorun değil.';

  static const businessTypeTitle = 'Hangi alanda çalışıyorsunuz?';
  static const businessTypeHelper =
      'Bayan kuaförü, berber, güzellik salonu gibi alanınızı seçin.';

  static const businessStatusTitle = 'İşletmenizin durumu nedir?';
  static const businessStatusHelper =
      'Bütçe ve açılış paketleri için size daha doğru öneriler sunalım.';

  static const otherHint = 'Alanınızı yazın';
  static const skip = 'Atla';
  static const continueText = 'Devam';
  static const save = 'Kaydet';
  static const saved = 'Bilgileriniz kaydedildi';
}
