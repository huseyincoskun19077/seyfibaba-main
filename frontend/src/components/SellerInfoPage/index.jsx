"use client";

import Link from "next/link";
import { sellerInfoPageList, sellerInfoPages } from "@/data/sellerInfoPages";

const WHATSAPP_DIGITS = "908503035073";
const WHATSAPP_TEXT =
  "Merhaba, Kuaför Tedarik'te satıcı olmak istiyorum. Bilgi almak istiyorum.";
const WHATSAPP_URL = `https://wa.me/${WHATSAPP_DIGITS}?text=${encodeURIComponent(WHATSAPP_TEXT)}`;

function CtaRow() {
  return (
    <div className="flex flex-col sm:flex-row gap-3">
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
        href="/satici"
        className="inline-flex items-center justify-center rounded-md border border-[#d9c89a] bg-white px-6 py-3.5 text-sm md:text-base font-700 text-qblacktext hover:bg-[#fffaf0] transition"
      >
        Satıcı Ana Sayfa
      </Link>
    </div>
  );
}

function RoadmapBody({ page }) {
  const steps = page.steps || [];

  return (
    <div className="rounded-2xl border border-[#ece3cf] bg-[#fffaf0] p-5 md:p-8">
      <h2 className="text-xl md:text-2xl font-bold text-qblacktext leading-tight">
        {page.roadmapTitle || "Yol haritası"}
      </h2>
      {page.roadmapSummary ? (
        <p className="mt-3 text-sm md:text-base text-[#666] leading-relaxed">
          <span className="font-700 text-qblacktext">Tek bakışta: </span>
          {page.roadmapSummary}
        </p>
      ) : null}

      <ol className="mt-8 space-y-0">
        {steps.map((step, index) => {
          const isLast = index === steps.length - 1;
          return (
            <li key={step.n} className="relative flex gap-4 md:gap-5">
              <div className="flex flex-col items-center shrink-0">
                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-qyellow font-700 text-qblack text-sm md:text-base z-[1]">
                  {step.n}
                </span>
                {!isLast ? (
                  <span
                    className="w-0.5 flex-1 min-h-[1.25rem] bg-[#e5d6a8] mt-1"
                    aria-hidden
                  />
                ) : null}
              </div>
              <div className={`pb-8 ${isLast ? "pb-0" : ""} min-w-0 flex-1`}>
                <h3 className="font-700 text-qblacktext text-base md:text-lg leading-snug pt-1.5">
                  {step.title}
                </h3>
                <p className="mt-2 text-sm md:text-base text-[#555] leading-relaxed">
                  {step.text}
                </p>
                {step.bullets?.length ? (
                  <ul className="mt-3 space-y-1.5 list-disc pl-5 text-sm text-[#666] leading-relaxed">
                    {step.bullets.map((b) => (
                      <li key={b}>{b}</li>
                    ))}
                  </ul>
                ) : null}
              </div>
            </li>
          );
        })}
      </ol>
    </div>
  );
}

function SectionsBody({ page }) {
  return (
    <>
      {(page.sections || []).map((section) => (
        <section key={section.heading} className="mb-10 last:mb-0">
          <h2 className="text-xl md:text-2xl font-bold text-qblacktext leading-tight">
            {section.heading}
          </h2>
          {(section.paragraphs || []).map((p) => (
            <p
              key={p.slice(0, 48)}
              className="mt-3 text-[#555] text-sm md:text-base leading-relaxed"
            >
              {p}
            </p>
          ))}
          {section.bullets?.length ? (
            <ul className="mt-4 space-y-2 list-disc pl-5 text-[#555] text-sm md:text-base leading-relaxed">
              {section.bullets.map((b) => (
                <li key={b}>{b}</li>
              ))}
            </ul>
          ) : null}
        </section>
      ))}
    </>
  );
}

export default function SellerInfoPage({ page }) {
  const related = (page.related || [])
    .map((slug) => sellerInfoPages[slug])
    .filter(Boolean);
  const isRoadmap = page.layout === "roadmap";

  return (
    <div className="w-full bg-[#faf7f1]">
      <section className="border-b border-[#ebe3d4] bg-gradient-to-b from-[#fffdf8] to-[#faf7f1]">
        <div className="container-x mx-auto px-4 md:px-6 py-10 md:py-14 max-w-3xl">
          <nav className="text-xs md:text-sm text-[#888] mb-4" aria-label="Sayfa yolu">
            <Link href="/satici" className="hover:text-qblacktext">
              Satıcı Ol
            </Link>
            <span className="mx-2">/</span>
            <span className="text-qblacktext">{page.h1}</span>
          </nav>
          <h1 className="text-3xl md:text-4xl font-bold text-qblacktext leading-tight">
            {page.h1}
          </h1>
          <p className="mt-4 text-[#555] text-base md:text-lg leading-relaxed">
            {page.lead}
          </p>
          <div className="mt-8">
            <CtaRow />
          </div>
        </div>
      </section>

      <article className="container-x mx-auto px-4 md:px-6 py-10 md:py-14 max-w-3xl">
        {isRoadmap ? <RoadmapBody page={page} /> : <SectionsBody page={page} />}

        {related.length > 0 ? (
          <aside className="mt-12 pt-8 border-t border-[#ebe3d4]">
            <h2 className="text-lg font-bold text-qblacktext mb-4">
              İlgili satıcı sayfaları
            </h2>
            <ul className="space-y-2">
              {related.map((r) => (
                <li key={r.slug}>
                  <Link
                    href={`/satici/${r.slug}`}
                    className="text-sm md:text-base text-qblacktext underline underline-offset-2 hover:text-qyellow"
                  >
                    {r.h1}
                  </Link>
                </li>
              ))}
            </ul>
          </aside>
        ) : null}

        <aside className="mt-12 pt-8 border-t border-[#ebe3d4]">
          <h2 className="text-lg font-bold text-qblacktext mb-3">
            Tüm satıcı rehberi
          </h2>
          <ul className="grid sm:grid-cols-2 gap-2">
            {sellerInfoPageList.map((item) => (
              <li key={item.slug}>
                <Link
                  href={item.href}
                  className={`text-sm leading-snug ${
                    item.slug === page.slug
                      ? "font-700 text-qblacktext"
                      : "text-[#555] hover:text-qblacktext underline underline-offset-2"
                  }`}
                >
                  {item.title}
                </Link>
              </li>
            ))}
          </ul>
          <div className="mt-8">
            <CtaRow />
          </div>
        </aside>
      </article>
    </div>
  );
}
