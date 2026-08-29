import JsonLd, { generateFAQSchema } from "../Helpers/JsonLd";
import Accodion from "../Helpers/Accodion";

const faqItems = [
  {
    question: "Seyfibaba'da hangi berber ve kuaför malzemeleri bulunur?",
    answer:
      "Seyfibaba; berber koltugu, kuafor tezgahi, tiras makineleri, makas setleri, salon ekipmanlari, sarf malzemeler ve farkli profesyonel marka urunlerini bir arada sunar. Kategori yapisi salon kurulumundan gunluk tedarige kadar farkli ihtiyaclara gore ilerlemeyi kolaylastirir. Bu sayede kullanici yalnizca tek bir urune degil; ayni hizmet akisinin parcasi olan tamamlayici ekipmanlara da hizli sekilde ulasabilir.",
  },
  {
    question: "Salonum için doğru ekipmanı nasıl seçebilirim?",
    answer:
      "Secim yaparken urunun kullanim yogunlugu, malzeme kalitesi, teknik ozellikleri, olculeri ve salonunuzun hizmet tipi birlikte degerlendirilmelidir. Berber koltugu veya kuafor mobilyasi gibi urunlerde ergonomi, temizlik kolayligi ve dayaniklilik uzun vadede daha belirleyicidir. Pratikte dogru yontem; once hizmet istasyonu bazinda zorunlu ekipmani cikarmak, sonra marka, fiyat, stok ve servis kosullarini ayni karar listesinde toplamaktir.",
  },
  {
    question: "Seyfibaba profesyonel kullanıcılar için uygun mu?",
    answer:
      "Evet. Platform, profesyonel berberler, kuaforler, guzellik merkezleri ve yeni salon kuran girisimciler icin uygun urun gruplarini bir araya getirir. Bu sayede hem tekli urun arayanlar hem de toplu ekipman ihtiyaci olan isletmeler daha hizli sekilde ilerleyebilir. Ozellikle salon acilisi, ekipman yenileme ve tekrarli sarf tedarigi gibi farkli satin alma senaryolari icin kategori akisi daha kullanisli bir karsilastirma zemini sunar.",
  },
  {
    question: "Marka ve fiyat karşılaştırması yapabilir miyim?",
    answer:
      "Seyfibaba'da urunler kategori ve marka baglaminda listelendigi icin farkli secenekleri karsilastirmak daha kolaydir. Bu yapi, profesyonel kuafor ekipmanlari ve salon malzemeleri icin butceye uygun alternatifleri daha net gormenizi saglar. Karsilastirma yaparken yalnizca etiket fiyatina degil; stok surekliligi, teknik ozellikler, kullanim amaci ve satici guven sinyallerine birlikte bakmak daha saglikli sonuc verir.",
  },
];

export default function HomeFAQ() {
  return (
    <section
      aria-labelledby="home-faq-heading"
      className="container-x mx-auto"
    >
      <JsonLd data={generateFAQSchema(faqItems)} />
      <div className="rounded-[32px] bg-white px-6 py-10 shadow-sm ring-1 ring-[#f1eadc] md:px-10 md:py-14">
        <div className="max-w-3xl">
          <h2
            id="home-faq-heading"
            className="text-3xl font-black text-[#1f2937] md:text-4xl"
          >
            Sıkça Sorulan Sorular
          </h2>
          <p className="mt-4 text-[15px] leading-8 text-[#4b5563] md:text-base">
            Berber ve kuaför malzemeleri satın alırken en çok sorulan konuları
            aşağıda topladık. Bu bölüm, hem kullanıcıların hızlı bilgiye
            ulaşmasını hem de arama motorlarının anasayfa içeriğini daha iyi
            anlamasını destekler.
          </p>
        </div>
        <div className="mt-8 space-y-4">
          {faqItems.map((faq, index) => (
            <Accodion
              key={faq.question}
              init={index === 0}
              title={faq.question}
              des={faq.answer}
            />
          ))}
        </div>
      </div>
    </section>
  );
}
