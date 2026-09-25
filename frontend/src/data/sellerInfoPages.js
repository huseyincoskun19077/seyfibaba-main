/**
 * Kuaför Tedarik satıcı bilgilendirme sayfaları.
 * İçerik yalnızca platformda satıcı/müşterinin bildiği kurallara dayanır.
 */

export const sellerInfoPages = {
  "nasil-satici-olunur": {
    slug: "nasil-satici-olunur",
    title: "Kuaför Tedarik'te Nasıl Satıcı Olunur?",
    description:
      "Kuaför Tedarik'te satıcı olmak için kayıt, KYC doğrulama, Iyzico alt üye işyeri ve ürün ekleme yol haritası.",
    h1: "Nasıl satıcı olunur?",
    lead: "Kuaför Tedarik; berber, kuaför ve güzellik salonu ekipmanlarına özel Türkiye pazaryeridir. Satıcı olmak için abonelik ücreti yoktur. Aşağıdaki yol haritasını sırayla tamamlayın.",
    layout: "roadmap",
    roadmapTitle: "Satıcı olma yol haritası",
    roadmapSummary:
      "Kayıt → giriş → KYC + Iyzico → ürün ekle → satışa hazır",
    steps: [
      {
        n: "1",
        title: "Satıcı başvurunuzu yapın",
        text: "Sitedeki Satıcı Ol kaydından veya WhatsApp bilgi hattından (0850 303 5073) başvurunuzu başlatın.",
        bullets: [
          "Mağaza ve iletişim bilgilerinizi eksiksiz doldurun",
          "Kayıt sonrası Satıcı Girişi ile panele girin",
          "Web ve mobil satıcı paneli aynı hesabı kullanır",
        ],
      },
      {
        n: "2",
        title: "Satıcı paneline giriş yapın",
        text: "Hesabınız açıldıktan sonra Satıcı Girişi ile paneli açın. Destek için Admin’e Mesaj, SSS veya 0850 303 5073 hattını kullanabilirsiniz.",
        bullets: [
          "Mağaza bilgilerini kontrol edin",
          "Bildirimleri açık tutun",
        ],
      },
      {
        n: "3",
        title: "KYC doğrulama yapın",
        text: "Satış ve hakediş için hesap doğrulama zorunludur. Belgeler, IBAN ve Iyzico alt üye işyeri tamamlanmadan ödeme aktarımı gecikebilir veya yapılamaz.",
        bullets: [
          "Kimlik ve vergi belgelerini panelden yükleyin",
          "Vergi levhası sektöre özel satışı teyit eder",
          "IBAN kimlik / vergi bilgilerinizle uyumlu olsun",
          "Iyzico alt üye işyeri kaydı (TC kimlik zorunlu)",
        ],
      },
      {
        n: "4",
        title: "Ürünlerinizi ekleyin",
        text: "Doğrulama sonrası ürünleri tek tek, hızlı ürün ekleme (AI) veya Excel toplu yükleme ile ekleyin. Fotoğraf, stok, fiyat ve ölçü bilgilerini doğru girin.",
        bullets: [
          "Tek ürün / hızlı AI / Excel toplu yükleme",
          "İsterseniz ürün yükleme desteği alın",
          "Stok, fiyat ve ölçüleri güncel tutun",
        ],
      },
      {
        n: "5",
        title: "Satışa hazır olun",
        text: "Ürünleriniz yayınlandığında web ve mobil vitrinde görünür. Sipariş gelince paneli takip eder, hazırlar, kargolar ve takip numarasını girersiniz.",
        bullets: [
          "Platform komisyonu sabit %10",
          "Aylık abonelik veya gizli listeleme ücreti yok",
          "Kargo bedeli satıcıya aittir",
        ],
      },
    ],
    related: [
      "nasil-satis-yapilir",
      "komisyon-ve-hakedis",
      "urun-nasil-eklenir",
    ],
  },

  "nasil-satis-yapilir": {
    slug: "nasil-satis-yapilir",
    title: "Kuaför Tedarik'te Nasıl Satış Yapılır?",
    description:
      "Siparişten kargoya, alıcı onayından Iyzico hakedişine kadar Kuaför Tedarik satış yol haritası.",
    h1: "Nasıl satış yapılır?",
    lead: "Kuaför Tedarik’te satış; sipariş karşılama, kargo, alıcı onayı ve Iyzico hakedişinden oluşur. Ödeme Iyzico pazaryeri havuzunda işlenir; kart bilgisi Kuaför Tedarik’te tutulmaz.",
    layout: "roadmap",
    roadmapTitle: "Satış yol haritası",
    roadmapSummary:
      "Sipariş → hazırla → kargola → teslimat / alıcı onayı → %10 komisyon → IBAN’a hakediş",
    steps: [
      {
        n: "1",
        title: "Sipariş gelir",
        text: "Sipariş düşünce panel ve mobil bildirim alırsınız. Siparişi kontrol edip onaylar / hazırlarsınız.",
        bullets: [
          "Stok ve ürün bilgisini kontrol edin",
          "Paketlemeye uygun şekilde hazırlayın",
        ],
      },
      {
        n: "2",
        title: "Kargoya verin",
        text: "Kargo bedeli ve sorumluluğu tamamen satıcıya aittir. Kuaför Tedarik ürünü depolamaz. Anlaşmalı firmanızla gönderip takip numarasını panele girin.",
        bullets: [
          "Manuel Kargo bölümünden takip numarası girin",
          "Geliver veya kendi kargo anlaşmanızı kullanabilirsiniz",
          "Takip numarasını siparişe mutlaka işleyin",
        ],
      },
      {
        n: "3",
        title: "Teslimat ve alıcı onayı",
        text: "Ürün alıcıya ulaşır. Alıcı onayı / teslimat onayı hakediş sürecinin kritik adımıdır.",
        bullets: [
          "İade süreci sorunsuz tamamlanmalıdır",
          "KYC + IBAN + Iyzico kaydı tamamlanmış olmalıdır",
        ],
      },
      {
        n: "4",
        title: "Komisyon kesilir, hakediş hesaplanır",
        text: "Müşteri ödemesi Iyzico güvenli havuzundadır. Süreç tamamlanınca %10 platform komisyonu kesilir; kalan tutar satıcı payıdır.",
        bullets: [
          "Örnek: 1.000 TL satış → ~100 TL komisyon, ~900 TL satıcı payı (kargo hariç)",
          "Gizli üyelik / listeleme ücreti yoktur",
        ],
      },
      {
        n: "5",
        title: "Tutar IBAN’ınıza aktarılır",
        text: "Hakediş onayından sonra tutar Iyzico hesabınıza yatırılır; banka hesabına geçiş Iyzico takvimine göredir. Onay, paranın hemen IBAN’ınıza yatmış olduğu anlamına gelmez.",
        bullets: [
          "Hakedişi satıcı panelinden takip edin",
          "IBAN kimlik / vergi bilgilerinizle uyumlu olsun",
        ],
      },
      {
        n: "6",
        title: "İade olursa (gerekirse)",
        text: "Alıcı yasal süre içinde siteden veya uygulamadan iade talebi açar. Talep İade Talepleri ekranına düşer; kabul veya gerekçeli ret verirsiniz.",
        bullets: [
          "Kabulde alıcı ürünü geri gönderir",
          "Onaylanan iadede tutar hakedişten düşülür veya aktarım düzeltilir",
          "Anlaşmazlıkta Admin’e Mesaj veya 0850 303 5073",
        ],
      },
    ],
    related: [
      "nasil-satici-olunur",
      "komisyon-ve-hakedis",
      "kargo-ve-teslimat",
      "iade-sureci",
    ],
  },

  "kimlere-hitap-ediyor": {
    slug: "kimlere-hitap-ediyor",
    title: "Kuaför Tedarik Kimlere Hitap Ediyor?",
    description:
      "Kuaför Tedarik kuaför, berber ve güzellik salonu işletmelerine ürün satan satıcılar ile bu işletmelere yönelik pazaryeridir.",
    h1: "Kimlere hitap ediyor?",
    lead: "Kuaför Tedarik genel bir pazaryeri değildir. Yalnızca berber, kuaför ve güzellik sektörüne odaklanır. Hem alıcı hem satıcı tarafı bu sektöre göre şekillenir.",
    sections: [
      {
        heading: "Alıcı kitlesi",
        paragraphs: [
          "Platformdaki müşteriler ağırlıklı olarak kuaför salonları, berber dükkânları ve güzellik salonlarıdır. Türkiye genelindeki bu işletmeler, ihtiyaç duydukları mobilya, malzeme, kozmetik ve yedek parçayı Kuaför Tedarik web ve mobil uygulamasından keşfeder.",
        ],
      },
      {
        heading: "Satıcı kitlesi",
        paragraphs: [
          "Kuaför, berber ve güzellik sektörüne ürün satan üreticiler, toptancılar ve yetkili satıcılar Kuaför Tedarik’te mağaza açabilir. Amaç; doğru ürünü doğru işletmeyle buluşturmak ve mevcut satış kanallarınıza Türkiye geneli ek bir kanal eklemektir.",
        ],
      },
      {
        heading: "Neden sektöre özel?",
        paragraphs: [
          "Genel pazaryerlerinde ürününüz binlerce kategori arasında kaybolabilir. Kuaför Tedarik’te vitrin, kategori yapısı ve tanıtım çalışmaları doğrudan bu sektöre yöneliktir. Ürün sayfalarında satıcı adı gösterilmez; müşteri ürüne odaklanır.",
        ],
      },
      {
        heading: "Ne satılır?",
        paragraphs: [
          "Kuaför ve berber koltukları, yıkama üniteleri, tezgâhlar, aynalar ve salon mobilyaları; profesyonel saç bakım ürünleri, makineler ve ekipmanlar; saç bakımı, boyama, şekillendirme, erkek bakım, cilt, kirpik-kaş, tırnak, ağda, makyaj, hijyen ve sarf kozmetikleri; koltuk ve ünite yedek parçaları.",
        ],
      },
    ],
    related: [
      "hangi-urunler-satilir",
      "nasil-satici-olunur",
      "neden-seyfibaba",
    ],
  },

  "neden-seyfibaba": {
    slug: "neden-seyfibaba",
    title: "Neden Kuaför Tedarik'te Satıcı Olmalıyım?",
    description:
      "Sektöre özel vitrin, %10 şeffaf komisyon, Iyzico güvencesi, ürün yükleme desteği ve Türkiye geneli görünürlük.",
    h1: "Neden Kuaför Tedarik?",
    lead: "Kuaför Tedarik; berber ve kuaför sektörüne odaklı pazaryeridir. Iyzico güvencesi, şeffaf komisyon ve sektöre özel alıcı kitlesi sunar.",
    sections: [
      {
        heading: "Satıcı avantajları",
        bullets: [
          "Türkiye genelinde sektöre özel ürün tanıtımı",
          "Kendi müşteri çevrenizin dışında yeni işletmelere ulaşma",
          "Dijital ve saha odaklı pazarlama çalışmaları",
          "Ürün yükleme desteği",
          "Uygun sistemlerle ürün, fiyat ve stok entegrasyon seçenekleri",
          "Web ve mobil uygulama üzerinden görünürlük",
          "Aylık abonelik yok; komisyon sabit %10",
        ],
      },
      {
        heading: "Şeffaf komisyon",
        paragraphs: [
          "Ürün satışından yalnızca %10 platform komisyonu kesilir. Gizli üyelik veya listeleme ücreti yoktur. Satış tutarının yaklaşık %90’ı satıcıya aittir (iade ve kargo ayrıdır).",
        ],
      },
      {
        heading: "Güvenli ödeme",
        paragraphs: [
          "Kart ödemesi Iyzico’da işlenir. Müşteri ödemesi Iyzico pazaryeri havuzunda tutulur; alıcı onayı sonrası net tutar doğrulanmış IBAN’ınıza aktarılır.",
        ],
      },
    ],
    related: [
      "kimlere-hitap-ediyor",
      "komisyon-ve-hakedis",
      "nasil-satici-olunur",
    ],
  },

  "komisyon-ve-hakedis": {
    slug: "komisyon-ve-hakedis",
    title: "Kuaför Tedarik Komisyon ve Hakediş",
    description:
      "Kuaför Tedarik platform komisyonu %10'dur. Iyzico hakediş, IBAN aktarımı ve kesintiler hakkında net bilgi.",
    h1: "Komisyon ve hakediş",
    lead: "Satıcıların en çok sorduğu konu budur. Kuaför Tedarik’te komisyon sabittir; ödeme Iyzico üzerinden yürür.",
    sections: [
      {
        heading: "Hangi kesintiler var?",
        paragraphs: [
          "Kuaför Tedarik platform komisyonu sabit %10’dur. Ödeme Iyzico altyapısından işlenir. Hakediş onayından sonra tutar Iyzico hesabınıza yatırılır; banka hesabına geçiş Iyzico takvimine göredir.",
          "Örnek: 1.000 TL satış → yaklaşık 100 TL komisyon, yaklaşık 900 TL satıcı payı (kargo hariç). Komisyon, hakediş hesaplanırken düşülür.",
        ],
      },
      {
        heading: "Param ne zaman hesabıma geçer?",
        paragraphs: [
          "Müşteri ödemesi Iyzico güvenli havuzuna düşer. Teslimat ve iade süreci sorunsuz tamamlanınca %10 komisyon kesilir; kalan tutar doğrulanmış IBAN’ınıza otomatik aktarılır.",
          "Alıcı onayı / teslimat onayı kritik adımdır. KYC, IBAN ve Iyzico alt üye işyeri kaydı tamamlanmış olmalıdır. Hakedişi satıcı panelinden takip edebilirsiniz.",
        ],
      },
      {
        heading: "Aylık ücret var mı?",
        paragraphs: [
          "Hayır. Satıcı olmak için aylık abonelik modeli yoktur. Gizli üyelik veya listeleme ücreti alınmaz.",
        ],
      },
    ],
    related: [
      "nasil-satis-yapilir",
      "iade-sureci",
      "nasil-satici-olunur",
    ],
  },

  "kargo-ve-teslimat": {
    slug: "kargo-ve-teslimat",
    title: "Kuaför Tedarik'te Kargo ve Teslimat",
    description:
      "Kuaför Tedarik'te kargo ücreti satıcıya aittir. Takip numarası, Manuel Kargo ve gönderim sorumluluğu hakkında bilgi.",
    h1: "Kargo ve teslimat",
    lead: "Kuaför Tedarik ürünü depolamaz. Paketleme, kargolama ve kargo ücreti satıcıya aittir.",
    sections: [
      {
        heading: "Kargo ücretini kim öder?",
        paragraphs: [
          "Kargo bedeli her durumda satıcıya aittir. Siparişlerinizi kendi anlaşmalı kargo firmanızla gönderir; satıcı panelindeki Manuel Kargo bölümünden takip numarasını girersiniz.",
        ],
      },
      {
        heading: "Kendi kargomla gönderebilir miyim?",
        paragraphs: [
          "Evet. Anlaşmalı kargo firmasıyla gönderip takip numarasını panele girmeniz yeterli; maliyet yine size aittir. Geliver entegrasyonu veya kendi kargo anlaşmanızı kullanabilirsiniz.",
        ],
      },
      {
        heading: "Ölçü ve ağırlık neden önemli?",
        paragraphs: [
          "Ürün eklerken ölçü ve ağırlık bilgisini doğru girin. Bu bilgiler kargo maliyeti ve müşteri deneyimi için önemlidir. Stok ve fiyatı da güncel tutun.",
        ],
      },
    ],
    related: [
      "nasil-satis-yapilir",
      "urun-nasil-eklenir",
      "iade-sureci",
    ],
  },

  "urun-nasil-eklenir": {
    slug: "urun-nasil-eklenir",
    title: "Kuaför Tedarik'e Ürün Nasıl Eklenir?",
    description:
      "Tek ürün, hızlı AI ürün ekleme ve Excel toplu yükleme ile Kuaför Tedarik satıcı paneline ürün ekleme.",
    h1: "Ürün nasıl eklenir?",
    lead: "Doğrulanmış satıcı hesabınızla panele girip ürünlerinizi üç yoldan ekleyebilirsiniz. İsterseniz ürün yükleme desteği de alırsınız.",
    sections: [
      {
        heading: "Üç ekleme yolu",
        bullets: [
          "Tek ürün ekleme — her ürünü formdan tek tek girersiniz",
          "Hızlı ürün ekleme (AI) — panelde Türkçe asistanla içerik ve süreç desteği",
          "Excel toplu yükleme — çok sayıda ürünü toplu aktarırsınız",
        ],
      },
      {
        heading: "Nelere dikkat edilmeli?",
        paragraphs: [
          "Fotoğraf, stok, fiyat ve ölçü bilgilerini doğru girin. Stok ve fiyatı güncel tutun. Ölçü ve ağırlık kargo için önemlidir.",
          "Ürün adı, görsel, fiyat ve stok bilgilerinizi paylaşarak ekibimizden yükleme desteği isteyebilirsiniz. WhatsApp bilgi hattı: 0850 303 5073.",
        ],
      },
      {
        heading: "Entegrasyon",
        paragraphs: [
          "Uygun sistemlerle ürün, fiyat ve stok aktarımı veya güncelleme için entegrasyon seçenekleri mevcuttur. Detay için kayıt sonrası destek hattından bilgi alabilirsiniz.",
        ],
      },
    ],
    related: [
      "hangi-urunler-satilir",
      "nasil-satici-olunur",
      "nasil-satis-yapilir",
    ],
  },

  "hangi-urunler-satilir": {
    slug: "hangi-urunler-satilir",
    title: "Kuaför Tedarik'te Hangi Ürünler Satılır?",
    description:
      "Kuaför mobilyaları, malzemeler, kozmetik ve yedek parçalar — Kuaför Tedarik'te satabileceğiniz ürün kategorileri.",
    h1: "Hangi ürünler satılır?",
    lead: "Kuaför Tedarik’te satılan ürünler berber, kuaför ve güzellik salonu ihtiyaçlarına yöneliktir.",
    sections: [
      {
        heading: "Kuaför mobilyaları",
        paragraphs: [
          "Kuaför koltukları, berber koltukları, yıkama üniteleri, tezgâhlar, aynalar ve salon mobilyaları.",
        ],
      },
      {
        heading: "Kuaför malzemeleri",
        paragraphs: [
          "Profesyonel saç bakım ürünleri, makineler, ekipmanlar, aksesuarlar ve salon ihtiyaçları.",
        ],
      },
      {
        heading: "Kozmetik",
        paragraphs: [
          "Saç bakımı, boyama, şekillendirme, erkek bakım, cilt, kirpik-kaş, tırnak, ağda, makyaj, hijyen ve sarf ürünleri.",
        ],
      },
      {
        heading: "Yedek parçalar",
        paragraphs: [
          "Kuaför koltukları, yıkama üniteleri ve salon ekipmanlarına yönelik yedek parçalar.",
        ],
      },
    ],
    related: [
      "kimlere-hitap-ediyor",
      "urun-nasil-eklenir",
      "nasil-satici-olunur",
    ],
  },

  "iade-sureci": {
    slug: "iade-sureci",
    title: "Kuaför Tedarik Satıcı İade Süreci",
    description:
      "Müşteri iade talebi geldiğinde satıcı panelinde ne yapılır? Kabul, ret ve hakediş etkisi.",
    h1: "İade süreci",
    lead: "İade talepleri satıcı panelindeki İade Talepleri bölümünden yönetilir. Karar size aittir; onaylanan iadelerde hakediş güncellenir.",
    sections: [
      {
        heading: "Talep nasıl gelir?",
        paragraphs: [
          "Alıcı yasal süre içinde uygulamadan veya siteden iade talebi açar; neden ve gerekirse fotoğraf ekler. Talep size İade Talepleri ekranında düşer.",
        ],
      },
      {
        heading: "Siz ne yaparsınız?",
        bullets: [
          "Ürünü ve gerekçeyi incelersiniz",
          "Kabul veya gerekçeli ret verirsiniz",
          "Kabulde alıcı ürünü geri gönderir; ürün size ulaşınca süreç tamamlanır",
          "Onaylanan iadede alıcıya ödeme iadesi yapılır; satıcı hakedişinden ilgili tutar düşülür veya henüz aktarılmamışsa aktarım düzeltilir / iptal edilir",
        ],
      },
      {
        heading: "Anlaşmazlık",
        paragraphs: [
          "Anlaşmazlıkta Admin’e Mesaj veya 0850 303 5073 destek hattı devreye girer.",
        ],
      },
    ],
    related: [
      "nasil-satis-yapilir",
      "komisyon-ve-hakedis",
      "kargo-ve-teslimat",
    ],
  },
};

export const sellerInfoPageList = Object.values(sellerInfoPages).map((p) => ({
  slug: p.slug,
  title: p.h1,
  description: p.description,
  href: `/satici/${p.slug}`,
}));

export function getSellerInfoPage(slug) {
  return sellerInfoPages[slug] || null;
}

export function getSellerInfoSlugs() {
  return Object.keys(sellerInfoPages);
}
