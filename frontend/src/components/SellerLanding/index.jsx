"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import Accodion from "@/components/Helpers/Accodion";
import { sellerFaqSections } from "@/data/sellerFaq";
import { sellerInfoPageList } from "@/data/sellerInfoPages";

const WHATSAPP_DIGITS = "908503035073";
const WHATSAPP_TEXT =
  "Merhaba, Kuaför Tedarik'te satıcı olmak istiyorum. Bilgi almak istiyorum.";
const WHATSAPP_URL = `https://wa.me/${WHATSAPP_DIGITS}?text=${encodeURIComponent(WHATSAPP_TEXT)}`;

const SELLER_TOPICS = [
  {
    id: "baslangic",
    title: "Satıcı olmak",
    keywords: ["satıcı", "kayıt", "başvuru", "nasıl", "kyc", "üyelik"],
  },
  {
    id: "komisyon",
    title: "Komisyon & hakediş",
    keywords: ["komisyon", "hakediş", "ödeme", "iban", "iyzico", "para", "%10"],
  },
  {
    id: "urun",
    title: "Ürün yükleme",
    keywords: ["ürün", "yükleme", "stok", "fiyat", "excel", "ai"],
  },
  {
    id: "kargo",
    title: "Sipariş & kargo",
    keywords: ["kargo", "sipariş", "teslimat", "gönderi", "takip"],
  },
  {
    id: "iade",
    title: "İade süreçleri",
    keywords: ["iade", "iptal", "cayma", "geri"],
  },
  {
    id: "entegrasyon",
    title: "Entegrasyon",
    keywords: ["entegrasyon", "sentos", "softtr", "sistem", "api"],
  },
  {
    id: "avantajlar",
    title: "Neden Kuaför Tedarik?",
    keywords: ["neden", "avantaj", "görünürlük", "pazarlama", "sektör"],
  },
  {
    id: "rehber",
    title: "Satıcı rehberi",
    type: "guides",
  },
  {
    id: "basvuru",
    title: "Başvuru yap",
    type: "link",
    href: "/satici-kayit",
  },
  {
    id: "giris",
    title: "Satıcı girişi",
    type: "link",
    href: "/satici-giris",
  },
  {
    id: "whatsapp",
    title: "WhatsApp bilgi hattı",
    type: "external",
    href: WHATSAPP_URL,
  },
  {
    id: "yardim",
    title: "Alıcı yardım merkezi",
    type: "link",
    href: "/yardim",
  },
];

const TOPIC_FAQS = {
  baslangic: [
    {
      id: "b1",
      question: "Satıcı olmak için neler gerekiyor?",
      answer:
        "KYC doğrulama, Iyzico alt üye işyeri kaydı (TC kimlik zorunlu), vergi levhası doğrulaması ve geçerli IBAN. Vergi levhası, sektöre özel satış yaptığınızı teyit etmek içindir. Aylık abonelik ücreti yoktur.",
    },
    {
      id: "b2",
      question: "Nasıl başvururum?",
      answer:
        "Satıcı başvuru formunu doldurun veya WhatsApp bilgi hattından (0850 303 5073) yazın. Kayıt sonrası SMS ile gelen şifreyle satıcı paneline giriş yaparsınız.",
    },
    {
      id: "b3",
      question: "Başvuru sonrası ne olur?",
      answer:
        "Hesabınız oluşturulur; panelden mağaza bilgilerini, KYC belgelerini ve ürünlerinizi tamamlayın. Onay sonrası ürünleriniz vitrinde görünür.",
    },
  ],
  komisyon: [
    {
      id: "k1",
      question: "Komisyon oranı nedir?",
      answer:
        "Kuaför Tedarik platform komisyonu sabit %10’dur. Aylık abonelik, gizli listeleme veya ek platform ücreti yoktur.",
    },
    {
      id: "k2",
      question: "Param ne zaman hesabıma geçer?",
      answer:
        "Müşteri ödemesi Iyzico güvenli havuzuna düşer. Teslimat ve iade süreci sorunsuz tamamlanınca %10 komisyon kesilir; kalan tutar doğrulanmış IBAN’ınıza aktarılır. Hakediş onayı, paranın anında bankaya geçtiği anlamına gelmez; Iyzico takvimine bağlıdır.",
    },
  ],
  urun: [
    {
      id: "u1",
      question: "Ürünleri nasıl eklerim?",
      answer:
        "Panelden tek ürün, hızlı ürün ekleme (AI) veya Excel toplu yükleme ile ekleyebilirsiniz. Fotoğraf, stok, fiyat ve ölçüleri güncel tutun. İsterseniz ürün yükleme desteği alın.",
    },
    {
      id: "u2",
      question: "Hangi ürünleri satabilirim?",
      answer:
        "Kuaför/berber mobilyaları, salon ekipmanları, profesyonel saç ve bakım ürünleri, kozmetik ve yedek parçalar. Yasaklı ürün politikasına uygun olmalıdır.",
    },
  ],
  kargo: [
    {
      id: "c1",
      question: "Kargo ücretini kim öder?",
      answer:
        "Kargo bedeli satıcıya aittir. Siparişleri kendi anlaşmalı kargo firmanızla gönderip takip numarasını satıcı paneline girersiniz.",
    },
    {
      id: "c2",
      question: "Kendi kargomla gönderebilir miyim?",
      answer:
        "Evet. Anlaşmalı kargo firmanızla gönderip takip numarasını panele girmeniz yeterlidir.",
    },
  ],
  iade: [
    {
      id: "i1",
      question: "İade talebi gelirse ne yapmalıyım?",
      answer:
        "Talepler İade Talepleri bölümüne düşer. Kabul veya ret verirsiniz; onaylanan iadelerde tutar müşteriye iade edilir ve hakedişinizden düşülür.",
    },
  ],
  entegrasyon: [
    {
      id: "e1",
      question: "Mevcut sistemimle çalışabilir miyim?",
      answer:
        "Uygun entegratör ve stok/fiyat yazılımlarıyla ürün aktarımı desteklenir. Başvuru formunda kullandığınız entegratörü belirtebilirsiniz; ekip yönlendirir.",
    },
  ],
  avantajlar: [
    {
      id: "a1",
      question: "Neden Kuaför Tedarik?",
      answer:
        "Yalnızca kuaför, berber ve güzellik sektörüne odaklıyız. Türkiye geneli görünürlük, şeffaf %10 komisyon, Iyzico güvencesi ve sektöre özel alıcı kitlesi sunarız.",
    },
    {
      id: "a2",
      question: "Ürünlerim nerede görünür?",
      answer:
        "Web sitesi ve mobil uygulamada. Platform pazarlama çalışmalarıyla ürünlerinizi daha fazla salona ulaştırmayı hedefler.",
    },
  ],
};

function SearchIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden>
      <circle cx="11" cy="11" r="7" stroke="currentColor" strokeWidth="2" />
      <path d="M20 20l-3.5-3.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </svg>
  );
}

function matchesKeywords(text, keywords = []) {
  const hay = String(text || "").toLowerCase();
  return keywords.some((k) => hay.includes(String(k).toLowerCase()));
}

function buildFaqPool() {
  const fromSections = sellerFaqSections.flatMap((section, si) =>
    section.items.map((item, qi) => ({
      id: `sf-${si}-${qi}`,
      question: item.q,
      answer: item.a,
      section: section.title,
    }))
  );
  const fromTopics = Object.values(TOPIC_FAQS).flat();
  const seen = new Set();
  const pool = [];
  [...fromTopics, ...fromSections].forEach((f) => {
    const key = f.question.toLowerCase();
    if (seen.has(key)) return;
    seen.add(key);
    pool.push(f);
  });
  return pool;
}

export default function SellerLanding() {
  const [query, setQuery] = useState("");
  const [activeId, setActiveId] = useState(null);

  const allFaqs = useMemo(() => buildFaqPool(), []);

  const categoryFaqs = useMemo(() => {
    const map = {};
    SELLER_TOPICS.forEach((topic) => {
      if (topic.type) return;
      const base = TOPIC_FAQS[topic.id] || [];
      const fromPool = allFaqs.filter((f) =>
        matchesKeywords(`${f.question} ${f.answer}`, topic.keywords || [])
      );
      const merged = [...base];
      fromPool.forEach((f) => {
        if (!merged.some((m) => m.question.toLowerCase() === f.question.toLowerCase())) {
          merged.push(f);
        }
      });
      map[topic.id] = merged;
    });
    return map;
  }, [allFaqs]);

  const searchResults = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return { faqs: [], guides: [] };

    const faqs = allFaqs.filter(
      (f) =>
        f.question.toLowerCase().includes(q) ||
        f.answer.toLowerCase().includes(q)
    );

    const guides = sellerInfoPageList.filter(
      (g) =>
        g.title.toLowerCase().includes(q) ||
        String(g.description || "").toLowerCase().includes(q)
    );

    return { faqs, guides };
  }, [query, allFaqs]);

  const activeTopic = SELLER_TOPICS.find((t) => t.id === activeId) || null;
  const showingSearch = query.trim().length > 1;
  const showingGuides = activeId === "rehber";
  const listFaqs = showingSearch
    ? searchResults.faqs
    : activeTopic && !activeTopic.type
      ? categoryFaqs[activeTopic.id] || []
      : [];

  const openTopic = (topic) => {
    setQuery("");
    if (topic.type === "link" && topic.href) {
      window.location.href = topic.href;
      return;
    }
    if (topic.type === "external" && topic.href) {
      window.open(topic.href, "_blank", "noopener,noreferrer");
      return;
    }
    setActiveId(topic.id);
  };

  return (
    <div className="w-full min-h-[70vh] bg-[#f4f7f9]">
      {/* Hero — kurumsal, yardım merkezi ile aynı dil */}
      <section className="relative overflow-hidden border-b border-[#04334a]/10">
        <div
          className="absolute inset-0"
          style={{
            background:
              "radial-gradient(ellipse 80% 70% at 50% 0%, rgba(252,191,73,0.28), transparent 55%), linear-gradient(180deg, #eef3f6 0%, #f4f7f9 100%)",
          }}
        />
        <div
          className="pointer-events-none absolute inset-0 opacity-25"
          style={{
            backgroundImage:
              "repeating-linear-gradient(90deg, transparent, transparent 48px, rgba(4,51,74,0.04) 49px), repeating-linear-gradient(0deg, transparent, transparent 48px, rgba(4,51,74,0.04) 49px)",
          }}
        />
        <div className="relative container-x mx-auto px-4 py-12 md:py-16 text-center">
          <p className="mb-3 text-xs font-800 uppercase tracking-widest text-[#04334a]/50">
            Kuaför Tedarik Satıcı Merkezi
          </p>
          <h1 className="mb-3 text-2xl font-800 text-[#04334a] md:text-4xl">
            Satıcı olmak için neye ihtiyacın var?
          </h1>
          <p className="mx-auto mb-6 max-w-2xl text-sm text-[#04334a]/65 md:text-base">
            Kuaför, berber ve güzellik sektörüne özel pazaryerinde satışa başlayın.
            Komisyon, hakediş, ürün yükleme ve başvuru adımlarını buradan bulun.
          </p>
          <div className="relative mx-auto max-w-xl">
            <label htmlFor="satici-ara" className="sr-only">
              Satıcı merkezinde ara
            </label>
            <input
              id="satici-ara"
              type="search"
              value={query}
              onChange={(e) => {
                setQuery(e.target.value);
                if (e.target.value.trim()) setActiveId(null);
              }}
              placeholder="Satıcı Merkezinde Ara"
              className="h-14 w-full rounded-xl border border-[#04334a]/15 bg-white pl-5 pr-14 text-[#04334a] shadow-sm outline-none placeholder:text-[#04334a]/40 focus:border-qyellow focus:ring-2 focus:ring-qyellow/40"
            />
            <span className="absolute right-4 top-1/2 -translate-y-1/2 text-qyellow">
              <SearchIcon />
            </span>
          </div>
          <div className="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link
              href="/satici-kayit"
              className="inline-flex h-12 min-w-[180px] items-center justify-center rounded-lg bg-[#04334a] px-6 text-sm font-800 text-white hover:bg-[#032736]"
            >
              Satıcı Başvurusu
            </Link>
            <Link
              href="/satici-giris"
              className="inline-flex h-12 min-w-[180px] items-center justify-center rounded-lg border border-[#04334a]/20 bg-white px-6 text-sm font-800 text-[#04334a] hover:border-qyellow hover:bg-[#FFF8E8]"
            >
              Satıcı Girişi
            </Link>
          </div>
        </div>
      </section>

      <div className="container-x mx-auto px-4 py-8 md:py-10">
        {(showingSearch || activeId) && (
          <button
            type="button"
            onClick={() => {
              setActiveId(null);
              setQuery("");
            }}
            className="mb-5 text-sm font-700 text-[#04334a]/70 hover:text-[#04334a]"
          >
            ← Tüm konular
          </button>
        )}

        {!showingSearch && !activeId ? (
          <div className="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4 lg:grid-cols-4">
            {SELLER_TOPICS.map((topic) => (
              <button
                key={topic.id}
                type="button"
                onClick={() => openTopic(topic)}
                className={`min-h-[96px] rounded-xl border border-[#04334a]/10 bg-white px-4 py-5 text-center text-sm font-800 text-[#04334a] shadow-sm transition hover:border-qyellow hover:shadow-md md:min-h-[112px] md:text-[15px] ${
                  topic.id === "basvuru" ? "ring-1 ring-qyellow/50" : ""
                }`}
              >
                {topic.title}
              </button>
            ))}
          </div>
        ) : null}

        {/* Guides */}
        {(showingGuides || (showingSearch && searchResults.guides.length > 0)) && (
          <div className={`max-w-3xl mx-auto ${showingSearch ? "mb-8" : ""}`}>
            {!showingSearch ? (
              <h2 className="mb-4 text-xl font-800 text-[#04334a]">Satıcı rehberi</h2>
            ) : (
              <h2 className="mb-4 text-lg font-800 text-[#04334a]">
                Rehber sayfaları ({searchResults.guides.length})
              </h2>
            )}
            <div className="grid gap-3 sm:grid-cols-2">
              {(showingSearch ? searchResults.guides : sellerInfoPageList).map((g) => (
                <Link
                  key={g.slug}
                  href={g.href}
                  className="rounded-xl border border-[#04334a]/10 bg-white p-4 transition hover:border-qyellow hover:shadow-sm"
                >
                  <p className="text-sm font-800 text-[#04334a]">{g.title}</p>
                  <p className="mt-1 line-clamp-2 text-xs leading-relaxed text-[#04334a]/55">
                    {g.description}
                  </p>
                </Link>
              ))}
            </div>
          </div>
        )}

        {/* FAQ list / search */}
        {(showingSearch || (activeId && !showingGuides && !activeTopic?.type)) && (
          <div className="mx-auto max-w-3xl">
            <h2 className="mb-4 text-xl font-800 text-[#04334a]">
              {showingSearch
                ? `Soru sonuçları${searchResults.faqs.length ? ` (${searchResults.faqs.length})` : ""}`
                : activeTopic?.title}
            </h2>

            {listFaqs.length === 0 && !(showingSearch && searchResults.guides.length) ? (
              <div className="rounded-xl border border-[#04334a]/10 bg-white p-8 text-center">
                <p className="mb-4 text-sm text-[#04334a]/60">
                  Bu konuda sonuç bulunamadı.
                </p>
                <Link
                  href="/satici-kayit"
                  className="inline-flex h-11 items-center justify-center rounded-lg bg-qyellow px-5 text-sm font-800 text-[#04334a]"
                >
                  Satıcı başvurusu yap
                </Link>
              </div>
            ) : listFaqs.length > 0 ? (
              <div className="flex flex-col gap-3">
                {listFaqs.map((faq) => (
                  <div
                    key={faq.id}
                    className="overflow-hidden rounded-xl border border-[#04334a]/10 bg-white"
                  >
                    <Accodion title={faq.question} des={faq.answer} />
                  </div>
                ))}
              </div>
            ) : null}

            <div className="mt-8 rounded-xl border border-dashed border-[#04334a]/20 bg-white p-5 text-center">
              <p className="mb-3 text-sm text-[#04334a]/70">
                Hâlâ sorunuz mu var?
              </p>
              <div className="flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a
                  href={WHATSAPP_URL}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex h-11 items-center justify-center rounded-lg bg-[#25D366] px-5 text-sm font-800 text-white"
                >
                  WhatsApp ile sor
                </a>
                <Link
                  href="/yardim?kategori=destek"
                  className="inline-flex h-11 items-center justify-center rounded-lg bg-[#04334a] px-5 text-sm font-800 text-white"
                >
                  Destek talebi oluştur
                </Link>
              </div>
            </div>
          </div>
        )}

        {/* Home bottom CTA */}
        {!showingSearch && !activeId ? (
          <div className="mt-10 rounded-2xl bg-[#04334a] px-6 py-8 text-center text-white">
            <h2 className="mb-2 text-lg font-800 md:text-xl">
              Satışa hazır mısınız?
            </h2>
            <p className="mx-auto mb-5 max-w-md text-sm text-white/70">
              Başvurunuzu tamamlayın; SMS ile giriş bilgileriniz gelsin. Aylık
              abonelik yok, komisyon şeffaf %10.
            </p>
            <div className="flex flex-col items-center justify-center gap-3 sm:flex-row">
              <Link
                href="/satici-kayit"
                className="inline-flex h-12 items-center justify-center rounded-lg bg-qyellow px-6 text-sm font-800 text-[#04334a] hover:brightness-95"
              >
                Satıcı Başvurusu
              </Link>
              <a
                href={WHATSAPP_URL}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex h-12 items-center justify-center rounded-lg border border-white/30 bg-transparent px-6 text-sm font-800 text-white hover:bg-white/10"
              >
                WhatsApp Bilgi Al
              </a>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}
