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
    <div className="legal-document-page w-full bg-[#f4f7f8] pb-16 min-h-screen print:bg-white">
      <div
        className="fixed top-0 left-0 h-1 bg-qyellow z-[100] print:hidden"
        style={{ width: `${progress}%` }}
        aria-hidden="true"
      />

      <div className="w-full mb-6 print:mb-4">
        <PageTitle
          breadcrumb={[
            { name: ServeLangItem()?.home || "Ana Sayfa", path: "/" },
            { name: document.title, path: `/legal/${document.slug}` },
          ]}
          title={document.title}
        />
      </div>

      <div className="container-x mx-auto px-4">
        <div className="mx-auto max-w-[860px]">
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
            <div className="text-sm text-[#5c6b76]">
              {updatedLabel && <span>Son güncelleme: {updatedLabel}</span>}
              {document.version && (
                <span className="ml-3 inline-flex rounded-full bg-white px-2 py-0.5 text-xs text-[#04334a] ring-1 ring-[#d7e0e6]">
                  v{document.version}
                </span>
              )}
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={handlePrint}
                className="h-9 rounded-md border border-[#d7e0e6] bg-white px-3 text-sm text-[#04334a] hover:bg-[#f7fbfc]"
              >
                Yazdır
              </button>
              <button
                type="button"
                onClick={handlePdf}
                className="h-9 rounded-md border border-[#d7e0e6] bg-white px-3 text-sm text-[#04334a] hover:bg-[#f7fbfc]"
                title="PDF olarak kaydetmek için yazdır menüsünden PDF seçin"
              >
                PDF İndir
              </button>
            </div>
          </div>

          {headings.length > 0 && (
            <nav
              aria-label="İçindekiler"
              className="mb-4 rounded-2xl border border-[#e3eaee] bg-white p-5 print:hidden"
            >
              <h2 className="mb-3 text-sm font-semibold text-[#04334a]">İçindekiler</h2>
              <ol className="space-y-2 text-sm">
                {headings.map((item) => (
                  <li key={item.id} className={item.level === "h3" ? "ml-4" : ""}>
                    <a href={`#${item.id}`} className="text-[#3d6f86] hover:text-[#04334a] hover:underline">
                      {item.text}
                    </a>
                  </li>
                ))}
              </ol>
            </nav>
          )}

          <article
            ref={contentRef}
            className="legal-document-content rounded-2xl border border-[#e3eaee] bg-white px-5 py-8 text-[#1c2430] sm:px-10 sm:py-10"
            dangerouslySetInnerHTML={{ __html: document.content || "<p>İçerik henüz eklenmedi.</p>" }}
          />

          <p className="mt-6 text-sm text-[#5c6b76] print:hidden">
            Sorularınız için{" "}
            <Link href="/contact" className="text-[#04334a] underline">
              iletişim
            </Link>{" "}
            sayfamızı ziyaret edebilirsiniz.
          </p>
        </div>
      </div>

      <style jsx global>{`
        .legal-document-content {
          overflow-x: auto;
          font-size: 15px;
          line-height: 1.75;
          background: #fff !important;
          color: #1c2430 !important;
        }
        .legal-document-content h2,
        .legal-document-content h3 {
          color: #04334a;
          font-weight: 700;
          line-height: 1.35;
          scroll-margin-top: 6rem;
        }
        .legal-document-content h2 {
          margin: 2rem 0 0.75rem;
          font-size: 1.25rem;
        }
        .legal-document-content h2:first-child {
          margin-top: 0;
        }
        .legal-document-content h3 {
          margin: 1.5rem 0 0.5rem;
          font-size: 1.05rem;
        }
        .legal-document-content p,
        .legal-document-content li {
          margin-bottom: 0.85rem;
        }
        .legal-document-content ul,
        .legal-document-content ol {
          margin: 0 0 1rem 1.25rem;
        }
        .legal-document-content a {
          color: #0b6e8a;
          text-decoration: underline;
        }
        .legal-document-content table {
          width: 100%;
          margin: 1rem 0 1.5rem;
          border-collapse: collapse;
          font-size: 14px;
        }
        .legal-document-content th,
        .legal-document-content td {
          border: 1px solid #e3eaee;
          padding: 0.65rem 0.75rem;
          text-align: left;
          vertical-align: top;
          background: #fff;
          color: #1c2430;
        }
        .legal-document-content th {
          background: #f4f7f8;
          color: #04334a;
        }
        @media print {
          .legal-document-page nav,
          .legal-document-page button,
          footer,
          header {
            display: none !important;
          }
          .legal-document-content {
            border: 0 !important;
            max-width: 100% !important;
          }
        }
      `}</style>
    </div>
  );
}
