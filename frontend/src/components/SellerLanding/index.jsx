"use client";

import Link from "next/link";

const benefits = [
  {
    title: "Hazır müşteri kitlesi",
    text: "Kuaför, berber ve güzellik sektörüne ürün arayan profesyoneller Seyfibaba’da alışveriş yapıyor.",
  },
  {
    title: "Kolay mağaza kurulumu",
    text: "Hızlı kayıt sonrası panelle ürün ekleyin, stok ve siparişleri tek yerden yönetin.",
  },
  {
    title: "Güvenli ödeme altyapısı",
    text: "Pazaryeri ödeme sistemiyle satışlarınızı düzenli takip edin, operasyonu sadeleştirin.",
  },
  {
    title: "Türkiye geneli görünürlük",
    text: "Ürünleriniz kategori ve arama sonuçlarında daha fazla salona ulaşır.",
  },
];

const audiences = [
  "Kuaför ve berber malzemesi satan firmalar",
  "Salon mobilyası ve ekipman tedarikçileri",
  "Kozmetik ve profesyonel saç ürünleri toptancıları",
  "Tırnak, cilt bakım ve güzellik ekipmanı satıcıları",
  "Marka distribütörleri ve yetkili bayiler",
];

const steps = [
  {
    n: "1",
    title: "Üye olun",
    text: "Hızlı satıcı kaydıyla hesabınızı oluşturun.",
  },
  {
    n: "2",
    title: "Mağazanızı açın",
    text: "İşletme bilgilerinizi tamamlayıp paneli kullanmaya başlayın.",
  },
  {
    n: "3",
    title: "Ürün ekleyin, satın",
    text: "Katalogunuzu yayınlayın; siparişleri satıcı panelinden yönetin.",
  },
];

export default function SellerLanding() {
  return (
    <div className="w-full bg-[#faf7f1]">
      {/* Hero */}
      <section className="relative overflow-hidden border-b border-[#ece3cf]">
        <div
          className="pointer-events-none absolute inset-0 opacity-70"
          style={{
            background:
              "radial-gradient(ellipse 80% 60% at 20% 0%, #ffe8b8 0%, transparent 55%), radial-gradient(ellipse 70% 50% at 90% 20%, #f3e6c8 0%, transparent 50%)",
          }}
        />
        <div className="container-x mx-auto relative px-4 py-14 md:py-20">
          <p className="text-sm font-600 tracking-wide text-[#9a7b2f] mb-3">
            Seyfibaba Satıcı Platformu
          </p>
          <h1 className="max-w-3xl text-3xl md:text-5xl font-bold text-qblacktext leading-tight">
            Kuaför ve berber sektörüne ürün satıyorsanız, doğru adres Seyfibaba.
          </h1>
          <p className="mt-5 max-w-2xl text-base md:text-lg text-[#5c5c5c] leading-relaxed">
            Türkiye’nin berber, kuaför ve güzellik malzemeleri pazaryerinde
            mağazanızı açın. Reklamdan gelen satıcı adaylarını burada
            karşılıyoruz — hemen üye olun veya mevcut hesabınızla giriş yapın.
          </p>

          <div className="mt-8 flex flex-col sm:flex-row gap-3 sm:gap-4">
            <Link
              href="/satici-kayit"
              className="inline-flex items-center justify-center rounded-md bg-qyellow px-7 py-3.5 text-base font-700 text-qblack hover:brightness-95 transition"
            >
              Üye Ol — Satıcı Kaydı
            </Link>
            <Link
              href="/satici-giris"
              className="inline-flex items-center justify-center rounded-md border border-[#d9c89a] bg-white px-7 py-3.5 text-base font-700 text-qblacktext hover:bg-[#fffaf0] transition"
            >
              Satıcı Girişi
            </Link>
          </div>

          <p className="mt-4 text-sm text-[#7a7a7a]">
            Kayıt sonrası SMS/e-posta ile giriş bilgileriniz iletilir. Zaten
            satıcıysanız doğrudan giriş yapın.
          </p>
        </div>
      </section>

      {/* Benefits */}
      <section className="container-x mx-auto px-4 py-14 md:py-16">
        <h2 className="text-2xl md:text-3xl font-bold text-qblacktext text-center">
          Neden Seyfibaba’da satın?
        </h2>
        <p className="mt-3 text-center text-[#666] max-w-2xl mx-auto">
          Tek vitrinde sektör odaklı alıcılarla buluşun; kendi sitenizi büyütmek
          yerine hazır pazaryeri trafiğinden yararlanın.
        </p>
        <div className="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
          {benefits.map((item) => (
            <div
              key={item.title}
              className="rounded-2xl border border-[#ece3cf] bg-white p-5 md:p-6"
            >
              <h3 className="text-lg font-700 text-qblacktext">{item.title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-[#666]">{item.text}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Who */}
      <section className="border-y border-[#ece3cf] bg-[#fffaf0]">
        <div className="container-x mx-auto px-4 py-14 md:py-16">
          <div className="grid md:grid-cols-2 gap-10 items-start">
            <div>
              <h2 className="text-2xl md:text-3xl font-bold text-qblacktext">
                Kimler için?
              </h2>
              <p className="mt-3 text-[#666] leading-relaxed">
                Salonlara ürün veya ekipman tedarik eden firmaları
                platformumuza davet ediyoruz. Amaç; sektördeki satıcıları tek
                pazaryerinde buluşturmak.
              </p>
              <ul className="mt-6 space-y-3">
                {audiences.map((line) => (
                  <li key={line} className="flex gap-3 text-[#444]">
                    <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-qyellow" />
                    <span className="text-sm md:text-base leading-relaxed">{line}</span>
                  </li>
                ))}
              </ul>
            </div>
            <div className="rounded-2xl border border-[#ece3cf] bg-white p-6 md:p-8">
              <h3 className="text-xl font-700 text-qblacktext">Nasıl başlarım?</h3>
              <ol className="mt-6 space-y-5">
                {steps.map((s) => (
                  <li key={s.n} className="flex gap-4">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-qyellow font-700 text-qblack">
                      {s.n}
                    </span>
                    <div>
                      <p className="font-700 text-qblacktext">{s.title}</p>
                      <p className="mt-1 text-sm text-[#666]">{s.text}</p>
                    </div>
                  </li>
                ))}
              </ol>
              <div className="mt-8 flex flex-col gap-3">
                <Link
                  href="/satici-kayit"
                  className="inline-flex items-center justify-center rounded-md bg-qyellow px-6 py-3 font-700 text-qblack hover:brightness-95 transition"
                >
                  Üye Ol
                </Link>
                <Link
                  href="/satici-giris"
                  className="inline-flex items-center justify-center rounded-md border border-[#d9c89a] px-6 py-3 font-700 text-qblacktext hover:bg-[#faf7f1] transition"
                >
                  Satıcı Girişi
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Bottom CTA */}
      <section className="container-x mx-auto px-4 py-14 md:py-16 text-center">
        <h2 className="text-2xl md:text-3xl font-bold text-qblacktext">
          Mağazanızı bugün açın
        </h2>
        <p className="mt-3 text-[#666] max-w-xl mx-auto">
          Reklamdan geldiyseniz doğru sayfadasınız. Kaydınızı tamamlayın veya
          mevcut satıcı hesabınızla panele girin.
        </p>
        <div className="mt-8 flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center">
          <Link
            href="/satici-kayit"
            className="inline-flex items-center justify-center rounded-md bg-qyellow px-8 py-3.5 text-base font-700 text-qblack hover:brightness-95 transition"
          >
            Üye Ol
          </Link>
          <Link
            href="/satici-giris"
            className="inline-flex items-center justify-center rounded-md border border-[#d9c89a] bg-white px-8 py-3.5 text-base font-700 text-qblacktext hover:bg-[#fffaf0] transition"
          >
            Satıcı Girişi
          </Link>
        </div>
        <p className="mt-6 text-sm text-[#888]">
          Sorularınız için{" "}
          <Link href="/contact" className="underline hover:text-qblacktext">
            iletişim
          </Link>{" "}
          sayfamızı kullanabilirsiniz.
        </p>
      </section>
    </div>
  );
}
