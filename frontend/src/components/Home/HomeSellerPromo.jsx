import Link from "next/link";

export default function HomeSellerPromo() {
  return (
    <section className="container-x mx-auto">
      <div className="relative overflow-hidden rounded-3xl border border-[#ece3cf] bg-[#fffaf0]">
        <div
          className="pointer-events-none absolute inset-0 opacity-80"
          style={{
            background:
              "radial-gradient(ellipse 70% 80% at 0% 50%, #ffe8b8 0%, transparent 55%), radial-gradient(ellipse 50% 60% at 100% 0%, #f3e6c8 0%, transparent 45%)",
          }}
        />
        <div className="relative px-5 py-8 md:px-10 md:py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div className="max-w-2xl">
            <p className="text-xs md:text-sm font-700 tracking-wide text-[#9a7b2f] uppercase mb-2">
              Satıcılar için
            </p>
            <h2 className="text-xl md:text-3xl font-bold text-qblacktext leading-tight">
              Ürünlerinizi Türkiye genelindeki salonlara satmak ister misiniz?
            </h2>
            <p className="mt-3 text-sm md:text-base text-[#555] leading-relaxed">
              Kuaför, berber ve güzellik ürünlerinizi Seyfibaba’da satışa açın.
              Ürün yükleme desteği, pazarlama ve şeffaf komisyon hakkında tüm
              detaylar satıcı sayfasında.
            </p>
          </div>
          <div className="flex flex-col sm:flex-row gap-3 shrink-0">
            <Link
              href="/satici"
              className="inline-flex items-center justify-center rounded-md bg-qyellow px-6 py-3.5 text-sm font-700 text-qblack hover:brightness-95 transition"
            >
              Satıcı Bilgi Sayfası
            </Link>
            <Link
              href="/satici-kayit"
              className="inline-flex items-center justify-center rounded-md border border-[#d9c89a] bg-white px-6 py-3.5 text-sm font-700 text-qblacktext hover:bg-white/80 transition"
            >
              Hemen Kayıt Ol
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}
