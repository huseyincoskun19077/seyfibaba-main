"use client";

import { useEffect, useState } from "react";
import apiRoutes from "@/appConfig/apiRoutes";

/**
 * Yasal belgeyi modal / popup olarak gösterir.
 */
export default function LegalDocumentModal({ slug, title, open, onClose }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [doc, setDoc] = useState(null);

  useEffect(() => {
    if (!open || !slug) return;

    let cancelled = false;
    setLoading(true);
    setError("");
    setDoc(null);

    (async () => {
      try {
        const res = await fetch(`${apiRoutes.legalDocumentShow}/${slug}`, {
          headers: { Accept: "application/json" },
        });
        if (!res.ok) throw new Error("Belge yüklenemedi");
        const data = await res.json();
        if (!cancelled) {
          setDoc(data?.document || null);
          if (!data?.document) setError("Belge bulunamadı veya henüz yayınlanmadı.");
        }
      } catch {
        if (!cancelled) setError("Belge yüklenemedi. Lütfen daha sonra tekrar deneyin.");
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [open, slug]);

  useEffect(() => {
    if (!open) return;
    const onKey = (e) => {
      if (e.key === "Escape") onClose?.();
    };
    document.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = prev;
    };
  }, [open, onClose]);

  if (!open) return null;

  const heading = doc?.title || title || "Yasal Belge";

  return (
    <div
      className="fixed inset-0 z-[10050] flex items-end justify-center sm:items-center sm:p-4"
      role="dialog"
      aria-modal="true"
      aria-label={heading}
    >
      <button
        type="button"
        className="absolute inset-0 bg-black/50"
        aria-label="Kapat"
        onClick={onClose}
      />
      <div className="relative z-10 flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
        <div className="flex items-start justify-between gap-3 border-b border-gray-100 px-4 py-3 sm:px-5">
          <div className="min-w-0">
            <h2 className="truncate text-base font-700 text-[#04334a] sm:text-lg">
              {heading}
            </h2>
            {doc?.version ? (
              <p className="mt-0.5 text-xs text-qgray">Sürüm {doc.version}</p>
            ) : null}
          </div>
          <button
            type="button"
            onClick={onClose}
            className="shrink-0 rounded-lg px-2 py-1 text-sm font-600 text-qgray hover:bg-gray-100 hover:text-qblack"
          >
            Kapat
          </button>
        </div>
        <div className="flex-1 overflow-y-auto px-4 py-4 sm:px-5">
          {loading ? (
            <p className="py-10 text-center text-sm text-qgray">Yükleniyor…</p>
          ) : error ? (
            <p className="py-10 text-center text-sm text-qred">{error}</p>
          ) : (
            <div
              className="legal-modal-content prose prose-sm max-w-none text-[#1D1D1D] prose-headings:text-[#04334a]"
              dangerouslySetInnerHTML={{ __html: doc?.content || "" }}
            />
          )}
        </div>
      </div>
    </div>
  );
}
