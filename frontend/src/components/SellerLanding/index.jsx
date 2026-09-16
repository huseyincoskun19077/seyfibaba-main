"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { sellerFaqIntro, sellerFaqSections } from "@/data/sellerFaq";

const WHATSAPP_DIGITS = "908503035073";
const WHATSAPP_TEXT =
  "Merhaba, Seyfibaba'da satıcı olmak istiyorum. Bilgi almak istiyorum.";
const WHATSAPP_URL = `https://wa.me/${WHATSAPP_DIGITS}?text=${encodeURIComponent(WHATSAPP_TEXT)}`;

const SECTIONS = [
  { id: "giris", label: "Satışa başlayın" },
  { id: "neden", label: "Neden Seyfibaba?" },
  { id: "gorunurluk", label: "Türkiye geneli görünürlük" },
  { id: "pazarlama", label: "Pazarlama desteği" },
  { id: "platform", label: "100+ satıcı, 2.000+ ürün" },
  { id: "urun-yukleme", label: "Kolay ürün yükleme" },
  { id: "whatsapp-destek", label: "WhatsApp ürün desteği" },
  { id: "entegrasyon", label: "Mevcut sisteminizle çalışın" },
  { id: "urunler", label: "Hangi ürünleri satabilirsiniz?" },
  { id: "avantajlar", label: "Satıcı avantajları" },
  { id: "surec", label: "Nasıl katılırım?" },
  { id: "sss", label: "Sıkça sorulan sorular" },
  { id: "basvuru", label: "Başvuru ve iletişim" },
];

const ADVANTAGES = [
  {
    title: "Türkiye genelinde ürün tanıtımı",
    text: "Ürünlerinizin Türkiye genelindeki sektör profesyonellerine ulaştırılması için çalışıyoruz.",
  },
  {
    title: "Yeni müşterilere ulaşma",
    text: "Kendi müşteri çevrenizin dışında yeni işletmelere ulaşabileceğiniz ek bir satış kanalı.",
  },
  {
    title: "Pazarlama çalışmaları",
    text: "Platformun büyümesi ve yeni işletmelere ulaşması için dijital ve saha odaklı tanıtım sürdürülür.",
  },
  {
    title: "Ürün yükleme desteği",
    text: "Ürün adı, görsel, fiyat ve stok bilgilerinizi platforma aktarma sürecinde destek alın.",
  },
  {
    title: "Entegrasyon imkânları",
    text: "Uygun sistemlerle ürün, fiyat ve stok aktarımı / güncelleme için entegrasyon seçenekleri.",
  },
  {
    title: "Mobil ve web erişimi",
    text: "Müşteriler ürünlerinizi Seyfibaba web ve mobil uygulamaları üzerinden keşfeder.",
  },
  {
    title: "Aylık abonelik yok",
    text: "Satıcı olmak için aylık abonelik modeli yoktur. Komisyon şeffaftır.",
  },
  {
    title: "Sektöre özel pazaryeri",
    text: "Genel pazaryeri yerine doğrudan kuaför, berber ve güzellik sektörüne odaklı platform.",
  },
];

const PRODUCT_CATEGORIES = [
  {
    title: "Kuaför mobilyaları",
    text: "Kuaför koltukları, berber koltukları, yıkama üniteleri, tezgâhlar, aynalar ve salon mobilyaları.",
  },
  {
    title: "Kuaför malzemeleri",
    text: "Profesyonel saç bakım ürünleri, makineler, ekipmanlar, aksesuarlar ve salon ihtiyaçları.",
  },
  {
    title: "Kozmetik",
    text: "Saç bakımı, boyama, şekillendirme, erkek bakım, cilt, kirpik-kaş, tırnak, ağda, makyaj, hijyen ve sarf ürünleri.",
  },
  {
    title: "Yedek parçalar",
    text: "Kuaför koltukları, yıkama üniteleri ve salon ekipmanlarına yönelik yedek parçalar.",
  },
];

const STEPS = [
  {
    n: "1",
    title: "Satıcı başvurunuzu yapın",
    text: "Hızlı kayıt formundan bilgilerinizi bırakın veya WhatsApp’tan bilgi alın.",
  },
  {
    n: "2",
    title: "Ürünlerinizi belirleyin",
    text: "Satmak istediğiniz ürünleri ve ürün bilgilerini paylaşın; gerekirse ekibimiz yardımcı olur.",
  },
  {
    n: "3",
    title: "Ürünleriniz platforma eklensin",
    text: "Ürünleriniz Seyfibaba’da yayınlanır; web ve mobil vitrinde görünür.",
  },
  {
    n: "4",
    title: "Türkiye genelindeki müşterilere ulaşın",
    text: "Kuaför, berber ve güzellik salonları ürünlerinizi keşfeder.",
  },
  {
    n: "5",
    title: "Satışlarınızı büyütün",
    text: "Seyfibaba’yı mevcut kanallarınıza ekleyerek yeni müşterilere ulaşın.",
  },
];

function CtaRow({ className = "" }) {
  return (
    <div className={`flex flex-col sm:flex-row gap-3 ${className}`}>
      <Link
        href="/satici-kayit"
        className="inline-flex items-center justify-center rounded-md bg-qyellow px-6 py-3.5 text-sm md:text-base font-700 text-qblack hover:brightness-95 transition"
      >
        Satışa Başla — Kayıt Ol
      </Link>
      <a
        href={WHATSAPP_URL}
        target="_blank"
        rel="noopener noreferrer"
        className="inline-flex items-center justify-center rounded-md border border-[#25D366] bg-[#25D366] px-6 py-3.5 text-sm md:text-base font-700 text-white hover:brightness-95 transition"
      >
        WhatsApp ile Bilgi Al
      </a>
      <Link
        href="/satici-giris"
        className="inline-flex items-center justify-center rounded-md border border-[#d9c89a] bg-white px-6 py-3.5 text-sm md:text-base font-700 text-qblacktext hover:bg-[#fffaf0] transition"
      >
        Satıcı Girişi
      </Link>
    </div>
  );
}

function SectionHeading({ children }) {
  return (
    <h2 className="text-2xl md:text-3xl font-bold text-qblacktext leading-tight">
      {children}
    </h2>
  );
}

function Body({ children }) {
  return (
    <p className="mt-3 text-[#555] text-sm md:text-base leading-relaxed">
      {children}
    </p>
  );
}

export default function SellerLanding() {
  const [activeId, setActiveId] = useState("giris");
  const [openFaq, setOpenFaq] = useState("0-0");
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  const faqFlat = useMemo(
    () =>
      sellerFaqSections.flatMap((section, si) =>
        section.items.map((item, qi) => ({
          key: `${si}-${qi}`,
          section: section.title,
          q: item.q,
          a: item.a,
        }))
      ),
    []
  );

  const scrollToId = useCallback((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const top = el.getBoundingClientRect().top + window.scrollY - 96;
    window.scrollTo({ top, behavior: "smooth" });
    setActiveId(id);
    setMobileNavOpen(false);
  }, []);

  useEffect(() => {
    const ids = SECTIONS.map((s) => s.id);
    const observers = [];

    ids.forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      const obs = new IntersectionObserver(
        ([entry]) => {
          if (entry.isIntersecting) setActiveId(id);
        },
        { rootMargin: "-20% 0px -65% 0px", threshold: 0 }
      );
      obs.observe(el);
      observers.push(obs);
    });

    return () => observers.forEach((o) => o.disconnect());
  }, []);

  const openFaqAndScroll = (key) => {
    setOpenFaq(key);
    scrollToId("sss");
  };

  return (
    <div className="w-full bg-[#faf7f1]">
      {/* Hero */}
      <section
        id="giris"
        className="relative scroll-mt-24 overflow-hidden border-b border-[#ece3cf]"
      >
        <div
          className="pointer-events-none absolute inset-0 opacity-70"
          style={{
            background:
              "radial-gradient(ellipse 80% 60% at 20% 0%, #ffe8b8 0%, transparent 55%), radial-gradient(ellipse 70% 50% at 90% 20%, #f3e6c8 0%, transparent 50%)",
          }}
        />
        <div className="container-x mx-auto relative px-4 py-12 md:py-16">
          <p className="text-sm font-600 tracking-wide text-[#9a7b2f] mb-3">
            Seyfibaba Satıcı Platformu
          </p>
          <h1 className="max-w-4xl text-3xl md:text-5xl font-bold text-qblacktext leading-tight">
            Seyfibaba’da satışa başlayın, ürünlerinizi Türkiye’nin her yerine
            ulaştırın
          </h1>
          <p className="mt-5 max-w-3xl text-base md:text-lg text-[#5c5c5c] leading-relaxed">
            Kuaför, berber ve güzellik sektörüne ürün mü satıyorsunuz? Ürünlerinizi
            yalnızca kendi mağazanızda bırakmayın.{" "}
            <strong className="font-700 text-qblacktext">
              Seyfibaba ile Türkiye genelindeki salonların karşısına çıkarın.
            </strong>
          </p>
          <p className="mt-4 max-w-3xl text-sm md:text-base text-[#666] leading-relaxed">
            Seyfibaba; sektör profesyonellerini ve ürün tedarikçilerini bir araya
            getiren online pazaryeridir. Amacımız yalnızca listelemek değil;
            ürünlerinizin daha fazla işletme tarafından keşfedilmesini sağlamaktır.
          </p>
          <CtaRow className="mt-8" />
          <p className="mt-4 text-sm text-[#7a7a7a]">
            WhatsApp’tan yazarken mesaj otomatik olarak{" "}
            <em>“Satıcı olmak istiyorum”</em> ile açılır. Zaten satıcıysanız{" "}
            <Link href="/satici-giris" className="underline hover:text-qblacktext">
              satıcı girişi
            </Link>{" "}
            yapın.
          </p>
        </div>
      </section>

      <div className="container-x mx-auto px-4 py-8 md:py-12">
        <div className="lg:grid lg:grid-cols-[280px_minmax(0,1fr)] lg:gap-10 items-start">
          {/* Left nav */}
          <aside className="lg:sticky lg:top-24 mb-6 lg:mb-0">
            <div className="rounded-2xl border border-[#ece3cf] bg-white shadow-sm overflow-hidden">
              <button
                type="button"
                className="lg:hidden w-full flex items-center justify-between px-4 py-3.5 font-700 text-qblacktext text-sm"
                onClick={() => setMobileNavOpen((v) => !v)}
                aria-expanded={mobileNavOpen}
              >
                İçindekiler / SSS
                <span className="text-qyellow text-lg">{mobileNavOpen ? "−" : "+"}</span>
              </button>

              <nav
                className={`${
                  mobileNavOpen ? "block" : "hidden"
                } lg:block max-h-[70vh] overflow-y-auto px-2 py-2`}
                aria-label="Satıcı sayfası içindekiler"
              >
                <p className="hidden lg:block px-3 pt-3 pb-2 text-xs font-700 uppercase tracking-wide text-[#9a7b2f]">
                  Bu sayfada
                </p>
                <ul className="space-y-0.5">
                  {SECTIONS.map((item) => (
                    <li key={item.id}>
                      <button
                        type="button"
                        onClick={() => scrollToId(item.id)}
                        className={`w-full text-left rounded-lg px-3 py-2 text-sm transition ${
                          activeId === item.id
                            ? "bg-[#fff6de] text-qblacktext font-700"
                            : "text-[#555] hover:bg-[#faf7f1] font-500"
                        }`}
                      >
                        {item.label}
                      </button>
                    </li>
                  ))}
                </ul>

                <div className="mt-3 border-t border-[#ece3cf] pt-3 px-1">
                  <p className="px-2 pb-2 text-xs font-700 uppercase tracking-wide text-[#9a7b2f]">
                    SSS — hızlı bak
                  </p>
                  <ul className="space-y-0.5 pb-2">
                    {faqFlat.map((item) => (
                      <li key={item.key}>
                        <button
                          type="button"
                          onClick={() => openFaqAndScroll(item.key)}
                          className={`w-full text-left rounded-lg px-3 py-2 text-xs leading-snug transition ${
                            openFaq === item.key && activeId === "sss"
                              ? "bg-[#fff6de] text-qblacktext font-700"
                              : "text-[#666] hover:bg-[#faf7f1]"
                          }`}
                        >
                          {item.q}
                        </button>
                      </li>
                    ))}
                  </ul>
                </div>

                <div className="border-t border-[#ece3cf] p-3 space-y-2">
                  <Link
                    href="/satici-kayit"
                    className="flex items-center justify-center w-full rounded-md bg-qyellow py-2.5 text-sm font-700 text-qblack"
                  >
                    Kayıt Ol
                  </Link>
                  <a
                    href={WHATSAPP_URL}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center justify-center w-full rounded-md bg-[#25D366] py-2.5 text-sm font-700 text-white"
                  >
                    WhatsApp Bilgi Al
                  </a>
                </div>
              </nav>
            </div>
          </aside>

          {/* Content */}
          <div className="space-y-8 md:space-y-10 min-w-0">
            <section
              id="neden"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-8"
            >
              <SectionHeading>Neden Seyfibaba?</SectionHeading>
              <Body>
                Seyfibaba’da yer alan ürünleriniz yalnızca bulunduğunuz şehirde
                değil, Türkiye’nin dört bir yanındaki kuaför, berber ve güzellik
                salonlarına ulaştırılmak üzere tanıtılır.
              </Body>
              <Body>
                İstanbul’dan İzmir’e, Ankara’dan Antalya’ya, Bursa’dan
                Gaziantep’e kadar sektör profesyonellerinin ürünlerinizi
                keşfetmesini hedefliyoruz.
              </Body>
              <p className="mt-4 text-sm md:text-base font-700 text-qblacktext">
                Siz ürünlerinizi ekleyin, biz daha fazla işletmeye ulaşması için
                çalışalım.
              </p>
            </section>

            <section
              id="gorunurluk"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-8"
            >
              <SectionHeading>Ürünleriniz Türkiye genelinde görünür olsun</SectionHeading>
              <Body>
                Amacımız satıcıların ürünlerini yalnızca bir pazaryerinde
                listelemek değildir. <strong>Ürünlerinizi pazarlıyoruz.</strong>
              </Body>
              <Body>
                Platformda yayınlanan ürünler kuaförlere, berberlere, güzellik
                salonlarına ve sektör profesyonellerine ulaşabilecek şekilde
                tanıtılır. Böylece yeni müşteriler için ek bir satış ve pazarlama
                kanalı oluşturursunuz.
              </Body>
            </section>

            <section
              id="pazarlama"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-8"
            >
              <SectionHeading>Sadece satış değil, pazarlama desteği</SectionHeading>
              <Body>
                Bir pazaryerine ürün yüklemek kolaydır. Asıl önemli olan ürünün
                müşteriye ulaşmasıdır. Seyfibaba olarak satıcı ürünlerinin daha
                fazla kişiye ulaşması için dijital pazarlama çalışmalarını
                sürdürüyoruz.
              </Body>
              <Body>
                Platform büyüdükçe Seyfibaba’ya gelen her yeni kuaför, berber ve
                güzellik salonu sizin için de potansiyel müşteri anlamına gelir.
              </Body>
              <p className="mt-4 text-sm md:text-base font-700 text-qblacktext">
                Siz ürünlerinizi ekleyin. Biz Seyfibaba’yı büyütelim.
              </p>
            </section>

            <section
              id="platform"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-[#fffaf0] p-5 md:p-8"
            >
              <SectionHeading>100+ satıcı, 2.000+ ürün ve büyüyen platform</SectionHeading>
              <Body>
                Seyfibaba her geçen gün büyüyen bir sektör platformudur.
                Platformumuzda 100’den fazla satıcı ve 2.000’den fazla ürün yer
                alır.
              </Body>
              <ul className="mt-5 space-y-2.5">
                {[
                  "Daha fazla salon platforma geliyor",
                  "Daha fazla ürün keşfediliyor",
                  "Daha fazla satıcı görünürlük kazanıyor",
                  "Daha fazla işletme ile satıcı arasında bağlantı oluşuyor",
                ].map((line) => (
                  <li key={line} className="flex gap-3 text-sm md:text-base text-[#444]">
                    <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-qyellow" />
                    {line}
                  </li>
                ))}
              </ul>
              <p className="mt-5 font-700 text-qblacktext text-sm md:text-base">
                Siz de büyüyen bu ekosistemde yerinizi alın.
              </p>
            </section>

            <section
              id="urun-yukleme"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-8"
            >
              <SectionHeading>Ürünlerinizi yüklemek için saatlerinizi harcamayın</SectionHeading>
              <Body>
                Ürünlerinizi tek tek sisteme girmek zorunda değilsiniz. Ürün adı,
                açıklama, görseller, fiyat, stok ve diğer bilgiler platforma
                aktarılabilir.
              </Body>
              <Body>
                Mevcut satış sistemleriniz veya entegrasyon çözümleri üzerinden
                ürün aktarımı ve güncelleme seçenekleri sunuyoruz. Ürün yükleme
                konusunda ekibimizden de destek alabilirsiniz.
              </Body>
              <p className="mt-4 font-700 text-qblacktext text-sm md:text-base">
                Siz satışınıza odaklanın; taşıma sürecini birlikte kolaylaştıralım.
              </p>
            </section>

            <section
              id="whatsapp-destek"
              className="scroll-mt-24 rounded-2xl border border-[#c8efd8] bg-[#f3fff7] p-5 md:p-8"
            >
              <SectionHeading>WhatsApp üzerinden ürün yükleme desteği</SectionHeading>
              <Body>
                Karmaşık işlemlerle uğraşmak istemiyorsanız ürün bilgilerinizi
                ekibimize ileterek yükleme sürecinde destek alabilirsiniz.
                Görseller, isimler, fiyatlar ve gerekli bilgileri paylaşın;
                yayın sürecini birlikte yürütelim.
              </Body>
              <a
                href={WHATSAPP_URL}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-6 inline-flex items-center justify-center rounded-md bg-[#25D366] px-6 py-3.5 text-sm font-700 text-white hover:brightness-95 transition"
              >
                WhatsApp ile Bilgi Al / Destek İste
              </a>
              <p className="mt-3 text-xs text-[#666]">
                Ön tanımlı mesaj: “Merhaba, Seyfibaba&apos;da satıcı olmak istiyorum…”
              </p>
            </section>

            <section
              id="entegrasyon"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-8"
            >
              <SectionHeading>Mevcut sisteminizle çalışmaya devam edin</SectionHeading>
              <Body>
                Seyfibaba’ya katılmak için mevcut satış sisteminizden
                vazgeçmeniz gerekmez. Kendi web sitenizi, mağazanızı veya diğer
                kanallarınızı kullanırken Seyfibaba’yı ek kanal olarak
                kullanabilirsiniz.
              </Body>
              <p className="mt-4 font-700 text-qblacktext text-sm md:text-base">
                Mevcut satış kanallarınız + Seyfibaba = daha geniş satış ağı
              </p>
            </section>

            <section
              id="urunler"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-8"
            >
              <SectionHeading>Seyfibaba’da hangi ürünleri satabilirsiniz?</SectionHeading>
              <Body>
                Kuaför ve güzellik sektörüne yönelik ürünlerinizi satışa
                sunabilirsiniz.
              </Body>
              <div className="mt-6 grid sm:grid-cols-2 gap-4">
                {PRODUCT_CATEGORIES.map((cat) => (
                  <div
                    key={cat.title}
                    className="rounded-xl border border-[#ece3cf] bg-[#faf7f1] p-4"
                  >
                    <h3 className="font-700 text-qblacktext text-sm md:text-base">
                      {cat.title}
                    </h3>
                    <p className="mt-2 text-xs md:text-sm text-[#666] leading-relaxed">
                      {cat.text}
                    </p>
                  </div>
                ))}
              </div>
            </section>

            <section
              id="avantajlar"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-8"
            >
              <SectionHeading>Seyfibaba satıcılarına sağladığımız avantajlar</SectionHeading>
              <div className="mt-6 grid sm:grid-cols-2 gap-3 md:gap-4">
                {ADVANTAGES.map((item) => (
                  <div
                    key={item.title}
                    className="rounded-xl border border-[#ece3cf] p-4 flex gap-3"
                  >
                    <span className="text-qyellow font-700 shrink-0">✓</span>
                    <div>
                      <h3 className="font-700 text-qblacktext text-sm">{item.title}</h3>
                      <p className="mt-1 text-xs md:text-sm text-[#666] leading-relaxed">
                        {item.text}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
              <Body>
                Sizin ürününüz, Seyfibaba’nın pazarlama gücüyle daha fazla
                işletmeye ulaşsın. Siz ürünlerinizi ekleyin; biz platformu
                büyütelim, yeni salonlara ulaşalım ve ürünlerinizi tanıtalım.
              </Body>
            </section>

            <section
              id="surec"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-[#fffaf0] p-5 md:p-8"
            >
              <SectionHeading>Seyfibaba’ya katılmak için ne yapmalısınız?</SectionHeading>
              <ol className="mt-6 space-y-4">
                {STEPS.map((s) => (
                  <li key={s.n} className="flex gap-4">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-qyellow font-700 text-qblack">
                      {s.n}
                    </span>
                    <div>
                      <p className="font-700 text-qblacktext text-sm md:text-base">
                        {s.title}
                      </p>
                      <p className="mt-1 text-sm text-[#666] leading-relaxed">{s.text}</p>
                    </div>
                  </li>
                ))}
              </ol>
              <CtaRow className="mt-8" />
            </section>

            <section
              id="sss"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-8"
            >
              <SectionHeading>Satıcılar için sıkça sorulan sorular</SectionHeading>
              <p className="mt-3 text-sm text-[#666] leading-relaxed">{sellerFaqIntro}</p>
              <p className="mt-2 text-xs text-[#888]">
                Soldaki SSS listesinden bir soruya tıklayınca yanıt burada açılır.
              </p>

              <div className="mt-6 space-y-5">
                {sellerFaqSections.map((section, si) => (
                  <div key={section.title}>
                    <h3 className="text-xs font-700 uppercase tracking-wide text-[#9a7b2f] mb-2">
                      {section.title}
                    </h3>
                    <div className="rounded-xl border border-[#ece3cf] overflow-hidden">
                      {section.items.map((item, qi) => {
                        const key = `${si}-${qi}`;
                        const isOpen = openFaq === key;
                        return (
                          <div
                            key={key}
                            id={`faq-${key}`}
                            className="border-b border-[#f0e8d4] last:border-b-0"
                          >
                            <button
                              type="button"
                              onClick={() => setOpenFaq(isOpen ? null : key)}
                              className="w-full text-left px-4 py-3.5 flex justify-between gap-3 items-start hover:bg-[#faf7f1] transition"
                              aria-expanded={isOpen}
                            >
                              <span className="font-600 text-qblacktext text-sm">
                                {item.q}
                              </span>
                              <span className="text-qyellow shrink-0">{isOpen ? "−" : "+"}</span>
                            </button>
                            {isOpen ? (
                              <div className="px-4 pb-4 text-sm text-[#555] leading-relaxed bg-[#fffdf8]">
                                {item.a}
                              </div>
                            ) : null}
                          </div>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>

              <div className="mt-8 rounded-xl border border-[#c8efd8] bg-[#f3fff7] p-4 md:p-5">
                <p className="text-sm text-[#444] leading-relaxed">
                  Aklınızda soru kaldıysa WhatsApp’tan yazın. Mesaj{" "}
                  <strong>“satıcı olmak istiyorum”</strong> ile açılır; ekibimiz
                  dönüş yapar.
                </p>
                <a
                  href={WHATSAPP_URL}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="mt-4 inline-flex items-center justify-center rounded-md bg-[#25D366] px-5 py-3 text-sm font-700 text-white"
                >
                  WhatsApp ile Sor
                </a>
              </div>
            </section>

            <section
              id="basvuru"
              className="scroll-mt-24 rounded-2xl border border-[#ece3cf] bg-qblacktext text-white p-5 md:p-8"
            >
              <h2 className="text-2xl md:text-3xl font-bold leading-tight">
                Siz satın, biz daha fazla müşteriye ulaşmanız için çalışalım
              </h2>
              <p className="mt-4 text-sm md:text-base text-white/80 leading-relaxed">
                Seyfibaba sadece ürün listelediğiniz bir yer değil; sektör
                profesyonelleriyle buluşmanızı hedefleyen bir pazaryeridir.
                Türkiye genelindeki salonlara ulaşmak ve mevcut kanallarınıza
                yeni bir kanal eklemek istiyorsanız başvurun.
              </p>
              <div className="mt-8 flex flex-col sm:flex-row gap-3">
                <Link
                  href="/satici-kayit"
                  className="inline-flex items-center justify-center rounded-md bg-qyellow px-6 py-3.5 text-sm md:text-base font-700 text-qblack hover:brightness-95 transition"
                >
                  Satışa Başla
                </Link>
                <a
                  href={WHATSAPP_URL}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center justify-center rounded-md bg-[#25D366] px-6 py-3.5 text-sm md:text-base font-700 text-white hover:brightness-95 transition"
                >
                  WhatsApp ile Bilgi Al
                </a>
                <Link
                  href="/satici-giris"
                  className="inline-flex items-center justify-center rounded-md border border-white/30 px-6 py-3.5 text-sm md:text-base font-700 text-white hover:bg-white/10 transition"
                >
                  Satıcı Girişi
                </Link>
              </div>
              <p className="mt-6 text-xs text-white/55">
                Seyfibaba.com — Kuaför, berber ve güzellik sektörünün online
                pazaryeri
              </p>
            </section>
          </div>
        </div>
      </div>

      {/* Mobile sticky CTAs */}
      <div className="lg:hidden fixed bottom-0 inset-x-0 z-40 border-t border-[#ece3cf] bg-white/95 backdrop-blur px-3 py-2.5 flex gap-2 safe-area-pb">
        <Link
          href="/satici-kayit"
          className="flex-1 inline-flex items-center justify-center rounded-md bg-qyellow py-3 text-xs font-700 text-qblack"
        >
          Kayıt Ol
        </Link>
        <a
          href={WHATSAPP_URL}
          target="_blank"
          rel="noopener noreferrer"
          className="flex-1 inline-flex items-center justify-center rounded-md bg-[#25D366] py-3 text-xs font-700 text-white"
        >
          WhatsApp
        </a>
      </div>
      <div className="lg:hidden h-16" />
    </div>
  );
}
