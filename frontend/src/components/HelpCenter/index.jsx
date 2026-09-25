"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import Accodion from "@/components/Helpers/Accodion";
import ContactForm from "@/components/Contact/ContactForm";
import { legalPath } from "@/config/legalDocuments";

const HELP_CATEGORIES = [
  {
    id: "populer",
    title: "Popüler Sorular",
    keywords: ["nasıl", "nedir", "seyfibaba", "üyelik", "kayıt"],
  },
  {
    id: "iade",
    title: "İade",
    keywords: ["iade", "cayma", "geri", "değişim", "iptal"],
    links: [{ href: legalPath("delivery-return"), label: "Teslimat ve İade Koşulları" }],
  },
  {
    id: "kargo",
    title: "Kargo ve Teslimat",
    keywords: ["kargo", "teslimat", "gönderi", "kurye", "süre"],
    links: [{ href: "/tracking-order", label: "Sipariş Takibi" }],
  },
  {
    id: "siparis",
    title: "Siparişler",
    keywords: ["sipariş", "fatura", "ödeme", "taksit", "iptal"],
    links: [{ href: "/profile#order", label: "Siparişlerim" }],
  },
  {
    id: "hakkinda",
    title: "Kuaför Tedarik Hakkında",
    keywords: ["hakkında", "kim", "güven", "platform", "pazaryeri"],
    links: [{ href: "/about", label: "Biz Kimiz" }],
  },
  {
    id: "hesabim",
    title: "Hesabım",
    keywords: ["hesap", "şifre", "profil", "adres", "üyelik"],
    links: [{ href: "/profile", label: "Hesabıma Git" }],
  },
  {
    id: "alisveris",
    title: "Ürün & Alışveriş",
    keywords: ["ürün", "sepet", "alışveriş", "fiyat", "stok", "marka"],
    links: [{ href: "/products", label: "Tüm Ürünler" }],
  },
  {
    id: "rehber",
    title: "İşlem Rehberi",
    keywords: ["nasıl", "adım", "rehber", "sipariş ver"],
    links: [{ href: "/tracking-order", label: "Sipariş Takip Rehberi" }],
  },
  {
    id: "destek",
    title: "Destek Talebi Oluştur",
    type: "support",
  },
  {
    id: "iletisim",
    title: "İletişim",
    type: "link",
    href: "/contact",
  },
  {
    id: "satici",
    title: "Satıcı Olmak",
    type: "link",
    href: "/satici",
  },
];

const FALLBACK_FAQS = {
  populer: [
    {
      id: "f1",
      question: "Kuaför Tedarik nedir?",
      answer:
        "Kuaför Tedarik; kuaför, berber ve güzellik salonları için ekipman, mobilya ve sarf malzemelerini güvenilir satıcılarla buluşturan Türkiye pazaryeridir.",
    },
    {
      id: "f2",
      question: "Nasıl sipariş verebilirim?",
      answer:
        "Ürünü sepete ekleyin, adresinizi seçin ve ödeme adımında Iyzico güvenli ödeme ile siparişinizi tamamlayın. Siparişlerinizi Hesabım > Siparişlerim’den takip edebilirsiniz.",
    },
    {
      id: "f3",
      question: "Destek talebi nasıl oluştururum?",
      answer:
        "Bu sayfada “Destek Talebi Oluştur” kartına tıklayın veya aşağıdaki formu doldurun. Talebiniz destek ekibimize iletilir.",
    },
  ],
  iade: [
    {
      id: "f4",
      question: "İade süreci nasıl işler?",
      answer:
        "Hesabım > Siparişlerim üzerinden iade talebi oluşturun. Ürün kullanılmamış ve orijinal ambalajında olmalıdır. Onay sonrası kargo ve kontrol adımlarını takip edin.",
    },
  ],
  kargo: [
    {
      id: "f5",
      question: "Kargo süresi ne kadar?",
      answer:
        "Teslimat süresi satıcıya ve ürüne göre değişir. Sipariş detayında tahmini süre ve kargo takip bilgisini görebilirsiniz.",
    },
  ],
  siparis: [
    {
      id: "f6",
      question: "Siparişimi nasıl takip ederim?",
      answer:
        "Giriş yaptıktan sonra Hesabım > Siparişlerim veya Sipariş Takip sayfasından sipariş numaranızla durumu görüntüleyebilirsiniz.",
    },
  ],
  hesabim: [
    {
      id: "f7",
      question: "Şifremi unuttum, ne yapmalıyım?",
      answer:
        "Giriş sayfasındaki “Şifremi Unuttum” bağlantısıyla e-posta veya telefonunuza sıfırlama adımlarını takip edin.",
    },
  ],
  alisveris: [
    {
      id: "f8",
      question: "Taksit seçenekleri var mı?",
      answer:
        "Taksit imkânı ürün kategorisine ve kartınıza göre değişir. Ödeme ekranında uygun taksit seçeneklerini görebilirsiniz.",
    },
  ],
  hakkinda: [
    {
      id: "f9",
      question: "Ödemeler güvenli mi?",
      answer:
        "Ödemeler Iyzico altyapısı ile 3D Secure üzerinden alınır. Kart bilgileriniz Kuaför Tedarik sunucularında saklanmaz.",
    },
  ],
  rehber: [
    {
      id: "f10",
      question: "İlk alışverişe nereden başlamalıyım?",
      answer:
        "Kategorilerden veya aramadan ürün bulun, satıcı ve kargo bilgisini kontrol edin, sepete ekleyip ödemeyi tamamlayın. Sorun olursa Destek Talebi oluşturun.",
    },
  ],
};

function normalizeFaq(item) {
  if (!item) return null;
  const question = String(item.question || "").trim();
  const answer = String(item.answer || item.ans || "").trim();
  if (!question || !answer) return null;
  return { id: item.id || question, question, answer };
}

function matchesKeywords(text, keywords = []) {
  const hay = String(text || "").toLowerCase();
  return keywords.some((k) => hay.includes(String(k).toLowerCase()));
}

function SearchIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden>
      <circle cx="11" cy="11" r="7" stroke="currentColor" strokeWidth="2" />
      <path d="M20 20l-3.5-3.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </svg>
  );
}

export default function HelpCenter({ faqs: rawFaqs = [] }) {
  const searchParams = useSearchParams();
  const initialCat = searchParams.get("kategori") || "";
  const [query, setQuery] = useState("");
  const [activeId, setActiveId] = useState(
    initialCat === "destek" ? "destek" : initialCat || null
  );

  const adminFaqs = useMemo(
    () => (Array.isArray(rawFaqs) ? rawFaqs.map(normalizeFaq).filter(Boolean) : []),
    [rawFaqs]
  );

  useEffect(() => {
    const k = searchParams.get("kategori");
    if (k) setActiveId(k);
  }, [searchParams]);

  const categoryFaqs = useMemo(() => {
    const map = {};
    HELP_CATEGORIES.forEach((cat) => {
      if (cat.type && cat.type !== "support") return;
      const fromAdmin = adminFaqs.filter((f) =>
        matchesKeywords(`${f.question} ${f.answer}`, cat.keywords || [])
      );
      const fallback = FALLBACK_FAQS[cat.id] || [];
      const merged = [...fromAdmin];
      fallback.forEach((f) => {
        if (!merged.some((m) => m.question.toLowerCase() === f.question.toLowerCase())) {
          merged.push(f);
        }
      });
      if (cat.id === "populer" && adminFaqs.length) {
        adminFaqs.slice(0, 8).forEach((f) => {
          if (!merged.some((m) => m.id === f.id)) merged.push(f);
        });
      }
      map[cat.id] = merged;
    });
    return map;
  }, [adminFaqs]);

  const searchResults = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return [];
    const pool = [
      ...adminFaqs,
      ...Object.values(FALLBACK_FAQS).flat(),
    ];
    const seen = new Set();
    return pool.filter((f) => {
      const key = f.question.toLowerCase();
      if (seen.has(key)) return false;
      const hit =
        f.question.toLowerCase().includes(q) ||
        f.answer.toLowerCase().includes(q);
      if (hit) seen.add(key);
      return hit;
    });
  }, [query, adminFaqs]);

  const activeCategory = HELP_CATEGORIES.find((c) => c.id === activeId) || null;
  const showingSearch = query.trim().length > 1;
  const showingSupport = activeId === "destek";
  const listFaqs = showingSearch
    ? searchResults
    : activeCategory && !showingSupport
      ? categoryFaqs[activeCategory.id] || []
      : [];

  const openCategory = (cat) => {
    setQuery("");
    if (cat.type === "link" && cat.href) {
      window.location.href = cat.href;
      return;
    }
    setActiveId(cat.id);
  };

  return (
    <div className="w-full bg-[#f4f7f9] min-h-[70vh]">
      {/* Hero */}
      <section className="relative overflow-hidden border-b border-[#04334a]/10">
        <div
          className="absolute inset-0"
          style={{
            background:
              "radial-gradient(ellipse 80% 70% at 50% 0%, rgba(252,191,73,0.35), transparent 55%), linear-gradient(180deg, #fff8eb 0%, #f4f7f9 100%)",
          }}
        />
        <div
          className="absolute inset-0 opacity-30 pointer-events-none"
          style={{
            backgroundImage:
              "repeating-radial-gradient(circle at 50% 40%, transparent 0, transparent 48px, rgba(4,51,74,0.06) 49px, transparent 50px)",
          }}
        />
        <div className="relative container-x mx-auto px-4 py-12 md:py-16 text-center">
          <p className="text-xs font-800 tracking-widest uppercase text-[#04334a]/50 mb-3">
            Kuaför Tedarik Yardım Merkezi
          </p>
          <h1 className="text-2xl md:text-4xl font-800 text-[#04334a] mb-6">
            Sana nasıl yardımcı olabiliriz?
          </h1>
          <div className="mx-auto max-w-xl relative">
            <label htmlFor="yardim-ara" className="sr-only">
              Yardım sayfasında ara
            </label>
            <input
              id="yardim-ara"
              type="search"
              value={query}
              onChange={(e) => {
                setQuery(e.target.value);
                if (e.target.value.trim()) setActiveId(null);
              }}
              placeholder="Yardım Sayfasında Ara"
              className="w-full h-14 rounded-xl border border-[#04334a]/15 bg-white pl-5 pr-14 text-[#04334a] placeholder:text-[#04334a]/40 shadow-sm outline-none focus:border-qyellow focus:ring-2 focus:ring-qyellow/40"
            />
            <span className="absolute right-4 top-1/2 -translate-y-1/2 text-qyellow">
              <SearchIcon />
            </span>
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

        {/* Category grid */}
        {!showingSearch && !activeId ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
            {HELP_CATEGORIES.map((cat) => (
              <button
                key={cat.id}
                type="button"
                onClick={() => openCategory(cat)}
                className={`min-h-[96px] md:min-h-[112px] rounded-xl bg-white border border-[#04334a]/10 px-4 py-5 text-center text-sm md:text-[15px] font-800 text-[#04334a] shadow-sm hover:border-qyellow hover:shadow-md transition ${
                  cat.id === "destek" ? "ring-1 ring-qyellow/50" : ""
                }`}
              >
                {cat.title}
              </button>
            ))}
          </div>
        ) : null}

        {/* FAQ list / search */}
        {(showingSearch || (activeId && !showingSupport)) && (
          <div className="max-w-3xl mx-auto">
            <h2 className="text-xl font-800 text-[#04334a] mb-4">
              {showingSearch
                ? `Arama sonuçları${searchResults.length ? ` (${searchResults.length})` : ""}`
                : activeCategory?.title}
            </h2>

            {!showingSearch && activeCategory?.links?.length ? (
              <div className="flex flex-wrap gap-2 mb-5">
                {activeCategory.links.map((l) => (
                  <Link
                    key={l.href}
                    href={l.href}
                    className="text-xs font-700 text-[#04334a] bg-qyellow/30 hover:bg-qyellow px-3 py-1.5 rounded-lg transition"
                  >
                    {l.label}
                  </Link>
                ))}
              </div>
            ) : null}

            {listFaqs.length === 0 ? (
              <div className="rounded-xl bg-white border border-[#04334a]/10 p-8 text-center">
                <p className="text-sm text-[#04334a]/60 mb-4">
                  Bu konuda henüz içerik bulunamadı.
                </p>
                <button
                  type="button"
                  onClick={() => setActiveId("destek")}
                  className="inline-flex h-11 items-center justify-center rounded-lg bg-qyellow px-5 text-sm font-800 text-[#04334a]"
                >
                  Destek talebi oluştur
                </button>
              </div>
            ) : (
              <div className="flex flex-col gap-3">
                {listFaqs.map((faq) => (
                  <div
                    key={faq.id}
                    className="rounded-xl overflow-hidden border border-[#04334a]/10 bg-white"
                  >
                    <Accodion title={faq.question} des={faq.answer} />
                  </div>
                ))}
              </div>
            )}

            <div className="mt-8 rounded-xl border border-dashed border-[#04334a]/20 bg-white p-5 text-center">
              <p className="text-sm text-[#04334a]/70 mb-3">
                Cevabını bulamadın mı?
              </p>
              <button
                type="button"
                onClick={() => {
                  setQuery("");
                  setActiveId("destek");
                }}
                className="inline-flex h-11 items-center justify-center rounded-lg bg-[#04334a] px-5 text-sm font-800 text-white hover:brightness-110"
              >
                Destek talebi oluştur
              </button>
            </div>
          </div>
        )}

        {/* Support form */}
        {showingSupport ? (
          <div className="max-w-xl mx-auto">
            <div className="rounded-2xl bg-white border border-[#04334a]/10 shadow-sm p-5 md:p-8">
              <h2 className="text-xl font-800 text-[#04334a] mb-1">
                Destek talebi oluştur
              </h2>
              <p className="text-sm text-[#04334a]/60 mb-5">
                Formu doldurun; ekibimiz en kısa sürede size dönüş yapsın.
              </p>
              <ContactForm />
            </div>
            <p className="mt-4 text-center text-xs text-[#04334a]/45">
              Acil durumlar için{" "}
              <a href="tel:08503035073" className="font-700 text-[#04334a] underline">
                0850 303 5073
              </a>
              {" · "}
              <a
                href="mailto:info@kuafortedarik.com"
                className="font-700 text-[#04334a] underline"
              >
                info@kuafortedarik.com
              </a>
            </p>
          </div>
        ) : null}

        {/* Home bottom CTA */}
        {!showingSearch && !activeId ? (
          <div className="mt-10 rounded-2xl bg-[#04334a] text-white px-6 py-8 text-center">
            <h2 className="text-lg md:text-xl font-800 mb-2">
              Hâlâ yardıma mı ihtiyacın var?
            </h2>
            <p className="text-sm text-white/70 mb-5 max-w-md mx-auto">
              Destek talebi oluştur; sipariş, iade, kargo veya hesap konularında
              sana yardımcı olalım.
            </p>
            <button
              type="button"
              onClick={() => setActiveId("destek")}
              className="inline-flex h-12 items-center justify-center rounded-lg bg-qyellow px-6 text-sm font-800 text-[#04334a] hover:brightness-95"
            >
              Destek Talebi Oluştur
            </button>
          </div>
        ) : null}
      </div>
    </div>
  );
}
