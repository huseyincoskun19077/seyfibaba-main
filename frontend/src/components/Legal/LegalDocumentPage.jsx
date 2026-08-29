"use client";

import Link from "next/link";
import { useEffect, useMemo, useRef, useState } from "react";
import PageTitle from "../Helpers/PageTitle";
import ServeLangItem from "../Helpers/ServeLangItem";

function slugifyHeading(text) {
  return String(text || "")
    .trim()
    .toLowerCase()
    .replace(/[^\w\sğüşıöçĞÜŞİÖÇ-]/g, "")
    .replace(/\s+/g, "-");
}

function extractHeadings(html) {
  if (!html || typeof window === "undefined") return [];

  const container = document.createElement("div");
  container.innerHTML = html;
  const nodes = container.querySelectorAll("h2, h3");

  return Array.from(nodes).map((node, index) => {
    const level = node.tagName.toLowerCase();
    const text = node.textContent?.trim() || `Bölüm ${index + 1}`;
    const id = slugifyHeading(text) || `section-${index + 1}`;
    return { id, text, level };
  });
}

export default function LegalDocumentPage({ document }) {
  const contentRef = useRef(null);
  const [progress, setProgress] = useState(0);
  const [headings, setHeadings] = useState([]);

  const updatedLabel = useMemo(() => {
    if (!document?.updated_at) return null;
    try {
      return new Date(document.updated_at).toLocaleDateString("tr-TR", {
        day: "2-digit",
        month: "long",
        year: "numeric",
      });
    } catch {
      return null;
    }
  }, [document?.updated_at]);

  useEffect(() => {
    if (!document?.content) return;

    const parsed = extractHeadings(document.content);
    setHeadings(parsed);

    if (!contentRef.current) return;

    const withIds = contentRef.current.querySelectorAll("h2, h3");
    withIds.forEach((node, index) => {
      const match = parsed[index];
      if (match) {
        node.id = match.id;
      }
    });
  }, [document?.content]);

  useEffect(() => {
    const onScroll = () => {
      const el = contentRef.current;
      if (!el) return;

      const rect = el.getBoundingClientRect();
      const total = el.scrollHeight - window.innerHeight;
      const scrolled = Math.min(Math.max(window.scrollY - (el.offsetTop - 80), 0), total);
      const pct = total > 0 ? (scrolled / total) * 100 : 0;
      setProgress(pct);
    };

    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
    return () => window.removeEventListener("scroll", onScroll);
  }, [document?.content]);

  const handlePrint = () => {
    window.print();
  };

  const handlePdf = () => {
    window.print();
  };

  if (!document) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <p className="text-qgray">Belge bulunamadı veya henüz yayınlanmadı.</p>
      </div>
    );
  }

  return (
    <div className="legal-document-page w-full bg-white dark:bg-[#111827] pb-[40px] min-h-screen print:bg-white">
      <div
        className="fixed top-0 left-0 h-1 bg-qyellow z-[100] print:hidden"
        style={{ width: `${progress}%` }}
        aria-hidden="true"
      />

      <div className="w-full mb-[30px] print:mb-4">
        <PageTitle
          breadcrumb={[
            { name: ServeLangItem()?.home || "Ana Sayfa", path: "/" },
            { name: document.title, path: `/legal/${document.slug}` },
          ]}
          title={document.title}
        />
      </div>

      <div className="container-x mx-auto px-4">
        <div className="max-w-[900px] mx-auto">
          <div className="flex flex-wrap items-center justify-between gap-3 mb-6 print:hidden">
            <div className="text-sm text-qgray dark:text-gray-400">
              {updatedLabel && <span>Son güncelleme: {updatedLabel}</span>}
              {document.version && (
                <span className="ml-3 inline-flex rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs">
                  v{document.version}
                </span>
              )}
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={handlePrint}
                className="h-9 px-3 rounded-md border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                Yazdır
              </button>
              <button
                type="button"
                onClick={handlePdf}
                className="h-9 px-3 rounded-md border border-gray-200 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                title="PDF olarak kaydetmek için yazdır menüsünden PDF seçin"
              >
                PDF İndir
              </button>
            </div>
          </div>

          {headings.length > 0 && (
            <nav
              aria-label="İçindekiler"
              className="mb-8 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-5 print:hidden"
            >
              <h2 className="text-sm font-semibold text-qblack dark:text-white mb-3">İçindekiler</h2>
              <ol className="space-y-2 text-sm">
                {headings.map((item) => (
                  <li key={item.id} className={item.level === "h3" ? "ml-4" : ""}>
                    <a
                      href={`#${item.id}`}
                      className="text-qgray dark:text-gray-300 hover:text-qblack dark:hover:text-white underline-offset-2 hover:underline"
                    >
                      {item.text}
                    </a>
                  </li>
                ))}
              </ol>
            </nav>
          )}

          <article
            ref={contentRef}
            className="legal-document-content prose prose-neutral dark:prose-invert max-w-none prose-headings:scroll-mt-24 prose-h2:text-xl prose-h3:text-lg prose-p:text-[15px] prose-p:leading-7 prose-li:text-[15px] text-qblack dark:text-gray-100"
            dangerouslySetInnerHTML={{ __html: document.content || "<p>İçerik henüz eklenmedi.</p>" }}
          />

          <div className="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700 text-sm text-qgray dark:text-gray-400 print:hidden">
            <p>
              Sorularınız için{" "}
              <Link href="/contact" className="underline hover:text-qblack dark:hover:text-white">
                iletişim
              </Link>{" "}
              sayfamızı ziyaret edebilirsiniz.
            </p>
          </div>
        </div>
      </div>

      <style jsx global>{`
        @media print {
          .legal-document-page nav,
          .legal-document-page button,
          footer,
          header {
            display: none !important;
          }
          .legal-document-content {
            max-width: 100% !important;
          }
        }
      `}</style>
    </div>
  );
}
