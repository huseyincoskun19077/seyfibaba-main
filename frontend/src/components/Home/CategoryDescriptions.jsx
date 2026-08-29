import Link from "next/link";

const blogTopics = [
  {
    title: "Profesyonel Erkek Kuaför Malzemeleri",
    summary: "Modern erkek kuaför salonları için doğru ekipman seçimi rehberi. Tıraş makineleri, makas setleri ve salon malzemeleri hakkında bilgi edinin.",
    href: "/blogs",
  },
  {
    title: "Kuaför Tezgahı ve Salon Mobilyaları",
    summary: "Salon tasarımınıza uygun modern, klasik ve kompakt mobilya çözümlerini keşfedin. Ergonomik ve dayanıklı seçenekler.",
    href: "/blogs",
  },
  {
    title: "Salon Ekipmanları ve Berber Koltuğu",
    summary: "Hidrolik koltuk modelleri, saç yıkama setleri ve sterilizasyon ekipmanları hakkında profesyonel rehber.",
    href: "/blogs",
  },
];

export default function CategoryDescriptions() {
  return (
    <section
      aria-labelledby="blog-highlights-heading"
      className="container-x mx-auto"
    >
      <div className="rounded-[32px] bg-white px-6 py-10 shadow-sm ring-1 ring-[#f1eadc] md:px-10 md:py-14">
        <div className="mb-10 flex items-center justify-between">
          <div>
            <h2
              id="blog-highlights-heading"
              className="text-3xl font-black text-[#1f2937] md:text-4xl"
            >
              Blog & Rehberler
            </h2>
            <p className="mt-3 text-[15px] leading-8 text-[#4b5563] max-w-2xl">
              Profesyonel salon ekipmanları hakkında güncel yazılar, ürün karşılaştırmaları ve sektör haberleri.
            </p>
          </div>
          <Link
            href="/blogs"
            className="hidden sm:flex items-center gap-2 rounded-full bg-qyellow px-5 py-2.5 text-sm font-semibold text-qblack hover:opacity-90 transition"
          >
            Tüm Yazılar
            <span>→</span>
          </Link>
        </div>
        <div className="grid gap-6 lg:grid-cols-3">
          {blogTopics.map((topic) => (
            <Link
              key={topic.title}
              href={topic.href}
              className="group rounded-3xl bg-[#fffaf0] p-6 ring-1 ring-[#eee0be] transition hover:ring-qyellow hover:shadow-md"
            >
              <h3 className="text-xl font-bold text-[#22223b] group-hover:text-qyellow transition">
                {topic.title}
              </h3>
              <p className="mt-3 text-sm leading-7 text-[#4b5563]">
                {topic.summary}
              </p>
              <span className="mt-4 inline-block text-sm font-semibold text-qyellow">
                Devamını Oku →
              </span>
            </Link>
          ))}
        </div>
        <div className="mt-6 sm:hidden text-center">
          <Link
            href="/blogs"
            className="inline-flex items-center gap-2 rounded-full bg-qyellow px-5 py-2.5 text-sm font-semibold text-qblack"
          >
            Tüm Blog Yazıları →
          </Link>
        </div>
      </div>
    </section>
  );
}
