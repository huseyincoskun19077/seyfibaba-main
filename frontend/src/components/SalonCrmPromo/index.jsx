"use client";

import Link from "next/link";
import { useSelector } from "react-redux";
import PageTitle from "../Helpers/PageTitle";
import GooglePlay from "../Helpers/icons/GooglePlay";
import AppleStore from "../Helpers/icons/AppleStore";

const FEATURES = [
  {
    title: "Randevu",
    text: "Günlük saat tablosu, mola, müşteri talebi ve onay. Dolu saate dokununca detay açılır.",
  },
  {
    title: "Personel",
    text: "Personel ekleyin, çalışma saatleri, hizmet ücretleri ve bugünkü iş özetini görün.",
  },
  {
    title: "Müşteri",
    text: "QR veya kod ile salonunuza bağlanır. Online talep gönderir, siz onaylarsınız.",
  },
  {
    title: "Kasa ve performans",
    text: "Günlük tahsilat, nakit/kart ve personelin ne kadar iş yaptığını tek ekranda takip edin.",
  },
];

export default function SalonCrmPromo() {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const download = websiteSetup?.payload?.flashSaleSidebarBanner;
  const playStoreUrl = download?.play_store?.trim();
  const appStoreUrl = download?.app_store?.trim();
  const hasAppLinks = Boolean(playStoreUrl || appStoreUrl);

  return (
    <div className="w-full bg-[#fdfdfd]">
      <PageTitle
        title="Salon CRM"
        breadcrumb={[
          { name: "Anasayfa", path: "/" },
          { name: "Salon CRM", path: "/salon-crm" },
        ]}
      />

      <div className="container-x mx-auto py-10 pb-16">
        <div className="rounded-3xl bg-qblack text-white px-6 py-10 md:px-12 md:py-14">
          <p className="text-qyellow text-xs font-800 uppercase tracking-[0.18em] mb-3">
            Seyfibaba mobil uygulama
          </p>
          <h2 className="text-3xl md:text-4xl font-800 leading-tight max-w-3xl">
            Kuaför ve berber salonunuzun randevusunu, kasasını ve personelini
            cebinizden yönetin.
          </h2>
          <p className="mt-4 max-w-2xl text-white/80 text-[15px] leading-7">
            Salon CRM web paneli değildir. Randevu almak, mola eklemek, müşteri
            onaylamak ve gün sonu bakiyeyi görmek için Seyfibaba uygulamasını
            indirmeniz yeterli. Ücretsiz tanışın, salonu uygulamadan açın.
          </p>
          <div className="mt-8 flex flex-wrap items-center gap-4">
            {playStoreUrl && (
              <a
                href={playStoreUrl}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Google Play üzerinden Seyfibaba uygulamasını indir"
              >
                <GooglePlay />
              </a>
            )}
            {appStoreUrl && (
              <a
                href={appStoreUrl}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="App Store üzerinden Seyfibaba uygulamasını indir"
              >
                <AppleStore />
              </a>
            )}
            {!hasAppLinks && (
              <p className="text-sm text-qyellow">
                Uygulamayı telefonunuzdaki mağazadan “Seyfibaba” yazarak bulun.
              </p>
            )}
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-5 mt-10">
          {FEATURES.map((item) => (
            <div
              key={item.title}
              className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
            >
              <h3 className="text-lg font-800 text-qblack mb-2">{item.title}</h3>
              <p className="text-sm text-[#4b5563] leading-6">{item.text}</p>
            </div>
          ))}
        </div>

        <div className="mt-10 rounded-2xl bg-qyellow px-6 py-8 md:px-10">
          <h3 className="text-xl font-800 text-qblack">Neden uygulamadan?</h3>
          <p className="mt-2 text-sm text-qblack/80 leading-6 max-w-3xl">
            Personel ve müşteri bildirimleri telefona gelir. QR ile müşteri
            bağlanır, WhatsApp hatırlatması atılır, kasa günü kapanır. Tüm bu
            akış Seyfibaba mobil uygulamasında; tarayıcıdan salon yönetimi yok.
          </p>
          <Link
            href="/products"
            className="inline-flex mt-5 h-11 items-center rounded-xl bg-qblack px-5 text-sm font-700 text-white"
          >
            Uygulamayı indirdikten sonra alışverişe de bakın
          </Link>
        </div>
      </div>
    </div>
  );
}
