import { TruckIcon, ShieldCheckIcon, StarIcon, CurrencyDollarIcon } from "./icons/WhySeyfibabaIcons";

const features = [
  {
    icon: TruckIcon,
    title: "Hızlı ve Güvenli Kargo",
    description:
      "Siparişleriniz aynı gün kargoya verilir. Türkiye'nin her yerine güvenli teslimat sağlıyoruz.",
  },
  {
    icon: ShieldCheckIcon,
    title: "Profesyonel Kalite Garantisi",
    description:
      "Tüm ürünlerimiz orijinal ve garantilidir. Berber ve kuaför salonlarının ihtiyaç duyduğu profesyonel kaliteyi sunuyoruz.",
  },
  {
    icon: CurrencyDollarIcon,
    title: "En Uygun Fiyat",
    description:
      "Toptan ve perakende en uygun fiyat garantisi veriyoruz. Aradığınız berber malzemelerini, kuaför ekipmanlarını ve salon mobilyalarını rakipsiz fiyatlarla bulabilirsiniz.",
  },
  {
    icon: StarIcon,
    title: "Geniş Ürün Yelpazesi",
    description:
      "Berber koltuğundan kuaför tezgahına, saç bakım ürünlerinden salon ekipmanlarına kadar 1.000'den fazla ürün çeşidi ile hizmetinizdeyiz.",
  },
];

const buildSignalCards = (homepageData) => {
  const categoryCount = homepageData?.homepage_categories?.length || 0;
  const brandCount = homepageData?.brands?.length || 0;
  const featuredCount = homepageData?.featuredCategoryProducts?.length || 0;
  const newArrivalCount = homepageData?.newArrivalProducts?.length || 0;

  return [
    {
      label: "Kategori gorunurlugu",
      value: categoryCount > 0 ? `${categoryCount}+ kategori` : "Kategori odakli kurgu",
      description:
        "Ana kategoriler, salon kurulumundan gunluk sarf tedarigine kadar farkli satin alma senaryolarina gore gruplanir.",
    },
    {
      label: "Marka karsilastirmasi",
      value: brandCount > 0 ? `${brandCount}+ marka` : "Marka filtreleme aktif",
      description:
        "Ayni ihtiyac icin farkli markalari filtreleyerek fiyat, stok ve kullanim amaci bazinda daha hizli karsilastirma yapabilirsiniz.",
    },
    {
      label: "One cikan urunler",
      value: featuredCount > 0 ? `${featuredCount}+ secili urun` : "Secili urun listeleri",
      description:
        "Featured ve popular alanlari, karar surecini hizlandiran daha gorunur urun vitrinleri olusturur.",
    },
    {
      label: "Guncel akıs",
      value: newArrivalCount > 0 ? `${newArrivalCount}+ yeni urun` : "Yeni urun akisi",
      description:
        "Yeni gelen urunler ve kampanya alanlari, tekrarli tedarik yapan salonlar icin guncel kesif noktasi saglar.",
    },
  ];
};

export default function WhySeyfibaba({ className, homepageData }) {
  const signalCards = buildSignalCards(homepageData);

  return (
    <section className={`w-full ${className || ""}`}>
      <div className="container-x mx-auto">
        <div className="text-center mb-10">
          <h2 className="sm:text-3xl text-2xl font-bold text-qblacktext leading-tight">
            Neden Seyfibaba?
          </h2>
          <p className="text-gray-500 mt-3 max-w-2xl mx-auto text-base leading-relaxed">
            Türkiye'nin en kapsamlı berber ve kuaför malzemeleri pazaryeri olarak
            profesyonellere özel hizmet sunuyoruz.
          </p>
        </div>
        <div className="mb-8 rounded-[28px] border border-[#ece3cf] bg-[#fffaf0] px-6 py-7 md:px-8">
          <h3 className="text-2xl font-bold text-qblacktext">
            Profesyonel satin alma kararini destekleyen ana sinyaller
          </h3>
          <p className="mt-3 max-w-4xl text-sm leading-7 text-gray-600">
            Seyfibaba, salon profesyonellerinin urun secimini yalnizca fiyat
            listesi uzerinden degil; kategori yapisi, marka karsilastirmasi,
            kampanya alani ve guncel urun akisi ile birlikte degerlendirebilmesi
            icin kurgulandi.
          </p>
          <p className="mt-3 max-w-4xl text-sm leading-7 text-gray-600">
            Kisa cevap su: berber ve kuafor ekipmani alirken en dogru karar,
            urunun hangi hizmet istasyonunda kullanilacagini netlestirmekle
            baslar. Bir berber koltugu icin hidrolik sistem ve doseme dayanimi,
            bir kuafor tezgahi icin depolama duzeni ve elektrik noktalarina
            uyum, bir kurutma makinesi icin ise motor tipi ve servis
            erisilebilirligi ayni tabloda degerlendirilmelidir. Seyfibaba
            uzerindeki kategori, marka ve satici yapisi; bu kriterleri tek bir
            akista karsilastirarak salon kurulumu, ekipman yenileme ve tekrarli
            sarf tedarigi senaryolarinda daha savunulabilir satin alma karari
            verilmesini kolaylastirir.
          </p>
          <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {signalCards.map((item) => (
              <article
                key={item.label}
                className="rounded-3xl bg-white px-5 py-5 ring-1 ring-[#eee0be]"
              >
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-[#8b6b29]">
                  {item.label}
                </p>
                <p className="mt-3 text-xl font-bold text-qblacktext">
                  {item.value}
                </p>
                <p className="mt-3 text-sm leading-7 text-[#555555]">
                  {item.description}
                </p>
              </article>
            ))}
          </div>
        </div>
        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {features.map((feature, index) => (
            <div
              key={index}
              className="bg-white rounded-2xl p-6 border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all duration-300"
            >
              <div className="w-12 h-12 bg-qyellow/10 rounded-xl flex items-center justify-center mb-4">
                <feature.icon className="w-6 h-6 text-qyellow" />
              </div>
              <h3 className="text-lg font-bold text-qblacktext mb-2">
                {feature.title}
              </h3>
              <p className="text-gray-500 text-sm leading-relaxed">
                {feature.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
